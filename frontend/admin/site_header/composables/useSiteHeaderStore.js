import { computed, reactive, ref, watch } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import { useSiteLayoutApi } from '../../site_layout/composables/useSiteLayoutApi';

/**
 * Shared state of the site header editor (one instance per page).
 *
 * `form` holds the settings being edited. Each change is rendered by the server right away for the live preview
 * (nothing saved), and saved as the draft after a pause. Publish copies the draft to the public site.
 */

const RENDER_MS = 150;
const AUTOSAVE_MS = 1200;

export const ROW_IDS = ['top', 'main', 'bottom'];
export const SLOTS = ['left', 'center', 'right'];
export const SINGLE_ITEMS = ['brand', 'menu', 'login', 'language', 'search'];

const server = reactive({
  loaded: false,
  published: null,
  draft: null,
  isDefault: true,
  forceDefault: false,
  defaults: {},
  presets: [],
  tokens: [],
  languagesEnabled: 1,
  themeLogoUrl: '',
  themeName: 'nada52',
  hasLocalHeader: false,
  themeHeaderFile: '',
  cache: { ok: true, code: '', path: '', catalog_root: '' },
  /** False when the published file (what visitors see) does not match the published settings */
  fileInSync: true,
  upload: { ok: true, code: '', path: '' },
  limits: {},
});

const form = ref(null);
/** Editor tab only (theme | builder | custom). Independent of form.source (“On the site”). */
const editorView = ref('theme');
const loadError = ref('');
const saving = ref(false);
const busy = ref('');
const dirty = ref(false);
const warnings = ref([]);
const fieldErrors = ref({});
const asGuest = ref(false);
const preview = reactive({ html: '', template: '', rendering: false, version: 0 });
const toast = reactive({ show: false, message: '', color: 'success' });

let api = null;
let i18n = null;
let saveTimer = null;
let renderTimer = null;
let renderSeq = 0;
let saveQueued = false;
/** Snapshot of the settings last loaded from or saved to the server */
let baseline = '';

/** Fields the server stores (the *_url fields are for display only) */
function payload(settings) {
  return JSON.parse(
    JSON.stringify(settings || {}, (key, value) => (key === 'logo_url' || key === 'bg_image_url' ? undefined : value))
  );
}

function snapshot(settings) {
  return JSON.stringify(payload(settings));
}

function clone(value) {
  return JSON.parse(JSON.stringify(value));
}

function setForm(settings) {
  form.value = settings ? clone(settings) : null;
  baseline = snapshot(form.value);
  dirty.value = false;
  const src = form.value?.source;
  editorView.value = src === 'builder' || src === 'custom' || src === 'theme' ? src : 'theme';
}

function syncModeFromSource() {
  if (!form.value) return;
  form.value.mode = form.value.source === 'custom' ? 'custom' : 'builder';
}

/** Settings sent to the preview renderer: current form, but rendered as the open editor tab. */
function previewPayload() {
  const src = editorView.value === 'custom' || editorView.value === 'builder' || editorView.value === 'theme'
    ? editorView.value
    : 'theme';
  return {
    ...payload(form.value),
    source: src,
    mode: src === 'custom' ? 'custom' : 'builder',
  };
}

function applyServer(data, { keepForm = false } = {}) {
  server.loaded = true;
  server.published = data.published;
  server.draft = data.draft;
  server.isDefault = !!data.is_default;
  server.forceDefault = !!data.force_default;
  server.defaults = data.defaults || {};
  server.presets = data.presets || [];
  server.tokens = data.tokens || [];
  server.languagesEnabled = Number(data.languages_enabled || 1);
  server.themeLogoUrl = data.theme_logo_url || '';
  server.themeName = data.theme_name || 'nada52';
  server.hasLocalHeader = !!data.has_local_header;
  server.themeHeaderFile = data.theme_header_file || '';
  server.cache = data.cache && typeof data.cache === 'object'
    ? {
        ok: !!data.cache.ok,
        code: data.cache.code || '',
        path: data.cache.path || '',
        catalog_root: data.cache.catalog_root || '',
      }
    : { ok: true, code: '', path: '', catalog_root: '' };
  server.fileInSync = data.file_in_sync !== false;
  server.upload = data.upload && typeof data.upload === 'object'
    ? {
        ok: !!data.upload.ok,
        code: data.upload.code || '',
        path: data.upload.path || '',
      }
    : { ok: true, code: '', path: '' };
  server.limits = data.limits || {};
  if (!keepForm) {
    const next = data.draft || data.published;
    if (next && !next.source) {
      next.source = next.mode === 'custom' ? 'custom' : 'builder';
    }
    setForm(next);
  }
}

const ERRORS = {
  VALIDATION_FAILED: ['sh_err_validation', 'Some values are not valid. Check the highlighted fields.'],
  INVALID_COLOR: ['sh_err_color', 'Use a colour like #1a2b3c.'],
  INVALID_IMAGE: ['sh_err_image', 'Upload the image again.'],
  INVALID_URL: ['sh_err_url', 'Use a full web address (https://…), mailto:, tel:, or a page on this site.'],
  IMAGE_NOT_FOUND: ['sh_err_image_missing', 'An image file is missing. Upload it again.'],
  HTML_TOO_LONG: ['sh_err_html_long', 'The HTML is too long.'],
  CSS_TOO_LONG: ['sh_err_css_long', 'The CSS is too long.'],
  NO_DRAFT: ['sh_err_no_draft', 'There are no changes to publish.'],
  FILE_TOO_LARGE: ['sh_err_file_large', 'The file is too large.'],
  FILE_TYPE_NOT_ALLOWED: ['sh_err_file_type', 'This type of file is not allowed.'],
  FILE_CONTENT_MISMATCH: ['sh_err_file_content', 'The file is not a valid image.'],
  SVG_NOT_SAFE: ['sh_err_svg', 'This SVG contains scripts or external links and cannot be used. Export it again as a plain SVG or PNG.'],
  NO_FILE: ['sh_err_no_file', 'Choose a file to upload.'],
  RUN_MIGRATIONS: [
    'sh_err_migrate',
    'The header cannot be saved because the site layout table is missing. Run the database migrations, then try again.',
  ],
  CACHE_WRITE_FAILED: [
    'sh_err_cache_write',
    'The header file under catalog_root/cache/site_header could not be written or removed, so nothing was changed. Check that the folder is writable, then try again.',
  ],
  CATALOG_ROOT_NOT_SET: [
    'sh_err_catalog_root',
    'The study files folder (catalog_root) is not set. Set it under Settings > General before publishing a builder or custom header.',
  ],
  CATALOG_ROOT_MISSING: [
    'sh_err_catalog_missing',
    'The study files folder (catalog_root) does not exist on disk. Create it or fix the path under Settings > General.',
  ],
  CACHE_NOT_WRITABLE: [
    'sh_err_cache_writable',
    'The header cache folder is not writable. The web server must be able to create files under catalog_root/cache/site_header.',
  ],
  UPLOAD_NOT_WRITABLE: [
    'sh_err_upload_writable',
    'The header image folder is not writable. The web server must be able to create files under files/site.',
  ],
  UPLOAD_FAILED: [
    'sh_err_upload_failed',
    'The image could not be saved. Check that files/site exists and is writable.',
  ],
  STORAGE_NOT_WRITABLE: [
    'sh_err_upload_writable',
    'The header image folder is not writable. The web server must be able to create files under files/site.',
  ],
  'CSRF-TOKEN-INVALID': ['sh_err_csrf', 'The page is out of date. Reload it and try again.'],
  'ACCESS-DENIED': ['sh_err_access', 'Access denied'],
};

export function useSiteHeaderStore() {
  if (!api) {
    api = useSiteLayoutApi().part('header');
    i18n = useI18n();
  }
  const { t } = i18n;

  function message(code, fallback) {
    const entry = ERRORS[code];
    return entry ? t(entry[0], entry[1]) : fallback || code || t('sh_err_generic', 'Something went wrong.');
  }

  function errorFrom(e) {
    const data = e?.response?.data || {};
    fieldErrors.value = data.errors || {};
    return message(data.message, e?.message);
  }

  function notify(text, color = 'success') {
    toast.message = text;
    toast.color = color;
    toast.show = true;
  }

  async function load() {
    loadError.value = '';
    try {
      applyServer(await api.load());
      renderNow();
    } catch (e) {
      loadError.value = errorFrom(e);
    }
  }

  /** Server rendering of the unsaved settings for the live preview */
  async function renderNow() {
    clearTimeout(renderTimer);
    if (!form.value) return;
    const seq = ++renderSeq;
    preview.rendering = true;
    try {
      const data = await api.render(previewPayload(), asGuest.value);
      if (seq !== renderSeq) return;
      preview.html = data.html || '';
      preview.template = data.template || '';
      preview.version++;
      const errs = data.errors && typeof data.errors === 'object' ? data.errors : {};
      fieldErrors.value = Array.isArray(errs) ? {} : errs;
    } catch {
      // the preview keeps the last good header; saving reports errors
    } finally {
      if (seq === renderSeq) preview.rendering = false;
    }
  }

  function scheduleRender() {
    clearTimeout(renderTimer);
    renderTimer = setTimeout(renderNow, RENDER_MS);
  }

  async function saveDraft() {
    clearTimeout(saveTimer);
    if (!form.value) return;
    if (saving.value) {
      saveQueued = true;
      return;
    }
    saving.value = true;
    const sent = snapshot(form.value);
    try {
      const data = await api.saveDraft(payload(form.value));
      applyServer(data, { keepForm: true });
      warnings.value = data.warnings || [];
      baseline = sent;
      if (snapshot(form.value) === sent) {
        dirty.value = false;
      }
    } catch (e) {
      notify(errorFrom(e), 'error');
    } finally {
      saving.value = false;
      if (saveQueued) {
        saveQueued = false;
        saveDraft();
      }
    }
  }

  async function run(name, fn, success) {
    clearTimeout(saveTimer);
    busy.value = name;
    try {
      const data = await fn();
      applyServer(data);
      warnings.value = data.warnings || [];
      fieldErrors.value = {};
      renderNow();
      if (success) notify(success);
      return true;
    } catch (e) {
      notify(errorFrom(e), 'error');
      return false;
    } finally {
      busy.value = '';
    }
  }

  async function publish() {
    clearTimeout(saveTimer);
    busy.value = 'publish';
    try {
      const data = await api.publish(payload(form.value));
      applyServer(data);
      warnings.value = data.warnings || [];
      fieldErrors.value = {};
      renderNow();
      notify(t('sh_published', 'The header is published.'));
      return true;
    } catch (e) {
      notify(errorFrom(e), 'error');
      return false;
    } finally {
      busy.value = '';
    }
  }
  const discard = () => run('discard', () => api.discard(), t('sh_discarded', 'Changes discarded.'));
  const reset = () =>
    run('reset', () => api.reset(), t('sh_reset_done', 'The theme file header is back.'));

  /** Write the published file again from the published settings; the draft and the editor are kept */
  async function republish() {
    busy.value = 'republish';
    try {
      applyServer(await api.republish(), { keepForm: true });
      notify(t('sh_republished', 'The published header is live again.'));
      return true;
    } catch (e) {
      notify(errorFrom(e), 'error');
      return false;
    } finally {
      busy.value = '';
    }
  }

  async function upload(kind, file) {
    try {
      return await api.upload(kind, file);
    } catch (e) {
      notify(errorFrom(e), 'error');
      return null;
    }
  }

  /** Use a layout: its rows (and header colours, when it has them); brand, logo and other settings stay */
  function applyPreset(preset) {
    const s = preset.settings || {};
    form.value.source = 'builder';
    form.value.mode = 'builder';
    editorView.value = 'builder';
    form.value.preset = preset.id;
    form.value.background_scope = s.background_scope || 'header';
    form.value.rows = clone(s.rows);
    if (s.header_style) {
      form.value.header_style = { ...form.value.header_style, ...clone(s.header_style) };
    }
  }

  /** True when the rows differ from the layout they started from */
  function rowsChangedFromPreset() {
    const p = server.presets.find((x) => x.id === form.value?.preset);
    if (!p) return true;
    return JSON.stringify(payload(form.value.rows)) !== JSON.stringify(payload(p.settings.rows));
  }

  /** Builder rows as HTML with tokens, for the custom HTML editor */
  async function builderTemplate() {
    const asBuilder = { ...payload(form.value), mode: 'builder' };
    const data = await api.render(asBuilder, asGuest.value);
    return data.template || '';
  }

  /** Where a single item (brand, menu, login, language, search) is placed: {row, slot, index} or null */
  function findItem(type) {
    for (const row of ROW_IDS) {
      for (const slot of SLOTS) {
        const i = (form.value?.rows?.[row]?.[slot] || []).findIndex((it) => it.type === type);
        if (i >= 0) return { row, slot, index: i };
      }
    }
    return null;
  }

  const hasDraft = computed(() => !!server.draft);
  const canPublish = computed(() => hasDraft.value || dirty.value);
  const hasMenuToken = computed(() => /\{\{\s*menu(\+[a-z_]+)*\s*\}\}/.test(form.value?.html || ''));

  return {
    server,
    form,
    editorView,
    syncModeFromSource,
    loadError,
    saving,
    busy,
    dirty,
    warnings,
    fieldErrors,
    asGuest,
    preview,
    toast,
    hasDraft,
    canPublish,
    hasMenuToken,
    t,
    load,
    renderNow,
    scheduleRender,
    saveDraft,
    publish,
    republish,
    discard,
    reset,
    upload,
    applyPreset,
    rowsChangedFromPreset,
    builderTemplate,
    findItem,
    notify,
    message,
  };
}

/** Live preview and autosave: call once from the root component */
export function useSiteHeaderAutosave() {
  const store = useSiteHeaderStore();
  watch(
    form,
    () => {
      if (!form.value) return;
      store.scheduleRender();
      if (snapshot(form.value) === baseline) {
        dirty.value = false;
        clearTimeout(saveTimer);
        return;
      }
      dirty.value = true;
      clearTimeout(saveTimer);
      saveTimer = setTimeout(() => store.saveDraft(), AUTOSAVE_MS);
    },
    { deep: true }
  );
  watch(asGuest, () => store.renderNow());
  watch(editorView, () => store.scheduleRender());
  return store;
}
