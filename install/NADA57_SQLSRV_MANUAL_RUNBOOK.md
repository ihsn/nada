# NADA 5.5 → 5.7 SQL Server manual upgrade runbook

Use this when **PHP CLI is unavailable** and the web migration UI times out or hits ODBC false failures. Prefer the web UI (**one migration version per request**); use SSMS for the steps below when a version fails.

**Full SQL bundle:** `install/nada56-upgrade-sqlsrv.sql` concatenates all topic SQL files in migration order. PHP-only steps (seed data, ACL copy, display templates, configuration rows) still require the web UI after running the bundle.

**Prerequisites:** full database backup, SQL Server Full-Text Search installed, `application/config/migration.php` → `migration_enabled = TRUE`.

**Connection test:** `php install/test_sqlsrv_connection.php`

---

## Migration versions (run in order)

| Version | SQL files (SSMS) | PHP-only steps (web UI) |
|---------|------------------|-------------------------|
| `20260701000001` | `nada55-upgrade-sqlsrv.sql`, `nada55-add-authtype-users-sqlsrv.sql`, `nada56-api-keys-security-sqlsrv.sql` | `resource_type` backfill from `dctype` |
| `20260701000002` | `EXEC sp_rename 'sitelogs', 'sitelogs_legacy'` then `nada56-fix-sitelogs-schema-sqlsrv.sql` if `sitelogs` missing | Rename/create via migration preferred |
| `20260701000003` | — | Dctypes/codelists seed, `surveys.abstract`, `var_keywords`, surveys full-text rebuild |
| `20260701000004` | `nada56-filestore-sqlsrv.sql`, `nada56-codelists-dsd-sqlsrv.sql` | — |
| `20260701000005` | `analytics-schema-sqlsrv.sql` (fixed `idx_dedup`), then re-run web for legacy totals | Legacy analytics totals backfill |
| `20260701000006` | `nada56-surveys-ts-fields-sqlsrv.sql`, `nada56-timeseries-value-counts-sqlsrv.sql`, `nada56-timeseries-db-links-sqlsrv.sql` | `ts_databases` → `surveys` cutover |
| `20260701000007` | — | `repositories_acl` create + copy + delete legacy rows |
| `20260701000008` | — | Configuration rows + study admin metadata tables |
| `20260701000009` | `nada56-citation-indexes-sqlsrv.sql`, `nada56-search-indexes-sqlsrv.sql` | — |
| `20260701000010` | Variables NVARCHAR (see below) | — |
| `20260707120001` | Fix `idx_dedup` if oversized (see Analytics) | Add `page_url`/`section` columns |
| `20260909120001` | — | Display templates schema + `sync_shipped_cores()` |
| `20260909120002` | — | `search_index_queue` / `search_index_state` + filter indexes |
| `20260909120003` | `schema.dd.sqlsrv.sql` (if `dd_*` missing), `nada56-datadeposit-alter-sqlsrv.sql` | `dd_projects.data_type` backfill |
| `20260909120004` | — | `site_user_register` configuration row |

**Target watermark:** `20260909120004`

---

## After manual SQL

1. Re-run the **same** migration version in the web UI (idempotent skips).
2. Or **Mark as applied** only after verifying objects exist — never skip PHP-only steps.

---

## Known issues and fixes

### `sp_rename` warning 15477 (sitelogs)

SQL Server returns a caution message; ODBC may report failure even when rename succeeded.

```sql
SELECT name FROM sys.tables WHERE name IN ('sitelogs', 'sitelogs_legacy');
```

If `sitelogs_legacy` exists, re-run `20260701000002` or run `nada56-fix-sitelogs-schema-sqlsrv.sql`.

### Analytics `idx_dedup` (error 1945 / key too long)

Old installs may have an oversized index including `page_url` in the key. Drop and recreate:

```sql
IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_dedup' AND object_id = OBJECT_ID('analytics_pageview_events'))
    DROP INDEX [idx_dedup] ON [analytics_pageview_events];

CREATE NONCLUSTERED INDEX [idx_dedup]
ON [analytics_pageview_events] ([study_id] ASC, [session_id] ASC, [section] ASC, [ts] ASC)
INCLUDE ([page_url]);
```

If `analytics-schema-sqlsrv.sql` failed partway, run the remainder of that file in SSMS (skip statements for objects that already exist).

### Variables NVARCHAR (`20260701000010`)

Before altering `vid`, drop the unique index that depends on it:

```sql
IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_variables' AND object_id = OBJECT_ID('variables'))
    DROP INDEX [IX_variables] ON [variables];

-- Drop full-text if present
IF EXISTS (SELECT 1 FROM sys.fulltext_indexes fi JOIN sys.tables t ON fi.object_id = t.object_id WHERE t.name = 'variables')
    DROP FULLTEXT INDEX ON variables;

ALTER TABLE variables ALTER COLUMN fid nvarchar(45) NULL;
ALTER TABLE variables ALTER COLUMN vid nvarchar(45) NULL;
ALTER TABLE variables ALTER COLUMN name nvarchar(100) NULL;
ALTER TABLE variables ALTER COLUMN labl nvarchar(255) NULL;
ALTER TABLE variables ALTER COLUMN qstn nvarchar(max) NULL;
ALTER TABLE variables ALTER COLUMN catgry nvarchar(max) NULL;
ALTER TABLE variables ALTER COLUMN metadata nvarchar(max) NULL;
-- if column exists:
ALTER TABLE variables ALTER COLUMN keywords nvarchar(max) NULL;

CREATE UNIQUE NONCLUSTERED INDEX [IX_variables] ON [variables] ([vid] ASC, [sid] ASC);

CREATE FULLTEXT INDEX ON variables
(catgry Language 1033, labl Language 1033, name Language 1033, qstn Language 1033)
KEY INDEX pk_idx_variables;
```

Run one `ALTER` at a time on large catalogs. Re-run `20260701000010` when done.

### Login blocked before migrations

Run `nada55-add-authtype-users-sqlsrv.sql` so 5.7 can query `users.authtype`.

---

## SQL file index (`install/`)

| File | Purpose |
|------|---------|
| `nada55-upgrade-sqlsrv.sql` | Resources table columns |
| `nada55-add-authtype-users-sqlsrv.sql` | OAuth columns on `users` |
| `nada56-api-keys-security-sqlsrv.sql` | API keys security columns |
| `nada56-filestore-sqlsrv.sql` | `filestore` table |
| `nada56-codelists-dsd-sqlsrv.sql` | Codelists SDMX + data structures |
| `analytics-schema-sqlsrv.sql` | Analytics tables |
| `nada56-surveys-ts-fields-sqlsrv.sql` | Timeseries columns on `surveys` |
| `nada56-timeseries-value-counts-sqlsrv.sql` | `timeseries_value_counts` |
| `nada56-timeseries-db-links-sqlsrv.sql` | `timeseries_db_links` |
| `nada56-citation-indexes-sqlsrv.sql` | Citation indexes |
| `nada56-search-indexes-sqlsrv.sql` | Catalog search indexes |
| `nada56-datadeposit-alter-sqlsrv.sql` | Data deposit v2 columns |
| `nada56-fix-sitelogs-schema-sqlsrv.sql` | Repair missing `sitelogs` |
| `schema.dd.sqlsrv.sql` | Full data deposit schema |

| `nada56-upgrade-sqlsrv.sql` | **Full SQL bundle** (topic files in migration order; PHP-only steps still need web UI) |
| `nada56-variables-unicode-sqlsrv.sql` | Variables NVARCHAR conversion (also in bundle) |
| `nada56-analytics-pageview-tracking-sqlsrv.sql` | Pageview columns + fixed `idx_dedup` (also in bundle) |
| `nada56-search-queue-sqlsrv.sql` | Search queue/state + catalog filter indexes (also in bundle) |

---

## Verify completion

```sql
SELECT version FROM migrations;  -- expect 20260909120004

SELECT c.name, ty.name
FROM sys.columns c
JOIN sys.types ty ON c.user_type_id = ty.user_type_id
JOIN sys.tables t ON c.object_id = t.object_id
WHERE t.name = 'variables' AND c.name IN ('fid','vid','labl');
-- type_name should be nvarchar for all
```

See also [NADA57_SQLSRV_UPGRADE.md](NADA57_SQLSRV_UPGRADE.md) and [NADA56_UPGRADE_README.md](NADA56_UPGRADE_README.md).
