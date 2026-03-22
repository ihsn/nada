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
          admin_dashboard: path.resolve(__dirname, 'admin/dashboard/main.js'),
          admin_catalog: path.resolve(__dirname, 'admin/catalog/main.js'),
          admin_codelists: path.resolve(__dirname, 'admin/codelists/main.js'),
          admin_collections: path.resolve(__dirname, 'admin/collections/main.js'),
          admin_dctypes: path.resolve(__dirname, 'admin/dctypes/main.js'),
          admin_test: path.resolve(__dirname, 'admin/test/main.js'),
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
