# Data Deposit — migrate legacy projects after upgrade

Starting with NADA 5.7, the data deposit API and UI have changed and need to be upgraded. Follow this guide to run the migration.

---

## What changes

| | Legacy (v1) | After import (v2) |
|---|---|---|
| Study description | `dd_study` columns (grids as JSON) | `dd_projects.metadata` (Custom JSON + `additional` + `citations[]`) |
| Access / submit notes | `dd_projects` columns | `dd_projects.submission` JSON |
| Catalog study id | `dd_study.ident_ddp_id` | `metadata.additional.catalog_study_id` |
| Files, collaborators, history | Unchanged | Unchanged |
| Flag | `schema_version` **1** (migration default) | `schema_version` **2** |

New projects created after the upgrade are already v2. The importer skips them unless you pass `force`.

**Do not** rename or drop `dd_study`, `dd_citations`, or `dd_citation_authors` until dumps verify and import is done. Keep the dump folder on backup disk.

---

## Before you start

- [ ] Database backup (mandatory)
- [ ] Deploy the new application code
- [ ] Run platform migrations: `php index.php cli/migrate latest`  
      That installs `dd_*` tables if they are missing (`install/schema.dd.mysql.sql` or `install/schema.dd.sqlsrv.sql`) and adds `dd_projects.schema_version` (default 1 on existing rows) plus `dd_projects.submission`. See [NADA56_UPGRADE_README.md](NADA56_UPGRADE_README.md).
- [ ] Turn the feature on: `enable_datadeposit` = `true` in `application/config/datadeposit.php` (default is off)
- [ ] Test on a copy of the database first if you can

List available commands:

```bash
php index.php cli/datadeposit
```

Dump files go under `{userdata_path}/datadeposit-legacy-dumps/` (`P-{id}.json` plus `manifest.json`).

`userdata_path` is set in `application/config/config.php`. The default is `userdata` (a folder next to `index.php`). You can change it to another relative path or an absolute path if you keep user files elsewhere. `php index.php cli/datadeposit` prints the resolved dump directory so you can confirm it before you run dump.

---

## Procedure

Run from the NADA root (same directory as `index.php`).

### 1. Dump

Snapshot each project from the live tables (generic legacy JSON, not IHSN metadata). File bytes are not included.

```bash
php index.php cli/datadeposit/dump          # all projects
php index.php cli/datadeposit/dump 42       # one project
```

You can dump **after** the upgrade (tables are still there) or dump on the old site and copy the dump folder onto the new one, then import.

### 2. Verify

```bash
php index.php cli/datadeposit/verify
```

This re-reads the dumps, checks checksums, and compares project ids to the live database. Fix any failures before import.

### 3. Dry-run import

```bash
php index.php cli/datadeposit/import dry
```

Prints warnings. Does not write. Already-v2 projects are skipped.

### 4. Import

```bash
php index.php cli/datadeposit/import        # all v1 projects
php index.php cli/datadeposit/import 42     # one project
php index.php cli/datadeposit/import force  # re-import even if already v2
```

Import:

- Builds DDI from the dump, parses it to Legacy JSON
- Copies extra non-DDI fields and the catalog study id onto `metadata.additional`
- Converts citations to Chicago text `{format, text}`
- Copies submit fields into `submission`
- Sets `schema_version` to 2
- Leaves files, collaborators, and history as they are

### 5. Spot-check

```bash
php index.php cli/datadeposit/show 42
```

Confirm `schema_version` is 2, study title, countries, citation count, and submission keys. Then open the same project in the depositor (`/datadeposit/study/{id}`) and in staff admin (`/admin/datadeposit/projects/{id}`).

---

## After import

- v2 projects use the Vue UI and REST APIs.
- Keep the dump directory until you are satisfied (copy it off the server).
- Folder-path repair (legacy md5 folders) is separate:  
  `php index.php cli/datadeposit/old_folder_paths`  
  `php index.php cli/datadeposit/update_folder_paths`

---

## Troubleshooting

| Issue | Likely cause | Action |
|-------|--------------|--------|
| `schema_version / submission columns missing` | Migrations not applied | `php index.php cli/migrate latest` |
| Import skips every project | They are already v2 | Expected for new projects; use `force` only to redo |
| Vue study form is empty | Project still v1, or import not run | Run dump / verify / import; do not save first |
| Verify: projects in DB but not in manifest | New project created after dump | Dump again, then import |
| Verify: checksum mismatch | Dump file edited or truncated | Dump that id again |
| Dry-run warnings | Empty or odd legacy fields | Review `show {id}` after import; edit in Vue if needed |

---

## Commands (summary)

```bash
php index.php cli/datadeposit
php index.php cli/datadeposit/dump
php index.php cli/datadeposit/dump {id}
php index.php cli/datadeposit/verify
php index.php cli/datadeposit/import dry
php index.php cli/datadeposit/import
php index.php cli/datadeposit/import {id}
php index.php cli/datadeposit/import force
php index.php cli/datadeposit/show {id}
```
