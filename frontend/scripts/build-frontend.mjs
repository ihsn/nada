/**
 * Two-pass Vite build so public catalog entries never share chunks with admin.
 *
 * 1) admin  → empties dist, writes .vite/manifest-admin.json
 * 2) public → appends to dist, writes .vite/manifest-public.json
 * 3) merge  → .vite/manifest.json (what PHP vite_helper reads)
 */
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const frontendRoot = path.resolve(__dirname, '..');
const distViteDir = path.join(frontendRoot, 'dist', '.vite');

function runBuild(target) {
  console.log(`\n[build-frontend] Building target: ${target}`);
  const result = spawnSync(
    process.platform === 'win32' ? 'npx.cmd' : 'npx',
    ['vite', 'build'],
    {
      cwd: frontendRoot,
      env: { ...process.env, VITE_BUILD_TARGET: target },
      stdio: 'inherit',
    }
  );
  if (result.status !== 0) {
    process.exit(result.status ?? 1);
  }
}

function readJson(filePath) {
  return JSON.parse(fs.readFileSync(filePath, 'utf8'));
}

function mergeManifests() {
  const adminPath = path.join(distViteDir, 'manifest-admin.json');
  const publicPath = path.join(distViteDir, 'manifest-public.json');
  const outPath = path.join(distViteDir, 'manifest.json');

  if (!fs.existsSync(adminPath) || !fs.existsSync(publicPath)) {
    console.error(
      '[build-frontend] Missing partial manifests. Expected:',
      adminPath,
      'and',
      publicPath
    );
    process.exit(1);
  }

  const merged = {
    ...readJson(adminPath),
    ...readJson(publicPath),
  };

  fs.mkdirSync(distViteDir, { recursive: true });
  fs.writeFileSync(outPath, JSON.stringify(merged, null, 2) + '\n');

  const adminEntries = Object.values(merged).filter((e) => e.isEntry && e.name?.startsWith('admin_')).length;
  const publicEntries = Object.values(merged).filter(
    (e) =>
      e.isEntry &&
      (e.name?.startsWith('catalog_') || e.name === 'pdf_viewer' || e.name === 'datadeposit')
  ).length;

  console.log(
    `[build-frontend] Merged manifest → ${outPath} (${Object.keys(merged).length} keys, ~${adminEntries} admin entries, ~${publicEntries} public entries)`
  );
}

runBuild('admin');
runBuild('public');
mergeManifests();
console.log('[build-frontend] Done.\n');
