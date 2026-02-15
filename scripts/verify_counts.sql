\set ON_ERROR_STOP on

\if :{?period_from}
\else
\set period_from '1900-01-01'
\endif

\if :{?period_to}
\else
\set period_to '2999-12-31'
\endif

\if :{?companies_limit}
\else
\set companies_limit '5'
\endif

\echo '=== KEY TABLE COUNTS ==='
WITH key_counts AS (
    SELECT 'tenants' AS table_name, CASE WHEN to_regclass('public.tenants') IS NULL THEN NULL::bigint ELSE (SELECT COUNT(*) FROM tenants)::bigint END AS row_count
    UNION ALL
    SELECT 'companies', CASE WHEN to_regclass('public.companies') IS NULL THEN NULL::bigint ELSE (SELECT COUNT(*) FROM companies)::bigint END
    UNION ALL
    SELECT 'users', CASE WHEN to_regclass('public.users') IS NULL THEN NULL::bigint ELSE (SELECT COUNT(*) FROM users)::bigint END
    UNION ALL
    SELECT 'counterparties', CASE WHEN to_regclass('public.counterparties') IS NULL THEN NULL::bigint ELSE (SELECT COUNT(*) FROM counterparties)::bigint END
    UNION ALL
    SELECT 'contracts', CASE WHEN to_regclass('public.contracts') IS NULL THEN NULL::bigint ELSE (SELECT COUNT(*) FROM contracts)::bigint END
    UNION ALL
    SELECT 'projects', CASE WHEN to_regclass('public.projects') IS NULL THEN NULL::bigint ELSE (SELECT COUNT(*) FROM projects)::bigint END
    UNION ALL
    SELECT 'transactions', CASE WHEN to_regclass('public.transactions') IS NULL THEN NULL::bigint ELSE (SELECT COUNT(*) FROM transactions)::bigint END
    UNION ALL
    SELECT 'receipts', CASE WHEN to_regclass('public.receipts') IS NULL THEN NULL::bigint ELSE (SELECT COUNT(*) FROM receipts)::bigint END
    UNION ALL
    SELECT 'spendings', CASE WHEN to_regclass('public.spendings') IS NULL THEN NULL::bigint ELSE (SELECT COUNT(*) FROM spendings)::bigint END
)
SELECT table_name, row_count
FROM key_counts
ORDER BY table_name;

\echo '=== MULTI-TENANT INVARIANTS (NULL tenant_id/company_id) ==='
WITH table_names AS (
    SELECT unnest(ARRAY[
        'companies',
        'users',
        'counterparties',
        'contracts',
        'projects',
        'transactions',
        'receipts',
        'spendings'
    ]) AS table_name
),
columns_meta AS (
    SELECT
        t.table_name,
        EXISTS (
            SELECT 1
            FROM information_schema.columns c
            WHERE c.table_schema = 'public'
              AND c.table_name = t.table_name
              AND c.column_name = 'tenant_id'
        ) AS has_tenant,
        EXISTS (
            SELECT 1
            FROM information_schema.columns c
            WHERE c.table_schema = 'public'
              AND c.table_name = t.table_name
              AND c.column_name = 'company_id'
        ) AS has_company
    FROM table_names t
)
SELECT
    table_name,
    has_tenant,
    has_company
FROM columns_meta
ORDER BY table_name;

\echo '=== SAMPLE COMPANIES (TOP BY TX COUNT) ==='
WITH params AS (
    SELECT
        COALESCE(NULLIF(:'companies_limit', ''), '5')::int AS companies_limit
)
SELECT
    c.id AS company_id,
    COUNT(t.id)::bigint AS tx_count_all_time
FROM companies c
LEFT JOIN transactions t ON t.company_id = c.id
CROSS JOIN params p
GROUP BY c.id, p.companies_limit
ORDER BY tx_count_all_time DESC, c.id
LIMIT (SELECT companies_limit FROM params);

\echo '=== PER-COMPANY CHECK (COUNTS + TRANSACTION SUM FOR PERIOD) ==='
WITH params AS (
    SELECT
        COALESCE(NULLIF(:'period_from', ''), '1900-01-01')::date AS period_from,
        COALESCE(NULLIF(:'period_to', ''), '2999-12-31')::date AS period_to,
        COALESCE(NULLIF(:'companies_limit', ''), '5')::int AS companies_limit
),
companies_sample AS (
    SELECT
        c.id AS company_id
    FROM companies c
    LEFT JOIN transactions t ON t.company_id = c.id
    CROSS JOIN params p
    GROUP BY c.id, p.companies_limit
    ORDER BY COUNT(t.id) DESC, c.id
    LIMIT (SELECT companies_limit FROM params)
)
SELECT
    cs.company_id,
    tx.tx_count,
    tx.tx_sum,
    rc.receipts_count,
    sp.spendings_count
FROM companies_sample cs
LEFT JOIN LATERAL (
    SELECT
        COUNT(*)::bigint AS tx_count,
        COALESCE(SUM(t.sum), 0)::numeric(18,2) AS tx_sum
    FROM transactions t
    CROSS JOIN params p
    WHERE t.company_id = cs.company_id
      AND t.date_is_paid::date BETWEEN p.period_from AND p.period_to
) tx ON true
LEFT JOIN LATERAL (
    SELECT COUNT(*)::bigint AS receipts_count
    FROM receipts r
    WHERE r.company_id = cs.company_id
) rc ON true
LEFT JOIN LATERAL (
    SELECT COUNT(*)::bigint AS spendings_count
    FROM spendings s
    WHERE s.company_id = cs.company_id
) sp ON true
ORDER BY cs.company_id;

