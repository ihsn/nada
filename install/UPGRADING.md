# Upgrading NADA

This guide applies to every release that uses database migrations. The steps are the same for each release, so you do not need a separate guide per version.

For older releases that predate database migrations, and for the 4.x → 5.x migration, see the [upgrade guides in the NADA documentation](https://ihsn.github.io/nada-documentation/installation-guide/upgrade/).

## How database upgrades work

Database changes are applied by migrations in `application/migrations/`. A migration is applied once and the database records the latest version applied.

- Migrations run in order, so a database from any earlier release is brought up to date by one `migrate latest`. You never need to upgrade through each intermediate release.
- Every step is **idempotent**: if a migration fails part way, fix the cause and run it again. Steps that are already applied are skipped. After a successful run, running `migrate latest` again changes nothing.
- Migrations are **one-way**. There is no rollback; restore the backup if you need to go back.
- Long-running steps (index builds, table rebuilds) are separate migrations, so a web request is less likely to time out.

## Before you start

- [ ] **Back up your database.**
- [ ] Back up your configuration files (`application/config/database.php` and any other local settings), and the `datafiles/` folder.
- [ ] Test on a staging copy first, if you can.

## Step 1: Deploy the new code

Replace the application files with the new release, and keep your configuration files and data folders.

## Step 2: Run the migrations (required)

**Do this before logging in or using the site.** The new code expects the upgraded database, and login and catalog pages fail until the migrations finish.

```bash
cd /path/to/nada

# Show the current version
php index.php cli migrate current

# List the migrations
php index.php cli migrate list_migrations

# Apply all pending migrations
php index.php cli migrate latest
```

Migrations must be enabled in `application/config/migration.php` (`migration_enabled = TRUE`).

## Step 3: Verify

1. Run `php index.php cli migrate current`. The version should equal the highest-numbered file in `application/migrations/`.
2. Log in as the site administrator and open **Site administration → Database migrations**. It should show the same version.
3. Smoke-test:
   - Login (`/index.php/auth/password`)
   - The catalog (`/index.php/catalog`)
   - A study page (`/index.php/catalog/{id}`)

## Using the web interface instead of the command line

The command line is the recommended way. The web interface is not suitable for the first upgrade from an older schema, because login can fail before the new columns exist.

If the command line is not available, open **Site administration → Database migrations** (`/index.php/admin/database_migration`) and run the versions one at a time. The **Run** button is only active on the next pending version. Do not use **Migrate to latest**, which runs everything in a single request.

For SQL Server on IIS, `set_time_limit(0)` does not raise the IIS / FastCGI request limit. See the [SQL Server upgrade notes](NADA57_SQLSRV_UPGRADE.md) and the [SQL Server manual runbook](NADA57_SQLSRV_MANUAL_RUNBOOK.md). SQL Server administrators can also run [nada56-upgrade-sqlsrv.sql](nada56-upgrade-sqlsrv.sql) in SSMS and then finish the PHP-only steps in the web interface.

## Fresh installs

New installations use `install/schema.mysql.sql` (or `schema.sqlsrv.sql`) plus `install/schema.dd.{mysql,sqlsrv}.sql`. The schema files already include all migrations up to the release, and the installer records the latest version. You do not need to run `migrate latest` after a fresh install.

## Troubleshooting

| Issue | Likely cause | Action |
|-------|--------------|--------|
| Login 500 / `row() on false` | Migrations not run | Run `php index.php cli migrate latest` |
| Catalog division by zero | Search query failed (missing columns) | Run the migrations |
| A migration fails part way | Partial apply | Fix the error and run `latest` again |
| `Both sitelogs and sitelogs_legacy exist` | Manual database conflict | Resolve the tables before continuing |
| "Duplicate column" in an SQL step | Step already applied | Should be skipped automatically; otherwise check the `MY_Migration` log |
| `sp_rename`, `idx_dedup` or `IX_variables` errors (SQL Server) | ODBC warnings or index key limits | See the [SQL Server manual runbook](NADA57_SQLSRV_MANUAL_RUNBOOK.md) |

## Further reading

### Data deposit

`migrate latest` installs the `dd_*` tables when they are missing. The feature stays off until you set `enable_datadeposit` to `true` in `application/config/datadeposit.php`. Existing deposit projects stay on the original schema until you import them; see [DATADEPOSIT_MIGRATION.md](DATADEPOSIT_MIGRATION.md).

### Other notes

- [Resources table upgrade details](NADA55_UPGRADE_README.md)
- [SQL Server upgrade notes](NADA57_SQLSRV_UPGRADE.md) and [manual runbook](NADA57_SQLSRV_MANUAL_RUNBOOK.md)
- Release-specific changes, if any, are in the release notes.
