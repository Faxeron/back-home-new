<?php

require __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;

$app = app();
$db = DB::connection('legacy_new');

// Mark the problematic migration as run 
$migrations = [
    '2025_11_18_141554_create_transactions_table',
    '2025_11_18_233618_create_finance_support_tables',
    '2025_11_19_104036_create_additional_finance_tables',
    '2025_11_19_104815_create_sale_types_table',
];

foreach ($migrations as $migration) {
    $exists = $db->table('migrations')->where('migration', $migration)->exists();
    if (!$exists) {
        $db->table('migrations')->insert([
            'migration' => $migration,
            'batch' => 1,
        ]);
        echo "Marked: $migration\n";
    } else {
        echo "Already marked: $migration\n";
    }
}

echo "Done!\n";
