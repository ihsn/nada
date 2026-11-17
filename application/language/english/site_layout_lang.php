<?php
/* Admin > Settings > Site layout (frontend/admin/site_layout). English only; other languages fall back to these texts. */

$lang['site_layout']="Site layout";
$lang['sl_intro']="Header, footer, and optional site CSS.";
$lang['sl_tab_header']="Header";
$lang['sl_tab_footer']="Footer";
$lang['sl_tab_styles']="Styles";

// Export and import
$lang['sl_export']="Export";
$lang['sl_import']="Import";
$lang['sl_cancel']="Cancel";
$lang['sl_close']="Close";
$lang['sl_export_title']="Export the site layout";
$lang['sl_export_help']="Downloads a .zip file with the settings and header images. Import it on another NADA site under Settings > Site layout. Menu items, the site title and languages are not included: they come from the site that imports it.";
$lang['sl_parts']="Include";
$lang['sl_export_state']="Version";
$lang['sl_state_published']="Published";
$lang['sl_state_draft']="Draft (the published version where there is no draft)";
$lang['sl_export_download']="Download";
$lang['sl_exported']="The site layout was exported.";
$lang['sl_import_title']="Import a site layout";
$lang['sl_import_help']="Choose a .zip file exported from Site layout. The parts you import become drafts: check them in the preview, then publish. Nothing changes for visitors until you publish.";
$lang['sl_import_file']="Package (.zip)";
$lang['sl_import_from']="Exported %s · NADA %s · theme %s";
$lang['sl_import_parts']="Import";
$lang['sl_import_part_draft']="exported from a draft";
$lang['sl_import_replaces_draft']="replaces your current draft";
$lang['sl_import_not_included']="Menu items, the site title and languages are not in the package; this site’s own are used.";
$lang['sl_import_button']="Import as drafts";
$lang['sl_imported']="Imported as drafts: %s. Check the preview on each tab, then publish.";
$lang['sl_import_notes']="Notes";
$lang['sl_err_generic']="Something went wrong.";
$lang['sl_err_nothing_to_export']="There is nothing to export: the chosen parts have no saved settings.";
$lang['sl_err_nothing_selected']="Choose at least one part to import.";
$lang['sl_err_nothing_imported']="Nothing was imported. See the notes below.";
$lang['sl_err_not_a_package']="This file is not a Site layout package exported from NADA.";
$lang['sl_err_package_newer']="This package was exported from a newer NADA version. Upgrade NADA, then try again.";
$lang['sl_err_file_too_large']="The file is too large (maximum 20 MB).";
$lang['sl_err_no_file']="Choose a .zip file exported from Site layout.";
$lang['sl_err_upload_failed']="The file could not be uploaded. Try again.";
$lang['sl_err_zip']="The server cannot read or write .zip files (the PHP zip extension is missing).";
$lang['sl_err_export_failed']="The package could not be created. Try again.";
$lang['sl_err_migrate']="The site layout table is missing. Run the database migrations, then try again.";
$lang['sl_warn_theme']="Exported from the theme “%s”. This site uses another theme, so the layout may look different.";
$lang['sl_warn_version']="Exported from NADA %s. Check the preview before you publish.";
$lang['sl_warn_image_missing']="Image not in the package, left out: %s";
$lang['sl_warn_image_skipped']="Image not allowed, left out: %s";
$lang['sl_warn_too_long']="Not imported: the %s is longer than this site allows.";
$lang['sl_warn_newer']="Saved by a newer NADA version. Options this site does not have were left out.";
$lang['sl_warn_adjusted']="Some settings were not valid and were set to their defaults: %s";
$lang['sl_warn_html_cleaned']="Some HTML was not allowed and was removed.";
$lang['sl_warn_css_cleaned']="Some CSS was not allowed and was removed.";
