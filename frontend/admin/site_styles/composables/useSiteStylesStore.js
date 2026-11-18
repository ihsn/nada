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
  exampleCss: '',
  cache: { ok: true, code: '', path: '', catalog_root: '' },
  /** False when the published file (what visitors see) does not match the published settings */
  fileInSync: true,
  limits: {},
});

const form = ref(null);
const loadError = ref('');
const saving = ref(false);
const busy = ref('');
const dirty = ref(false);
const warnings = ref([]);
const fieldErrors = ref({});
const preview = reactive({ css: '', rendering: false, version: 0 });
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
}

function emptyForm() {
  return {
    version: 1,
    css: '',
  };
}

function applyServer(data, { keepForm = false } = {}) {
  server.loaded = true;
  server.published = data.published;
  server.draft = data.draft;
  server.isDefault = !!data.is_default;
  server.forceDefault = !!data.force_default;
  server.defaults = data.defaults || {};
  server.exampleCss = typeof data.example_css === 'string' ? data.example_css : '';
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
    setForm(data.draft || data.published || emptyForm());
  }
}

const ERRORS = {
  VALIDATION_FAILED: ['ss_err_validation', 'Some values are not valid. Check the highlighted fields.'],
  CSS_TOO_LONG: ['ss_err_css_long', 'The CSS is too long.'],
  NO_DRAFT: ['ss_err_no_draft', 'There are no changes to publish.'],
  RUN_MIGRATIONS: [
    'ss_err_migrate',
    'The styles cannot be saved because the site layout table is missing. Run the database migrations, then try again.',
  ],
  CACHE_WRITE_FAILED: [
    'ss_err_cache_write',
    'The styles file under catalog_root/cache/site_styles could not be written or removed, so nothing was changed. Check that the folder is writable, then try again.',
  ],
  CATALOG_ROOT_NOT_SET: [
    'ss_err_catalog_root',
    'The study files folder (catalog_root) is not set. Set it under Settings > General before publishing styles.',
  ],
  CATALOG_ROOT_MISSING: [
    'ss_err_catalog_missing',
    'The study files folder (catalog_root) does not exist on disk. Create it or fix the path under Settings > General.',
  ],
  CACHE_NOT_WRITABLE: [
    'ss_err_cache_writable',
    'The styles cache folder is not writable. The web server must be able to create files under catalog_root/cache/site_styles.',
  ],
  'CSRF-TOKEN-INVALID': ['ss_err_csrf', 'The page is out of date. Reload it and try again.'],
  'ACCESS-DENIED': ['ss_err_access', 'Access denied'],
};

export function useSiteStylesStore() {
  if (!api) {
    api = useSiteLayoutApi().part('styles');
    i18n = useI18n();
  }
  const { t } = i18n;

  function message(code, fallback) {
    const entry = ERRORS[code];
    return entry ? t(entry[0], entry[1]) : fallback || code || t('ss_err_generic', 'Something went wrong.');
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
      const data = await api.render(payload(form.value));
      if (seq !== renderSeq) return;
      preview.css = data.css || '';
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

  async function publish() {
    busy.value = 'publish';
    try {
      clearTimeout(saveTimer);
      const data = await api.publish(payload(form.value));
      warnings.value = data.warnings || [];
      applyServer(data);
      notify(t('ss_published', 'Styles published.'));
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
      notify(t('ss_discarded', 'Changes discarded.'));
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
      notify(t('ss_reset_done', 'Published styles cleared.'));
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
      notify(t('ss_republished', 'The published styles are live again.'));
    } catch (e) {
      notify(errorFrom(e), 'error');
    } finally {
      busy.value = '';
    }
  }

  function insertExample() {
    if (!form.value) return;
    form.value.css = server.exampleCss || '';
  }

  const hasDraft = computed(() => !!server.draft);
  const canPublish = computed(() => hasDraft.value || dirty.value);

  return {
    server,
    form,
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
    insertExample,
    notify,
  };
}
