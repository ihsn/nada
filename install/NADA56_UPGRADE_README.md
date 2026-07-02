# NADA Upgrade Guide (5.5 → current release)

## Overview

Database upgrades are delivered as **8 consolidated migrations** (down from 35 incremental steps). Each migration bundles related schema and data changes. Every step is **idempotent** — safe to re-run if a migration fails partway through or if you run `migrate latest` more than once.

| Version | Migration | Contents |
|---------|-----------|----------|
| `20260701000001` | Platform baseline | Resources table, authtype columns, API key security |
| `20260701000002` | Sitelogs cutover | Rename legacy sitelogs, create new table, repair if missing |
| `20260701000003` | Catalog & codelists foundation | Dctypes, codelists schema, citation + search indexes, abstract/var_keywords |
| `20260701000004` | Codelists & DSD versioning | SDMX identity, data structures, PID/versioning |
| `20260701000005` | Analytics | Analytics schema, legacy totals backfill, dedup index |
| `20260701000006` | Timeseries platform | Survey TS columns, ts_databases cutover, db links |
| `20260701000007` | Repositories ACL | ACL table, copy legacy permissions, remove old rows |
| `20260701000008` | Site configuration | Default site settings, study admin metadata |

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
2. Open **Site administration → Database migrations** — should show version `20260701000008`
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

---

## Fresh installs

New installations should use the current `install/schema.mysql.sql` (or SQL Server equivalent). The schema already includes all changes from these migrations. You do not need to run migrations unless you installed from an older schema file.

---

## Troubleshooting

| Issue | Likely cause | Action |
|-------|--------------|--------|
| Login 500 / `row() on false` | Migrations not run | Run `php index.php cli migrate latest` |
| Catalog division by zero | Search query failed (missing columns) | Run migrations |
| Migration fails mid-bundle | Partial apply | Fix error, re-run `latest` (steps are idempotent) |
| `Both sitelogs and sitelogs_legacy exist` | Manual DB conflict | Resolve tables before continuing |
| SQL file "Duplicate column" | Step already applied | Should auto-skip; if not, check `MY_Migration` logs |

---

## Rollback

Migrations are **one-way**. To revert, restore from the database backup taken before upgrade.

---

## Support

See also [NADA55 resource upgrade details](NADA55_UPGRADE_README.md) for resources-table specifics.
