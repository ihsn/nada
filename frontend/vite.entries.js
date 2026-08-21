import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

/** Public-facing Vite entries (catalog search, study indicator, PDF viewer). */
export const publicEntries = {
  catalog_search: path.resolve(__dirname, 'catalog-search/main.js'),
  catalog_study_indicator_data: path.resolve(
    __dirname,
    'catalog/study_indicator_data_public/main.js'
  ),
  pdf_viewer: path.resolve(__dirname, 'pdf-viewer/main.js'),
};

/** Admin Vite entries — kept out of the public chunk graph. */
export const adminEntries = {
  admin_header: path.resolve(__dirname, 'admin/header/main.js'),
  admin_dashboard: path.resolve(__dirname, 'admin/dashboard/main.js'),
  admin_catalog: path.resolve(__dirname, 'admin/catalog/main.js'),
  admin_catalog_study_overview: path.resolve(
    __dirname,
    'admin/catalog_study_overview/main.js'
  ),
  admin_catalog_study_files: path.resolve(__dirname, 'admin/catalog_study_files/main.js'),
  admin_catalog_study_resources: path.resolve(
    __dirname,
    'admin/catalog_study_resources/main.js'
  ),
  admin_catalog_study_citations: path.resolve(
    __dirname,
    'admin/catalog_study_citations/main.js'
  ),
  admin_catalog_study_notes: path.resolve(__dirname, 'admin/catalog_study_notes/main.js'),
  admin_catalog_study_metadata: path.resolve(
    __dirname,
    'admin/catalog_study_metadata/main.js'
  ),
  admin_catalog_study_related_data: path.resolve(
    __dirname,
    'admin/catalog_study_related_data/main.js'
  ),
  admin_catalog_study_analytics: path.resolve(
    __dirname,
    'admin/catalog_study_analytics/main.js'
  ),
  admin_catalog_study_sidebar: path.resolve(
    __dirname,
    'admin/catalog_study_sidebar/main.js'
  ),
  admin_catalog_study_edit_breadcrumbs: path.resolve(
    __dirname,
    'admin/catalog_study_edit_breadcrumbs/main.js'
  ),
  admin_ddi_upload: path.resolve(__dirname, 'admin/ddi_upload/main.js'),
  admin_licensed_requests: path.resolve(__dirname, 'admin/licensed_requests/main.js'),
  admin_bulk_data_access: path.resolve(__dirname, 'admin/bulk_data_access/main.js'),
  admin_codelists: path.resolve(__dirname, 'admin/codelists/main.js'),
  admin_data_structures: path.resolve(__dirname, 'admin/data_structures/main.js'),
  admin_display_templates: path.resolve(__dirname, 'admin/display_templates/main.js'),
  admin_study_timeseries_data: path.resolve(
    __dirname,
    'admin/study_timeseries_data/main.js'
  ),
  admin_collections: path.resolve(__dirname, 'admin/collections/main.js'),
  admin_site_configurations: path.resolve(
    __dirname,
    'admin/site_configurations/main.js'
  ),
  admin_ui_kit: path.resolve(__dirname, 'admin/ui_kit/main.js'),
  admin_tables: path.resolve(__dirname, 'admin/tables/main.js'),
  admin_facets: path.resolve(__dirname, 'admin/facets/main.js'),
  admin_menu: path.resolve(__dirname, 'admin/menu/main.js'),
};

export const allEntries = {
  ...adminEntries,
  ...publicEntries,
};
