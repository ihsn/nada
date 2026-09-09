import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import vuetify, { transformAssetUrls } from 'vite-plugin-vuetify'
import path from 'path'
import { fileURLToPath } from 'url'
import { adminEntries, publicEntries, allEntries } from './vite.entries.js'

const __dirname = path.dirname(fileURLToPath(import.meta.url))

/**
 * VITE_BUILD_TARGET:
 * - unset / "all"  — every entry (dev server; single-graph prod if used alone)
 * - "admin"        — admin entries only (isolated chunk graph)
 * - "public"       — public catalog / PDF entries only (isolated chunk graph)
 *
 * Production builds use scripts/build-frontend.mjs (admin then public, merge manifests)
 * so the public catalog never shares vendor chunks with admin.
 */
function resolveEntries(target) {
  if (target === 'admin') return adminEntries
  if (target === 'public') return publicEntries
  return allEntries
}

function resolveManifest(target) {
  if (target === 'admin') return '.vite/manifest-admin.json'
  if (target === 'public') return '.vite/manifest-public.json'
  return true
}

// https://vite.dev/config/
// Use relative base so the build is portable. PHP passes full asset URLs when loading
// (base_url() in script/link tags), so the correct base is determined at runtime.
export default defineConfig(({ command }) => {
  const isDev = command === 'serve'
  const buildTarget = (process.env.VITE_BUILD_TARGET || 'all').toLowerCase()
  const entries = resolveEntries(buildTarget)
  const emptyOutDir = buildTarget === 'admin' || buildTarget === 'all'

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
      manifest: resolveManifest(buildTarget),
      emptyOutDir,
      rollupOptions: {
        input: entries,
        output: buildTarget === 'admin' || buildTarget === 'public'
          ? {
              // Separate folders so public never reuses admin chunk filenames.
              entryFileNames: `assets/${buildTarget}/[name]-[hash].js`,
              chunkFileNames: `assets/${buildTarget}/[name]-[hash].js`,
              assetFileNames: `assets/${buildTarget}/[name]-[hash][extname]`,
            }
          : undefined,
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
