[CmdletBinding()]
param(
    [ValidateSet('all', 'snapshot', 'schema', 'restore', 'data', 'verify')]
    [string[]]$Stage = @('all'),

    [string]$SnapshotDir = 'storage\db-migration',
    [string]$SnapshotName = '',
    [string]$SnapshotDatabase = 'erp_snapshot_import',

    [string]$SourceHost = '',
    [int]$SourcePort = 3306,
    [string]$SourceDatabase = '',
    [string]$SourceUser = '',
    [string]$SourcePassword = '',

    [string]$PgHost = '',
    [int]$PgPort = 5432,
    [string]$PgDatabase = '',
    [string]$PgUser = '',
    [string]$PgPassword = '',

    [int]$Chunk = 1000,
    [int]$Companies = 5,
    [string]$PeriodFrom = '',
    [string]$PeriodTo = '',

    [switch]$DisableConstraints,
    [switch]$KeepSnapshotDb,
    [switch]$AllowMismatch
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

function Get-DotEnvValue {
    param(
        [string]$DotEnvPath,
        [string]$Key
    )

    if (-not (Test-Path $DotEnvPath)) {
        return $null
    }

    $line = Get-Content -Path $DotEnvPath -Encoding UTF8 | Where-Object { $_ -match "^\s*$([regex]::Escape($Key))=" } | Select-Object -First 1
    if (-not $line) {
        return $null
    }

    $value = $line.Substring($line.IndexOf('=') + 1).Trim()
    if ($value.StartsWith('"') -and $value.EndsWith('"')) {
        $value = $value.Trim('"')
    }

    return $value
}

function Resolve-Setting {
    param(
        [string]$ProvidedValue,
        [string]$EnvKey,
        [string]$DotEnvPath,
        [string]$Fallback
    )

    if ($ProvidedValue -ne '') {
        return $ProvidedValue
    }

    $processValue = [Environment]::GetEnvironmentVariable($EnvKey)
    if ($processValue -and $processValue.Trim() -ne '') {
        return $processValue.Trim()
    }

    $dotEnvValue = Get-DotEnvValue -DotEnvPath $DotEnvPath -Key $EnvKey
    if ($dotEnvValue -and $dotEnvValue.Trim() -ne '') {
        return $dotEnvValue.Trim()
    }

    return $Fallback
}

function Assert-Identifier {
    param([string]$Value, [string]$Label)
    if ($Value -notmatch '^[A-Za-z0-9_]+$') {
        throw "$Label must match ^[A-Za-z0-9_]+$ (received '$Value')."
    }
}

$repoRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$dotEnvPath = Join-Path $repoRoot '.env'

$SourceHost = Resolve-Setting -ProvidedValue $SourceHost -EnvKey 'LEGACY_NEW_DB_HOST' -DotEnvPath $dotEnvPath -Fallback '127.0.0.1'
$SourceDatabase = Resolve-Setting -ProvidedValue $SourceDatabase -EnvKey 'LEGACY_NEW_DB_DATABASE' -DotEnvPath $dotEnvPath -Fallback ''
$SourceUser = Resolve-Setting -ProvidedValue $SourceUser -EnvKey 'LEGACY_NEW_DB_USERNAME' -DotEnvPath $dotEnvPath -Fallback 'root'
$SourcePassword = Resolve-Setting -ProvidedValue $SourcePassword -EnvKey 'LEGACY_NEW_DB_PASSWORD' -DotEnvPath $dotEnvPath -Fallback ''

$PgHost = Resolve-Setting -ProvidedValue $PgHost -EnvKey 'DB_HOST' -DotEnvPath $dotEnvPath -Fallback '127.0.0.1'
$PgDatabase = Resolve-Setting -ProvidedValue $PgDatabase -EnvKey 'DB_DATABASE' -DotEnvPath $dotEnvPath -Fallback 'erp'
$PgUser = Resolve-Setting -ProvidedValue $PgUser -EnvKey 'DB_USERNAME' -DotEnvPath $dotEnvPath -Fallback 'postgres'
$PgPassword = Resolve-Setting -ProvidedValue $PgPassword -EnvKey 'DB_PASSWORD' -DotEnvPath $dotEnvPath -Fallback ''

if ($SourceDatabase -eq '') {
    throw 'Source database is required. Provide --SourceDatabase or LEGACY_NEW_DB_DATABASE in .env.'
}

Assert-Identifier -Value $SourceDatabase -Label 'Source database'
Assert-Identifier -Value $SnapshotDatabase -Label 'Snapshot database'
Assert-Identifier -Value $PgDatabase -Label 'PostgreSQL database'

if ($Companies -lt 1) {
    throw 'Companies must be >= 1.'
}

if ($PeriodFrom -eq '') {
    $PeriodFrom = (Get-Date).AddMonths(-1).ToString('yyyy-MM-01')
}

if ($PeriodTo -eq '') {
    $PeriodTo = (Get-Date).ToString('yyyy-MM-dd')
}

$stages = if ($Stage -contains 'all') {
    @('snapshot', 'schema', 'restore', 'data', 'verify')
} else {
    $Stage
}

$runId = Get-Date -Format 'yyyyMMdd_HHmmss'
$runDir = Join-Path $repoRoot (Join-Path $SnapshotDir $runId)
New-Item -ItemType Directory -Path $runDir -Force | Out-Null

$logPath = Join-Path $runDir 'migrate_mysql_to_pg.log'
New-Item -ItemType File -Path $logPath -Force | Out-Null

$dumpFileName = if ($SnapshotName -ne '') { $SnapshotName } else { "mysql_snapshot_${runId}.sql" }
$dumpPath = Join-Path $runDir $dumpFileName
$countsPath = Join-Path $runDir 'mysql_snapshot_counts.tsv'
$countsSqlPath = Join-Path $runDir 'mysql_snapshot_counts.sql'
$dataReportPath = Join-Path $runDir 'artisan_data_report.json'
$verifyReportPath = Join-Path $runDir 'artisan_verify_report.json'
$verifySqlOutputPath = Join-Path $runDir 'verify_counts_psql.out'
$artisanDataLogPath = Join-Path $runDir 'artisan_data_stage.log'
$artisanVerifyLogPath = Join-Path $runDir 'artisan_verify_stage.log'

function Write-Log {
    param([string]$Message)
    $line = "[{0}] {1}" -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'), $Message
    $line | Tee-Object -FilePath $logPath -Append
}

function Require-Tool {
    param([string]$Name)
    if (-not (Get-Command $Name -ErrorAction SilentlyContinue)) {
        throw "Required tool '$Name' is not installed or not in PATH."
    }
}

function Invoke-PhpArtisan {
    param(
        [string[]]$Arguments,
        [string]$Description
    )

    Write-Log "RUN: $Description"
    Push-Location $repoRoot
    try {
        & php @Arguments 2>&1 | Tee-Object -FilePath $logPath -Append
        if ($LASTEXITCODE -ne 0) {
            throw "Command failed: php $($Arguments -join ' ')"
        }
    } finally {
        Pop-Location
    }
}

function Invoke-StartProcessChecked {
    param(
        [string]$FilePath,
        [string[]]$ArgumentList,
        [string]$Description,
        [string]$StdOutPath,
        [string]$StdErrPath,
        [string]$StdInPath = ''
    )

    Write-Log "RUN: $Description"

    if (Test-Path $StdOutPath) {
        Remove-Item -Path $StdOutPath -Force
    }
    if (Test-Path $StdErrPath) {
        Remove-Item -Path $StdErrPath -Force
    }

    $processArgs = @{
        FilePath               = $FilePath
        ArgumentList           = $ArgumentList
        NoNewWindow            = $true
        Wait                   = $true
        PassThru               = $true
        RedirectStandardOutput = $StdOutPath
        RedirectStandardError  = $StdErrPath
    }

    if ($StdInPath -ne '') {
        $processArgs['RedirectStandardInput'] = $StdInPath
    }

    $process = Start-Process @processArgs

    if (Test-Path $StdErrPath) {
        $stderrContent = Get-Content -Path $StdErrPath -Encoding UTF8
        foreach ($stderrLine in $stderrContent) {
            if ($stderrLine.Trim() -ne '') {
                Write-Log "stderr: $stderrLine"
            }
        }
    }

    if ($process.ExitCode -ne 0) {
        throw "$Description failed with exit code $($process.ExitCode)."
    }

    Write-Log "DONE: $Description"
}

function Set-PgEnv {
    $env:DB_CONNECTION = 'pgsql'
    $env:DB_HOST = $PgHost
    $env:DB_PORT = [string]$PgPort
    $env:DB_DATABASE = $PgDatabase
    $env:DB_USERNAME = $PgUser
    $env:DB_PASSWORD = $PgPassword
    $env:DB_CHARSET = 'utf8'

    $env:LEGACY_NEW_DB_CONNECTION = 'pgsql'
    $env:LEGACY_NEW_DB_HOST = $PgHost
    $env:LEGACY_NEW_DB_PORT = [string]$PgPort
    $env:LEGACY_NEW_DB_DATABASE = $PgDatabase
    $env:LEGACY_NEW_DB_USERNAME = $PgUser
    $env:LEGACY_NEW_DB_PASSWORD = $PgPassword
    $env:LEGACY_NEW_DB_CHARSET = 'utf8'
}

function Set-LegacySourceEnv {
    param([string]$Database)

    $env:LEGACY_DB_CONNECTION = 'mysql'
    $env:LEGACY_DB_HOST = $SourceHost
    $env:LEGACY_DB_PORT = [string]$SourcePort
    $env:LEGACY_DB_DATABASE = $Database
    $env:LEGACY_DB_USERNAME = $SourceUser
    $env:LEGACY_DB_PASSWORD = $SourcePassword
}

Write-Log "MySQL->PostgreSQL migration run_id=$runId"
Write-Log "Stages: $($stages -join ', ')"
Write-Log "Source: ${SourceHost}:${SourcePort}/${SourceDatabase}"
Write-Log "PostgreSQL: ${PgHost}:${PgPort}/${PgDatabase}"
Write-Log "Period: $PeriodFrom .. $PeriodTo | Companies=$Companies"
Write-Log "Artifacts directory: $runDir"

Require-Tool -Name 'php'
if ($stages -contains 'snapshot' -or $stages -contains 'restore') {
    Require-Tool -Name 'mysqldump'
    Require-Tool -Name 'mysql'
}
if ($stages -contains 'verify') {
    Require-Tool -Name 'psql'
}

$env:MYSQL_PWD = $SourcePassword
$env:PGPASSWORD = $PgPassword

if ($stages -contains 'snapshot') {
    $dumpErrPath = Join-Path $runDir 'mysqldump.stderr.log'
    $dumpArgs = @(
        "--host=$SourceHost",
        "--port=$SourcePort",
        "--user=$SourceUser",
        '--single-transaction',
        '--quick',
        '--routines',
        '--triggers',
        '--events',
        '--hex-blob',
        '--default-character-set=utf8mb4',
        '--skip-lock-tables',
        $SourceDatabase
    )

    Invoke-StartProcessChecked `
        -FilePath 'mysqldump' `
        -ArgumentList $dumpArgs `
        -Description 'Create MySQL snapshot dump' `
        -StdOutPath $dumpPath `
        -StdErrPath $dumpErrPath

    $countsErrPath = Join-Path $runDir 'mysql_counts.stderr.log'
    $countsQuery = @"
SELECT 'tenants' AS table_name, COUNT(*) AS row_count FROM tenants
UNION ALL
SELECT 'companies' AS table_name, COUNT(*) AS row_count FROM companies
UNION ALL
SELECT 'users' AS table_name, COUNT(*) AS row_count FROM users
UNION ALL
SELECT 'counterparties' AS table_name, COUNT(*) AS row_count FROM counterparties
UNION ALL
SELECT 'contracts' AS table_name, COUNT(*) AS row_count FROM contracts
UNION ALL
SELECT 'projects' AS table_name, NULL AS row_count
UNION ALL
SELECT 'transactions' AS table_name, COUNT(*) AS row_count FROM transactions
UNION ALL
SELECT 'receipts' AS table_name, COUNT(*) AS row_count FROM receipts
UNION ALL
SELECT 'spendings' AS table_name, COUNT(*) AS row_count FROM spendings;

SELECT company_id,
       COUNT(*) AS tx_count,
       ROUND(COALESCE(SUM(sum), 0), 2) AS tx_sum
FROM transactions
WHERE date_is_paid BETWEEN '$PeriodFrom' AND '$PeriodTo'
GROUP BY company_id
ORDER BY tx_count DESC, company_id
LIMIT $Companies;
"@

    Set-Content -Path $countsSqlPath -Value $countsQuery -Encoding UTF8

    $countsArgs = @(
        "--host=$SourceHost",
        "--port=$SourcePort",
        "--user=$SourceUser",
        "--database=$SourceDatabase",
        '--batch',
        '--raw'
    )

    try {
        Invoke-StartProcessChecked `
            -FilePath 'mysql' `
            -ArgumentList $countsArgs `
            -Description 'Capture MySQL key table counts and company transaction sums' `
            -StdOutPath $countsPath `
            -StdErrPath $countsErrPath `
            -StdInPath $countsSqlPath
    } catch {
        Write-Log "WARN: baseline MySQL counts step failed. Continuing. Reason: $($_.Exception.Message)"
    }
}

if ($stages -contains 'schema') {
    Set-PgEnv
    Invoke-PhpArtisan -Arguments @('artisan', 'migrate:fresh', '--force') -Description 'Build PostgreSQL schema from Laravel migrations'
    Invoke-PhpArtisan -Arguments @('artisan', 'migrate', '--database=legacy_new', '--path=database/migrations_new', '--force') -Description 'Apply PostgreSQL schema updates from database/migrations_new'
}

if ($stages -contains 'restore') {
    if (-not (Test-Path $dumpPath)) {
        throw "Dump file '$dumpPath' not found. Run snapshot stage first."
    }

    $restoreErrPath = Join-Path $runDir 'mysql_restore.stderr.log'
    $createSnapshotDbSqlPath = Join-Path $runDir 'mysql_restore_create_db.sql'
    $createSnapshotDbSql = @"
DROP DATABASE IF EXISTS $SnapshotDatabase;
CREATE DATABASE $SnapshotDatabase CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
"@
    Set-Content -Path $createSnapshotDbSqlPath -Value $createSnapshotDbSql -Encoding UTF8

    $createDbArgs = @(
        "--host=$SourceHost",
        "--port=$SourcePort",
        "--user=$SourceUser"
    )

    Invoke-StartProcessChecked `
        -FilePath 'mysql' `
        -ArgumentList $createDbArgs `
        -Description "Create temporary snapshot database '$SnapshotDatabase'" `
        -StdOutPath (Join-Path $runDir 'mysql_restore_create.out') `
        -StdErrPath $restoreErrPath `
        -StdInPath $createSnapshotDbSqlPath

    Invoke-StartProcessChecked `
        -FilePath 'mysql' `
        -ArgumentList @("--host=$SourceHost", "--port=$SourcePort", "--user=$SourceUser", "--database=$SnapshotDatabase") `
        -Description 'Restore snapshot dump into temporary MySQL database' `
        -StdOutPath (Join-Path $runDir 'mysql_restore_import.out') `
        -StdErrPath (Join-Path $runDir 'mysql_restore_import.stderr.log') `
        -StdInPath $dumpPath
}

if ($stages -contains 'data') {
    Set-PgEnv
    Set-LegacySourceEnv -Database $SnapshotDatabase

    $dataCommand = @(
        'artisan',
        'db:migrate-mysql-to-pg',
        '--source=legacy',
        '--target=legacy_new',
        "--chunk=$Chunk",
        '--truncate-target',
        "--companies=$Companies",
        "--period-from=$PeriodFrom",
        "--period-to=$PeriodTo",
        "--report=$dataReportPath",
        "--log=$artisanDataLogPath"
    )

    if ($DisableConstraints) {
        $dataCommand += '--disable-constraints'
    }

    if ($AllowMismatch) {
        $dataCommand += '--allow-mismatch'
    }

    Invoke-PhpArtisan -Arguments $dataCommand -Description 'Copy data from MySQL snapshot to PostgreSQL'
}

if ($stages -contains 'verify') {
    Set-PgEnv
    Set-LegacySourceEnv -Database $SnapshotDatabase

    $verifyCommand = @(
        'artisan',
        'db:migrate-mysql-to-pg',
        '--source=legacy',
        '--target=legacy_new',
        '--verify-only',
        "--companies=$Companies",
        "--period-from=$PeriodFrom",
        "--period-to=$PeriodTo",
        "--report=$verifyReportPath",
        "--log=$artisanVerifyLogPath"
    )

    if ($AllowMismatch) {
        $verifyCommand += '--allow-mismatch'
    }

    Invoke-PhpArtisan -Arguments $verifyCommand -Description 'Run cross-DB verification (counts + multi-tenant sums)'

    $verifySqlPath = Join-Path $repoRoot 'scripts\verify_counts.sql'
    if (-not (Test-Path $verifySqlPath)) {
        throw "Verification SQL script '$verifySqlPath' not found."
    }

    $psqlArgs = @(
        '--host', $PgHost,
        '--port', [string]$PgPort,
        '--username', $PgUser,
        '--dbname', $PgDatabase,
        '--set', "period_from=$PeriodFrom",
        '--set', "period_to=$PeriodTo",
        '--set', "companies_limit=$Companies",
        '--file', $verifySqlPath
    )

    Invoke-StartProcessChecked `
        -FilePath 'psql' `
        -ArgumentList $psqlArgs `
        -Description 'Run scripts/verify_counts.sql against PostgreSQL target' `
        -StdOutPath $verifySqlOutputPath `
        -StdErrPath (Join-Path $runDir 'verify_counts_psql.stderr.log')
}

if (-not $KeepSnapshotDb -and ($stages -contains 'restore' -or $stages -contains 'data' -or $stages -contains 'verify')) {
    $cleanupErrPath = Join-Path $runDir 'mysql_cleanup.stderr.log'
    $dropDbSqlPath = Join-Path $runDir 'mysql_cleanup_drop_db.sql'
    $dropDbSql = "DROP DATABASE IF EXISTS $SnapshotDatabase;"
    Set-Content -Path $dropDbSqlPath -Value $dropDbSql -Encoding UTF8

    try {
        Invoke-StartProcessChecked `
            -FilePath 'mysql' `
            -ArgumentList @("--host=$SourceHost", "--port=$SourcePort", "--user=$SourceUser") `
            -Description "Drop temporary snapshot database '$SnapshotDatabase'" `
            -StdOutPath (Join-Path $runDir 'mysql_cleanup.out') `
            -StdErrPath $cleanupErrPath `
            -StdInPath $dropDbSqlPath
    } catch {
        Write-Log "WARN: unable to drop temporary snapshot database '$SnapshotDatabase'. Reason: $($_.Exception.Message)"
    }
}

$metadata = [ordered]@{
    run_id             = $runId
    stages             = $stages
    source             = [ordered]@{
        host     = $SourceHost
        port     = $SourcePort
        database = $SourceDatabase
        user     = $SourceUser
    }
    postgres           = [ordered]@{
        host     = $PgHost
        port     = $PgPort
        database = $PgDatabase
        user     = $PgUser
    }
    snapshot_database  = $SnapshotDatabase
    dump_path          = $dumpPath
    counts_path        = $countsPath
    data_report_path   = $dataReportPath
    verify_report_path = $verifyReportPath
    verify_sql_output  = $verifySqlOutputPath
    period_from        = $PeriodFrom
    period_to          = $PeriodTo
    companies          = $Companies
    chunk              = $Chunk
    log_path           = $logPath
    finished_at        = (Get-Date).ToString('o')
}

$metadataPath = Join-Path $runDir 'run_metadata.json'
$metadata | ConvertTo-Json -Depth 5 | Set-Content -Path $metadataPath -Encoding UTF8
Write-Log "Run metadata saved: $metadataPath"
Write-Log 'MySQL->PostgreSQL migration workflow finished.'
