import { computed, reactive, ref, watch } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import { useSiteLayoutApi } from '../../site_layout/composables/useSiteLayoutApi';

const RENDER_MS = 150;
const AUTOSAVE_MS = 1200;

const server = reactive({
  loaded: false,
  published: null,
  draft: null,
  isDefault: true,
  forceDefault: false,
  defaults: {},
  tokens: [],
  themeName: 'nada52',
  hasLocalFooter: false,
  themeFooterFile: '',
  cache: { ok: true, code: '', path: '', catalog_root: '' },
  /** False when the published file (what visitors see) does not match the published settings */
  fileInSync: true,
  limits: {},
});

const form = ref(null);
/** Editor tab only (theme | custom). Independent of form.source (Choose source). */
const editorView = ref('theme');
const loadError = ref('');
const saving = ref(false);
const busy = ref('');
const dirty = ref(false);
const warnings = ref([]);
const fieldErrors = ref({});
const preview = reactive({ html: '', rendering: false, version: 0 });
const toast = reactive({ show: false, message: '', color: 'success' });

let api = null;
let i18n = null;
let saveTimer = null;
let renderTimer = null;
let renderSeq = 0;
let baseline = '';

function payload(settings) {
  return JSON.parse(JSON.stringify(settings || {}));
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
  editorView.value = src === 'custom' || src === 'theme' ? src : 'theme';
}

/** Settings sent to the preview renderer: current form, rendered as the open editor tab. */
function previewPayload() {
  const src = editorView.value === 'custom' ? 'custom' : 'theme';
  return {
    ...payload(form.value),
    source: src,
  };
}

function applyServer(data, { keepForm = false } = {}) {
  server.loaded = true;
  server.published = data.published;
  server.draft = data.draft;
  server.isDefault = !!data.is_default;
  server.forceDefault = !!data.force_default;
  server.defaults = data.defaults || {};
  server.tokens = data.tokens || [];
  server.themeName = data.theme_name || 'nada52';
  server.hasLocalFooter = !!data.has_local_footer;
  server.themeFooterFile = data.theme_footer_file || '';
  server.cache =
    data.cache && typeof data.cache === 'object'
      ? {
          ok: !!data.cache.ok,
          code: data.cache.code || '',
          path: data.cache.path || '',
          catalog_root: data.cache.catalog_root || '',
        }
      : { ok: true, code: '', path: '', catalog_root: '' };
  server.fileInSync = data.file_in_sync !== false;
  server.limits = data.limits || {};
  if (!keepForm) {
    const next = data.draft || data.published;
    if (next && !next.source) {
      next.source = 'theme';
    }
    setForm(next);
  }
}

const ERRORS = {
  VALIDATION_FAILED: ['sf_err_validation', 'Some values are not valid. Check the highlighted fields.'],
  HTML_TOO_LONG: ['sf_err_html_long', 'The HTML is too long.'],
  CSS_TOO_LONG: ['sf_err_css_long', 'The CSS is too long.'],
  NO_DRAFT: ['sf_err_no_draft', 'There are no changes to publish.'],
  RUN_MIGRATIONS: [
    'sf_err_migrate',
    'The footer cannot be saved because the site layout table is missing. Run the database migrations, then try again.',
  ],
  CACHE_WRITE_FAILED: [
    'sf_err_cache_write',
    'The footer file under catalog_root/cache/site_footer could not be written or removed, so nothing was changed. Check that the folder is writable, then try again.',
  ],
  CATALOG_ROOT_NOT_SET: [
    'sf_err_catalog_root',
    'The study files folder (catalog_root) is not set. Set it under Settings > General before publishing a custom footer.',
  ],
  CATALOG_ROOT_MISSING: [
    'sf_err_catalog_missing',
    'The study files folder (catalog_root) does not exist on disk. Create it or fix the path under Settings > General.',
  ],
  CACHE_NOT_WRITABLE: [
    'sf_err_cache_writable',
    'The footer cache folder is not writable. The web server must be able to create files under catalog_root/cache/site_footer.',
  ],
  'CSRF-TOKEN-INVALID': ['sf_err_csrf', 'The page is out of date. Reload it and try again.'],
  'ACCESS-DENIED': ['sf_err_access', 'Access denied'],
};

export function useSiteFooterStore() {
  if (!api) {
    api = useSiteLayoutApi().part('footer');
    i18n = useI18n();
  }
  const { t } = i18n;

  function message(code, fallback) {
    const entry = ERRORS[code];
    return entry ? t(entry[0], entry[1]) : fallback || code || t('sf_err_generic', 'Something went wrong.');
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

  async function renderNow() {
    if (!form.value) return;
    const seq = ++renderSeq;
    preview.rendering = true;
    try {
      const data = await api.render(previewPayload());
      if (seq !== renderSeq) return;
      preview.html = data.html || '';
      preview.version += 1;
    } catch (e) {
      if (seq === renderSeq) notify(errorFrom(e), 'error');
    } finally {
      if (seq === renderSeq) preview.rendering = false;
    }
  }

  function scheduleRender() {
    clearTimeout(renderTimer);
    renderTimer = setTimeout(renderNow, RENDER_MS);
  }

  async function saveDraftNow() {
    if (!form.value || !dirty.value) return;
    saving.value = true;
    try {
      const data = await api.saveDraft(payload(form.value));
      warnings.value = data.warnings || [];
      applyServer(data, { keepForm: true });
      baseline = snapshot(form.value);
      dirty.value = false;
      server.draft = clone(form.value);
    } catch (e) {
      notify(errorFrom(e), 'error');
    } finally {
      saving.value = false;
    }
  }

  function scheduleSave() {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(saveDraftNow, AUTOSAVE_MS);
  }

  watch(
    form,
    () => {
      if (!form.value || !server.loaded) return;
      dirty.value = snapshot(form.value) !== baseline;
      scheduleRender();
      if (dirty.value) scheduleSave();
    },
    { deep: true }
  );

  watch(editorView, () => scheduleRender());

  async function publish() {
    busy.value = 'publish';
    try {
      clearTimeout(saveTimer);
      const data = await api.publish(payload(form.value));
      warnings.value = data.warnings || [];
      applyServer(data);
      notify(t('sf_published', 'Footer published.'));
      renderNow();
    } catch (e) {
      notify(errorFrom(e), 'error');
    } finally {
      busy.value = '';
    }
  }

  async function discard() {
    busy.value = 'discard';
    try {
      applyServer(await api.discard());
      notify(t('sf_discarded', 'Changes discarded.'));
      renderNow();
    } catch (e) {
      notify(errorFrom(e), 'error');
    } finally {
      busy.value = '';
    }
  }

  async function reset() {
    busy.value = 'reset';
    try {
      applyServer(await api.reset());
      notify(t('sf_reset_done', 'Footer reset to the theme file.'));
      renderNow();
    } catch (e) {
      notify(errorFrom(e), 'error');
    } finally {
      busy.value = '';
    }
  }

  /** Write the published file again from the published settings; the draft and the editor are kept */
  async function republish() {
    busy.value = 'republish';
    try {
      applyServer(await api.republish(), { keepForm: true });
      notify(t('sf_republished', 'The published footer is live again.'));
    } catch (e) {
      notify(errorFrom(e), 'error');
    } finally {
      busy.value = '';
    }
  }

  const hasDraft = computed(() => !!server.draft);
  const canPublish = computed(() => hasDraft.value || dirty.value);

  return {
    server,
    form,
    editorView,
    loadError,
    saving,
    busy,
    dirty,
    warnings,
    fieldErrors,
    preview,
    toast,
    hasDraft,
    canPublish,
    t,
    message,
    load,
    renderNow,
    saveDraft: saveDraftNow,
    publish,
    republish,
    discard,
    reset,
    notify,
  };
}
