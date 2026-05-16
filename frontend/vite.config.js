import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import vuetify, { transformAssetUrls } from 'vite-plugin-vuetify'
import path from 'path'

// https://vite.dev/config/
// Use relative base so the build is portable. PHP passes full asset URLs when loading
// (base_url() in script/link tags), so the correct base is determined at runtime.
export default defineConfig(({ command }) => {
  const isDev = command === 'serve';
  return {
    plugins: [
      vue({
        template: { transformAssetUrls },
      }),
      vuetify({ autoImport: true }),
      vueDevTools(),
    ],
    resolve: {
      alias: {
        '@': path.resolve(__dirname),
      },
    },
    base: isDev ? '/' : './',
    build: {
      manifest: true,
      rollupOptions: {
        input: {
          admin_header: path.resolve(__dirname, 'admin/header/main.js'),
          admin_dashboard: path.resolve(__dirname, 'admin/dashboard/main.js'),
          admin_catalog: path.resolve(__dirname, 'admin/catalog/main.js'),
          admin_catalog_study_overview: path.resolve(__dirname, 'admin/catalog_study_overview/main.js'),
          admin_catalog_study_files: path.resolve(__dirname, 'admin/catalog_study_files/main.js'),
          admin_catalog_study_resources: path.resolve(__dirname, 'admin/catalog_study_resources/main.js'),
          admin_catalog_study_citations: path.resolve(__dirname, 'admin/catalog_study_citations/main.js'),
          admin_catalog_study_notes: path.resolve(__dirname, 'admin/catalog_study_notes/main.js'),
          admin_catalog_study_related_data: path.resolve(__dirname, 'admin/catalog_study_related_data/main.js'),
          admin_catalog_study_analytics: path.resolve(__dirname, 'admin/catalog_study_analytics/main.js'),
          admin_catalog_study_sidebar: path.resolve(__dirname, 'admin/catalog_study_sidebar/main.js'),
          admin_catalog_study_edit_breadcrumbs: path.resolve(__dirname, 'admin/catalog_study_edit_breadcrumbs/main.js'),
          admin_licensed_requests: path.resolve(__dirname, 'admin/licensed_requests/main.js'),
          admin_bulk_data_access: path.resolve(__dirname, 'admin/bulk_data_access/main.js'),
          admin_codelists: path.resolve(__dirname, 'admin/codelists/main.js'),
          admin_data_structures: path.resolve(__dirname, 'admin/data_structures/main.js'),
          admin_templates: path.resolve(__dirname, 'admin/templates/main.js'),
          admin_study_timeseries_data: path.resolve(__dirname, 'admin/study_timeseries_data/main.js'),
          catalog_study_indicator_data: path.resolve(__dirname, 'catalog/study_indicator_data_public/main.js'),
          admin_collections: path.resolve(__dirname, 'admin/collections/main.js'),
          admin_site_configurations: path.resolve(__dirname, 'admin/site_configurations/main.js'),
          admin_ui_kit: path.resolve(__dirname, 'admin/ui_kit/main.js'),
          admin_test: path.resolve(__dirname, 'admin/test/main.js'),
          catalog_search: path.resolve(__dirname, 'catalog-search/main.js'),
        },
      },
      outDir: 'dist',
    },
    server: {
      port: 5173,
      host: true,
      strictPort: false,
      // When the page is served from another origin (e.g. PHP at localhost/nada), asset URLs
      // in CSS (e.g. @mdi/font) must use this origin so fonts load from the dev server.
      origin: 'http://localhost:5173',
    },
  };
})
