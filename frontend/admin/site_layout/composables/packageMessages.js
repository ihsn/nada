/** Messages for api/admin/site_layout codes: code => [lang key, English fallback] */
export const PACKAGE_MESSAGES = {
  NOTHING_TO_EXPORT: ['sl_err_nothing_to_export', 'There is nothing to export: the chosen parts have no saved settings.'],
  NOTHING_SELECTED: ['sl_err_nothing_selected', 'Choose at least one part to import.'],
  NOTHING_IMPORTED: ['sl_err_nothing_imported', 'Nothing was imported. See the notes below.'],
  NOT_A_PACKAGE: ['sl_err_not_a_package', 'This file is not a Site layout package exported from NADA.'],
  PACKAGE_NEWER: ['sl_err_package_newer', 'This package was exported from a newer NADA version. Upgrade NADA, then try again.'],
  FILE_TOO_LARGE: ['sl_err_file_too_large', 'The file is too large (maximum 20 MB).'],
  NO_FILE: ['sl_err_no_file', 'Choose a .zip file exported from Site layout.'],
  UPLOAD_FAILED: ['sl_err_upload_failed', 'The file could not be uploaded. Try again.'],
  ZIP_NOT_AVAILABLE: ['sl_err_zip', 'The server cannot read or write .zip files (the PHP zip extension is missing).'],
  EXPORT_FAILED: ['sl_err_export_failed', 'The package could not be created. Try again.'],
  RUN_MIGRATIONS: ['sl_err_migrate', 'The site layout table is missing. Run the database migrations, then try again.'],
  // warnings
  THEME_DIFFERS: ['sl_warn_theme', 'Exported from the theme “%s”. This site uses another theme, so the layout may look different.'],
  VERSION_DIFFERS: ['sl_warn_version', 'Exported from NADA %s. Check the preview before you publish.'],
  IMAGE_MISSING: ['sl_warn_image_missing', 'Image not in the package, left out: %s'],
  IMAGE_SKIPPED: ['sl_warn_image_skipped', 'Image not allowed, left out: %s'],
  PART_TOO_LONG: ['sl_warn_too_long', 'Not imported: the %s is longer than this site allows.'],
  NEWER_VERSION: ['sl_warn_newer', 'Saved by a newer NADA version. Options this site does not have were left out.'],
  SETTINGS_ADJUSTED: ['sl_warn_adjusted', 'Some settings were not valid and were set to their defaults: %s'],
  HTML_CLEANED: ['sl_warn_html_cleaned', 'Some HTML was not allowed and was removed.'],
  CSS_CLEANED: ['sl_warn_css_cleaned', 'Some CSS was not allowed and was removed.'],
};

export function packageMessage(t, code, detail = '') {
  const entry = PACKAGE_MESSAGES[code];
  if (!entry) return code || t('sl_err_generic', 'Something went wrong.');
  return t(entry[0], entry[1], detail);
}
