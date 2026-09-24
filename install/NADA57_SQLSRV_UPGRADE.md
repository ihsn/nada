# NADA 5.7 upgrade checklist (SQL Server)

Use this checklist when upgrading an existing NADA 5.5 or 5.6 catalog on **Microsoft SQL Server** to 5.7.

Schema changes are applied by PHP migrations. Do **not** run the old `install/*-sqlsrv.sql` upgrade scripts by hand.

Related guides:

- Platform migrations (all databases): [NADA56_UPGRADE_README.md](NADA56_UPGRADE_README.md)
- Legacy data deposit projects: [DATADEPOSIT_MIGRATION.md](DATADEPOSIT_MIGRATION.md)

---

## Order of work

1. Back up the database and files
2. Deploy 5.7 (merge custom theme files — do not overwrite your theme)
3. Run `php index.php cli migrate latest` **before** anyone uses the site
4. If the catalog uses Solr: wipe the index, recreate the schema, full reindex from CLI
5. Smoke-test catalog and study pages
6. Enable data deposit only if you use it

---

## Before you start

- [ ] **Back up the database** (full backup). Migrations are one-way.
- [ ] Back up the application folder, especially `themes/<your-theme>/`, `application/config/`, `datafiles/`, and `userdata/`.
- [ ] Confirm PHP has the **sqlsrv** (or pdo_sqlsrv) extension and `application/config/database.php` uses `'dbdriver' => 'sqlsrv'`.
- [ ] Confirm SQL Server has **Full-Text Search** installed. 5.7 drops and recreates the full-text index on `variables` (Unicode / NVARCHAR). Without Full-Text Search that step fails.
- [ ] Test on a **copy** of the database first if you can.
- [ ] Note the current site theme folder (`themes/nada52` or a copy such as `themes/mycatalog`).

---

## Deploy code

- [ ] Deploy 5.7 over the existing install, or into a new folder and point the site at it.
- [ ] **Do not overwrite** your custom theme folder with `themes/nada52`. Merge the new files (see [Custom themes](#custom-themes-nada52--your-theme)).
- [ ] Keep your existing `application/config/database.php` and `email.php`. Use the new `*.sample.php` files only as a reference.
- [ ] Data deposit stays **off** until you set `enable_datadeposit` to `true` in `application/config/datadeposit.php`.
- [ ] Set `CI_ENV=production` (or the equivalent server environment) so errors are not shown in the browser.

---

## Run migrations (required, before login)

Do this from the NADA root **before** anyone uses the site. New code expects the new schema; login and catalog will fail until this finishes.

```bash
cd /path/to/nada
php index.php cli migrate current
php index.php cli migrate list_migrations
php index.php cli migrate latest
```

`application/config/migration.php` must have `migration_enabled = TRUE`.

On SQL Server this also:

- Adds catalog, analytics, timeseries, ACL, and site-config tables/columns
- `20260701000009` adds search/citation indexes (same content as `install/nada56-search-indexes-sqlsrv.sql`)
- `20260701000010` converts `variables` text columns to **NVARCHAR** (preserves Arabic and other Unicode; can take a while on large catalogs) and recreates the **full-text index** on `variables`
- Installs `dd_*` tables from `install/schema.dd.sqlsrv.sql` if they are missing

Re-run `migrate latest` if a step fails. Bundled steps are idempotent. A re-run skips indexes that already exist, skips `variables` columns that are already NVARCHAR, and does not drop a full-text index that already covers the right columns.

### Web UI (when CLI is not available)

`set_time_limit(0)` does not raise the IIS / FastCGI request limit. Use **Site administration → Database migrations** and click **Run** on the first pending version only. Repeat until the watermark is `20260701000010`.

- Do not use **Migrate to Latest**. That runs every pending migration in one request.
- `20260701000009` (indexes) and `20260701000010` (`variables` NVARCHAR) are separate because they are the long steps.
- If a request times out, run that same version again. Finished statements are skipped. The version is recorded only after the whole step succeeds.

Do **not** run `install/nada5-upgrade-sqlsrv.sql`, `nada55-upgrade-sqlsrv.sql`, or the standalone `nada56-*-sqlsrv.sql` files yourself. Those are already applied by `migrate latest`.

### After migrations

- [ ] Log in as site administrator.
- [ ] **Site administration → Database migrations** should show version `20260701000010`.
- [ ] If catalog search looks empty or errors, confirm Full-Text Search is running and the `variables` / `surveys` full-text indexes exist.

---

## Solr: wipe, recreate schema, full reindex (CLI)

Do this **after** `migrate latest` and **only if** the catalog uses Solr (`application/config/solr.php` and the catalog search driver set to Solr).

5.7 changes catalog search fields and filters (`tab_type`, drivers, variable metadata). An old Solr index will return wrong or empty results. Do **not** use the admin UI for this rebuild. Run it from the NADA root on the server.

Catalog search will be empty or incomplete until this finishes. Variable indexing can take a long time on large SQL Server catalogs.

### Before you start

- [ ] Database migrations have completed.
- [ ] Solr is running and reachable from the app server.
- [ ] `application/config/solr.php` has the correct `solr_host`, `solr_port`, and `solr_collection` (default collection is `nada`).
- [ ] Confirm the CLI can talk to Solr:

```bash
cd /path/to/nada
php index.php cli/solr/status
```

You should get JSON with `solr` and `database` counts. If you get a connection error, fix host/port/collection before continuing.

### Full rebuild

This **deletes every Solr document**, recreates the schema fields, then reindexes studies, variables, and citations. Each index command does **not** commit; you must commit after each type or the new documents stay invisible.

```bash
cd /path/to/nada

# 1. Clear the index and recreate the full schema (surveys, variables, citations)
php index.php cli/solr/setup_schema

# 2. Index studies, then commit
php index.php cli/solr/index_studies
php index.php cli/solr/commit_index

# 3. Index variables, then commit (slowest step)
php index.php cli/solr/index_variables
php index.php cli/solr/commit_index

# 4. Index citations, then commit
php index.php cli/solr/index_citations
php index.php cli/solr/commit_index

# 5. Compare Solr vs database counts
php index.php cli/solr/status
```

`setup_schema` already clears the index. You do not need a separate `clean_index` unless you only want to wipe documents and keep the existing schema.

### If a step is interrupted

Resume from the last ID printed (`start_row`). Do not run `setup_schema` again or you will wipe progress.

```bash
php index.php cli/solr/index_studies 12345
php index.php cli/solr/index_variables 67890
php index.php cli/solr/index_citations 100
php index.php cli/solr/commit_index
```

On Linux you can background the long jobs:

```bash
nohup php index.php cli/solr/index_variables > solr-variables.log 2>&1 &
```

On Windows, run the same `php index.php cli/solr/...` commands in a dedicated command window and leave it open until it finishes.

### After Solr is rebuilt

- [ ] `php index.php cli/solr/status` — Solr counts should match (or be very close to) database counts for studies, variables, and citations.
- [ ] Open `/index.php/catalog` and run a keyword search.
- [ ] Switch tabs (studies / variables) and confirm `tab_type` filtering works.
- [ ] Open a study and use variable search.

Do **not** use `synchronize_index` for this upgrade. That only heals added/deleted studies and citations. Variables still need a full `index_variables` run.

---

## Custom themes (`nada52` → your theme)

NADA loads the theme from `application/config/template.php` (`theme_name`) or a `THEME_NAME` constant. Catalog **search** now uses `themes/<your-theme>/layout_vue.php`. If that file is missing, **`/catalog` will fail**.

`header.php`, `footer.php`, and `layout.php` did **not** change in 5.7. Keep yours.

### Copy these new files into your theme (required)

Copy from `themes/nada52/` into `themes/<your-theme>/`:

| File | Why |
|------|-----|
| `layout_vue.php` | Shell for Vue catalog search |
| `head_vue.php` | Lean `<head>` (Bootstrap + Font Awesome only; no Google Fonts / classic catalog CSS) |
| `css/catalog-vue-chrome.css` | Fonts and colors for the Vue catalog shell |
| `css/fonts.css` | Google Fonts moved out of `style.css` so Vue search does not load them |

`layout_vue.php` includes your existing `header.php` and `footer.php`, so logo, nav, and footer customizations still apply on catalog search.

### Merge these if you copied them from an older `nada52`

**`head.php`** — add the `fonts.css` line **before** `style.css`:

```php
<link rel="stylesheet" href="<?php echo base_url().$bootstrap_theme ?>/css/fonts.css?v20260721">
<link rel="stylesheet" href="<?php echo base_url().$bootstrap_theme ?>/css/style.css?v20260721">
```

If you skip this and your `style.css` no longer has the `@import` font URLs, classic pages (home, study, citations) lose Inter / Roboto Condensed / IBM Plex Mono.

**`css/style.css`** — 5.7 **removed** the three Google Font `@import` lines and moved them to `fonts.css`.

- If you maintain your own `style.css`, leave your font imports as they are, **or** switch to `fonts.css` like stock `nada52`.
- Do not blindly overwrite a customized `style.css` with the stock file.

**`css/catalog-card.css`** — only a small icon-margin tweak. Copy only if you never customized this file. Classic catalog cards are not used on the new Vue search page.

### Leave these as they are (unless you want stock files)

- `header.php`, `footer.php`, `layout.php`
- `css/custom.css` (still loaded on classic pages **and** on Vue search via `head_vue.php`)
- `css/home.css`, `facets.css`, `catalog-tab.css`, `filter-action-bar.css` — still used on **classic** pages only

### What custom CSS will no longer apply

Vue catalog search **does not load** `facets.css`, `catalog-card.css`, `catalog-tab.css`, `home.css`, or `fonts.css`. Rules you wrote for `.wb-card-*`, facet widgets, or catalog tabs will not show on `/catalog`.

To restyle the new catalog:

- Put header/footer-only rules in `css/custom.css` (loaded by `head_vue.php`)
- Put catalog-search tokens (fonts, colors) in `css/catalog-vue-chrome.css`

Study pages still use the **default** layout (`layout.php` + `head.php`), so header/footer/`style.css`/`custom.css` still apply. The **body** of the study page is now a JSON display template, not the old PHP study views. Overrides of `application/views/survey_info/*` or similar will not run unless you switch that type back to “Older PHP page” under **Site configurations → Study description layout**.

### Theme decision

| Your situation | What to do |
|----------------|------------|
| Theme is stock `nada52` | Nothing. 5.7 already has the new files. |
| Copied `nada52` and only changed `header.php` / `footer.php` / `custom.css` | Copy the four new files above. Optionally add the `fonts.css` link in `head.php`. |
| Heavily edited `head.php` or `style.css` | Merge as above; do not overwrite. Copy `layout_vue.php` + `head_vue.php` + the two CSS files. |
| Restyled catalog cards / facets | Those styles will not apply to Vue search. Recreate them in `catalog-vue-chrome.css` or `custom.css`. |
| Custom PHP study-page views | Default is JSON display templates. Use Site configurations if you still need the old PHP page per type. |

---

## Data deposit (only if you use it)

1. After `migrate latest`, set `enable_datadeposit` to `true` in `application/config/datadeposit.php`.
2. Existing v1 projects: dump, then import, using `php index.php cli/datadeposit`. See [DATADEPOSIT_MIGRATION.md](DATADEPOSIT_MIGRATION.md).
3. Do not drop `dd_study`, `dd_citations`, or `dd_citation_authors` until the import is verified.

---

## Smoke test

- [ ] Login (`/index.php/auth/password`)
- [ ] Home page: fonts and header/footer look correct
- [ ] `/index.php/catalog`: search loads (proves `layout_vue.php` exists); header/footer match the rest of the site
- [ ] Keyword search and study/variable tabs work (Solr users: after the full reindex)
- [ ] Open a study (`/index.php/catalog/{id}`): layout and custom CSS still look right
- [ ] Variable search on a microdata study
- [ ] If fonts are missing on home/study but search looks fine: `head.php` is missing `fonts.css` (or your `style.css` lost the `@import`s)

---

## Rollback

Migrations are **one-way**. To revert, restore the database backup and the previous application files (including your theme).
