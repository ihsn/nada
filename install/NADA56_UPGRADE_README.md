# NADA Upgrade Guide (5.5 → current release)

## Overview

Database upgrades are delivered as **7 consolidated migrations**. Long-running steps (index builds, table rebuilds, the SQL Server `variables` conversion) are separate files; the small steps share one file. Every step is **idempotent** — safe to re-run if a migration fails partway through or if you run `migrate latest` more than once.

| Version | Migration | Contents | Speed |
|---------|-----------|----------|-------|
| `20261030120001` | Platform core | authtype and API-key columns, sitelogs cutover, analytics tables, codelists and data structures, repositories ACL, site configuration, data deposit, display templates, activation codes | Light |
| `20261030120002` | Core table alterations | `resources` upgrade, `surveys` abstract / `var_keywords` / full-text rebuild, timeseries columns, `data_structure_id` foreign key, unique `idno` | Long |
| `20261030120003` | Search and citation indexes | Citation, catalog search and filter indexes, search index queue | Long |
| `20261030120004` | Variables Unicode (SQL Server) | `variables` text columns to NVARCHAR, recreate full-text index. No-op on MySQL | Long |
| `20261030120005` | Pageview tracking index | `analytics_pageview_events` dedup index (skipped when already correct) | Long on large event tables |
| `20261030120006` | Timeseries data cutover | `ts_databases` rows become surveys, keyword backfill | Data |
| `20261030120007` | Post-upgrade data fixes | Minimum password length 5 → 8, remove the legacy `user` role | Light |

Databases from any earlier release (5.5, 5.6, 5.7.x) run all seven once and end up identical to a fresh install. Back up the database first.

Historical step implementations live under `application/migrations/archived/` (not executed directly by CodeIgniter).

---

## Before you start

- [ ] **Back up your database** (mandatory — migrations are one-way)
- [ ] Deploy the new application code
- [ ] Test on staging first if possible

---

## Step 1: Run migrations via CLI (required)

**Do this before logging in or using the site.** New code expects an upgraded schema; login and catalog pages will fail until migrations complete.

```bash
cd /path/to/nada-release

# Check current version
php index.php cli migrate current

# List pending migrations
php index.php cli migrate list_migrations

# Apply all pending migrations
php index.php cli migrate latest
```

Migrations must be enabled in `application/config/migration.php` (`migration_enabled = TRUE`).

**Re-running is safe:** If a migration fails halfway, fix the underlying issue and run `latest` again. Already-applied steps inside each bundle are skipped. After success, running `latest` again is a no-op.

---

## Step 2: Verify

1. Log in as site administrator
2. Open **Site administration → Database migrations** — should show version `20261030120007`
3. Smoke-test:
   - Login (`/index.php/auth/password`)
   - Catalog (`/index.php/catalog`)
   - Open a study page (`/index.php/catalog/{id}`)

---

## Step 3: Web UI (optional)

After CLI migration succeeds, admins can use the web interface for future migrations:

```
http://yoursite.com/index.php/admin/database_migration
```

The web UI is **not** suitable for the initial upgrade from an older schema because login may fail before authtype and other columns exist.

If CLI is unavailable, run versions one at a time from that page (the **Run** button is only active on the next pending version). `20261030120002`–`20261030120005` are split out so index builds and the SQL Server `variables` NVARCHAR conversion do not share one request. SQL Server admins can also run [nada56-upgrade-sqlsrv.sql](nada56-upgrade-sqlsrv.sql) in SSMS, then finish PHP-only steps via the web UI. See [NADA57_SQLSRV_UPGRADE.md](NADA57_SQLSRV_UPGRADE.md) and the [SQL Server manual runbook](NADA57_SQLSRV_MANUAL_RUNBOOK.md).

---

## Fresh installs

New installations run `install/schema.mysql.sql` (or `schema.sqlsrv.sql`) plus `install/schema.dd.{mysql,sqlsrv}.sql`. The main schema already includes the catalog migrations. You do not need to run `migrate latest` unless you installed from an older schema file. Data deposit stays off until `enable_datadeposit` is true.

---

## Troubleshooting

| Issue | Likely cause | Action |
|-------|--------------|--------|
| Login 500 / `row() on false` | Migrations not run | Run `php index.php cli migrate latest` |
| Catalog division by zero | Search query failed (missing columns) | Run migrations |
| Migration fails mid-bundle | Partial apply | Fix error, re-run `latest` (steps are idempotent) |
| `Both sitelogs and sitelogs_legacy exist` | Manual DB conflict | Resolve tables before continuing |
| SQL file "Duplicate column" | Step already applied | Should auto-skip; if not, check `MY_Migration` logs |
| `sp_rename` / `idx_dedup` / `IX_variables` errors (SQL Server) | ODBC warnings or index key limits | See [NADA57_SQLSRV_MANUAL_RUNBOOK.md](NADA57_SQLSRV_MANUAL_RUNBOOK.md) |

---

## Rollback

Migrations are **one-way**. To revert, restore from the database backup taken before upgrade.

---

## Data Deposit

`migrate latest` installs the `dd_*` tables when they are missing (from `install/schema.dd.mysql.sql` or `install/schema.dd.sqlsrv.sql`). The feature stays off until you set `enable_datadeposit` to `true` in `application/config/datadeposit.php`. The admin “Data deposit” menu is hidden while it is off. Site settings → Data deposit shows that status (not a toggle), the max deposit file size in MB, and the allowed file types (read-only).

Fresh installs also run that SQL file from the web installer.

Existing deposit projects stay on schema version 1 (`dd_study` / `dd_citations`) until you import them. The Vue UI reads JSON on `dd_projects`. See [DATADEPOSIT_MIGRATION.md](DATADEPOSIT_MIGRATION.md).

---

## Support

See also [NADA55 resource upgrade details](NADA55_UPGRADE_README.md) for resources-table specifics.
