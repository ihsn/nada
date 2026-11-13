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

## Search settings and NADA-AI

### Setting names (all sites)

The migrations rename the search settings, so there is nothing to change by hand. A value already saved under a new name is kept.

| Old setting | New setting |
|---|---|
| `search_provider` | `search_engine`: `database`, `solr` or `nada_ai` (`semantic` becomes `nada_ai`; `opensearch` becomes `database`) |
| `semantic_search_url` | `nada_ai_url` |
| `semantic_search_api_key`, `semantic_search_admin_api_key` | `nada_ai_api_key` (NADA now uses one key, NADA-AI's admin key) |
| `semantic_search_debug` | `nada_ai_debug` |
| `semantic_search_engine`, `citation_search_provider` | Removed |
| None | `search_index_engine` (new): the engine whose index NADA keeps in sync, set from `search_engine` |

`application/config/semantic_search.php` is replaced by `application/config/nada_ai.php`. Its old settings are no longer read, and the old file can be deleted.

### Deploying with NADA-AI

Skip this part if the site does not use NADA-AI. Otherwise, follow this order, keeping steps 1 to 4 close together:

1. **Update NADA-AI first.** NADA needs NADA-AI's new search fields and filters. Until NADA is updated too, variable searches from the old NADA past row 1,000 fail; to avoid that during a longer gap, set `NADA_VARIABLES_MAX_OFFSET=10000` on NADA-AI until NADA is updated.
2. **Add the filter fields to the study index.** On the NADA-AI server:

   ```bash
   curl -X POST -H "X-NADA-Admin-Key: YOUR_NADA_AI_ADMIN_KEY" http://localhost:8020/admin/filters/ensure-indexes
   ```

   Adjust the address to your deployment.
3. **Rebuild the variable index.** Writes to the variable index fail on the old mapping until this is done, and variable search is incomplete while it runs. It takes about 1.5 to 2 hours at 10 million variables.

   ```bash
   curl -X POST -H "X-NADA-Admin-Key: YOUR_NADA_AI_ADMIN_KEY" "http://localhost:8020/admin/index/variables/recreate?confirm=true"
   ```

4. **Update NADA and run the migrations,** as in steps 1 and 2 at the top of this guide.
5. **Check the settings.** In **Site configurations → Search**, confirm the search engine, the NADA-AI URL and the API key, which must be NADA-AI's admin key. Also check the [key NADA-AI uses for NADA](#nada-ai-api-key).
6. **Send every study to NADA-AI again.** This fills the new filter and sidebar count fields. On the **Search index** admin page, open **Indexing**, choose **Force re-index…** and run it for each study type. Search keeps working while it runs, and you can follow it under **Activity**.

## robots.txt (recommended)

Catalog search pages past the numbered pages are reached with a cursor (URLs ending in `…&cursor=…`), an endless chain of URLs crawlers should not follow. NADA does not install a `robots.txt` (a site may already have its own), so:

- If the web root has no `robots.txt`, copy `robots.txt.example` there as `robots.txt`.
- If it already has one, add these lines to it:

  ```
  User-agent: *
  Disallow: /*cursor=
  ```

`robots.txt` is only read at the root of the host (`https://your-host/robots.txt`); when NADA is installed in a subfolder, the file goes in the host's root folder, not NADA's. This applies to fresh installs too.

## API keys created before NADA 5.6

Keys from earlier versions that have not been converted are rejected by default. Users with such keys need to create a new key.

To accept them temporarily, set `$config['api_key_enable_legacy_support'] = TRUE;` in `application/config/rest.php`. Each old key is then converted the first time it is used, and expires one year later.

## NADA-AI API key

NADA-AI reads the catalog and the change queue with an API key from NADA. The user that owns the key must be a full administrator, or have a role with both the **view** and **edit** privileges of the **Search index** permission. Without them, indexing stops with access denied.

The same permission lets other users manage the **Search index** admin page without being full administrators.

## Site and API logs permission

The **Site logs**, **API logs** and **Log cleanup & archiving** admin pages now use their own **Site and API logs** permission instead of the **view** privilege of the users permission. Full administrators are not affected. For other roles that need these pages, grant **view** to browse the logs, and **cleanup** to archive old entries to CSV and delete them from the database.

The log APIs moved from `api/db_logs` and `api/api_logs` to `api/admin/db_logs` and `api/admin/api_logs`. The old URLs no longer work, so update any scripts that call them.

## Filestore API

The filestore API moved from `api/filestore` to `api/admin/filestore`. The old URL no longer works, so update any scripts that call it. Public links to stored files (`files/public/...`) are not affected.

Each filestore API call now checks its own privilege of the **Filestore** permission: **view** to list files, **download**, **upload**, and **delete**. Full administrators are not affected. The image picker in the menu page editor uses this API, so roles that edit menu pages also need **view** and **upload**.

## Indicator data access

Indicators (`timeseries`) accept only the **Open access**, **Direct access** and **Data not available** data access types. Creating, importing or updating an indicator with any other type now fails with `INDICATOR_ACCESS_POLICY_NOT_ALLOWED` instead of being saved as open access. New indicators without a data access type are set to open access.

**Data not available** publishes the indicator's metadata only. The Chart, Table and Data API tabs are hidden, data files are not listed under Downloads, and the timeseries API returns 403 (`DATA-NOT-AVAILABLE`) for `data`, `count`, `chart`, `table-export` and `filter-options`. The Data structure tab, `schema`, `export`, `export-xml` and `notes` stay available.

Existing indicators keep their stored data access type. The study page in the admin shows a warning for any indicator that uses a type that is no longer allowed.

## Citations are now stored as CSL-JSON

Each citation is one CSL-JSON document (`citations.metadata`), which is the standard used by Zotero, Crossref and most reference managers. Two migrations move existing citations over: `20261111120001_citations_csl` converts every citation, and `20261112120001_citations_drop_legacy` removes the old columns. Both run with `migrate latest`.

**Before you run the migrations:**

- **Back up the database.** The second migration cannot be undone.
- **First migration copies the old tables first** (before it alters anything) into `citations_legacy_backup` and `citation_authors_legacy_backup`, then converts in place. The live `citation_authors` table stays until the second migration so the backfill can read it.
- **Second migration** checks that every citation has a complete document and stops with a message if any row was only partly converted. Nothing is dropped in that case; fix the citation and run it again. It then removes the old columns and the live `citation_authors` table. The `*_legacy_backup` copies stay for you to drop when satisfied:

  ```sql
  DROP TABLE citations_legacy_backup;
  DROP TABLE citation_authors_legacy_backup;
  ```
- **SQL Server:** the column types change to `nvarchar` and the full-text index is rebuilt, which can take a few minutes on a large table.

**After the migrations:**

- **Rebuild the citation index** if you use Solr or NADA-AI. Citation types are now CSL types (for example `article-journal` instead of `journal`), and internal notes are no longer sent to the index. Until the index is rebuilt, a search that combines keywords with a citation type finds nothing.
- **Citations that were published or unpublished are unchanged.** Citations created through the API without a `published` value are now saved as drafts; before they were published.

**What changes for API users** (`api/citations`):

- **Read-only.** `POST` and `DELETE` on `api/citations` are removed. Create, update, publish, attach studies, and delete use `api/admin/citations` (session or API key).
- Existing read fields are still returned (`ctype` keeps the old type names, `authors` is still a list of first name, last name and initial).
- Each citation also has `metadata` (the CSL document) and `csl_type`.
- The `ctype` filter accepts either the old names or the CSL names.
- Only published citations are returned, for every caller (including admins). `notes`, `flag`, `owner`, `url_status` and audit usernames are never returned.

The citations admin pages (Citations menu) are a new interface. Its address, `admin/citations`, is unchanged; the study page links still work. The admin API is `api/admin/citations` and accepts a session or an API key.

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
