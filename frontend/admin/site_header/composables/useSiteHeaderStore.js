import { computed, reactive, ref, watch } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import { useSiteHeaderApi } from './useSiteHeaderApi';

/**
 * Shared state of the site header editor (one instance per page).
 *
 * `form` holds the settings being edited. Every change is saved as the draft after a short pause; the preview
 * frame shows the draft. Publish copies the draft to the public site.
 */

const AUTOSAVE_MS = 800;

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
  limits: {},
});

const form = ref(null);
const loadError = ref('');
const saving = ref(false);
const busy = ref('');
const dirty = ref(false);
const warnings = ref([]);
const fieldErrors = ref({});
const previewVersion = ref(0);
const toast = reactive({ show: false, message: '', color: 'success' });

let api = null;
let i18n = null;
let timer = null;
/** Snapshot of the settings last loaded from or saved to the server */
let baseline = '';
let saveQueued = false;

/** Fields the server stores (the *_url fields are for display only) */
function payload(settings) {
  const out = { ...settings };
  delete out.logo_url;
  delete out.bg_image_url;
  return out;
}

function snapshot(settings) {
  return JSON.stringify(payload(settings || {}));
}

function setForm(settings) {
  form.value = settings ? JSON.parse(JSON.stringify(settings)) : null;
  baseline = snapshot(form.value);
  dirty.value = false;
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
  server.limits = data.limits || {};
  if (!keepForm) {
    setForm(data.draft || data.published);
  }
}

const ERRORS = {
  VALIDATION_FAILED: ['sh_err_validation', 'Some values are not valid. Check the highlighted fields.'],
  INVALID_COLOR: ['sh_err_color', 'Use a colour like #1a2b3c.'],
  INVALID_IMAGE: ['sh_err_image', 'Upload the image again.'],
  IMAGE_NOT_FOUND: ['sh_err_image_missing', 'The image file is missing. Upload it again.'],
  HTML_TOO_LONG: ['sh_err_html_long', 'The HTML is too long.'],
  CSS_TOO_LONG: ['sh_err_css_long', 'The CSS is too long.'],
  PRESET_NOT_FOUND: ['sh_err_preset', 'That layout no longer exists. Pick another one.'],
  NO_DRAFT: ['sh_err_no_draft', 'There are no changes to publish.'],
  FILE_TOO_LARGE: ['sh_err_file_large', 'The file is too large.'],
  FILE_TYPE_NOT_ALLOWED: ['sh_err_file_type', 'This type of file is not allowed.'],
  FILE_CONTENT_MISMATCH: ['sh_err_file_content', 'The file is not a valid image.'],
  SVG_NOT_SAFE: ['sh_err_svg', 'This SVG contains scripts or external links and cannot be used. Export it again as a plain SVG or PNG.'],
  NO_FILE: ['sh_err_no_file', 'Choose a file to upload.'],
  VALUE_TOO_LONG_RUN_MIGRATIONS: ['sh_err_migrate', 'The header is too large for the database. Run the database migrations, then try again.'],
  'CSRF-TOKEN-INVALID': ['sh_err_csrf', 'The page is out of date. Reload it and try again.'],
  'ACCESS-DENIED': ['sh_err_access', 'Access denied'],
};

export function useSiteHeaderStore() {
  if (!api) {
    api = useSiteHeaderApi();
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
    } catch (e) {
      loadError.value = errorFrom(e);
    }
  }

  async function saveDraft() {
    clearTimeout(timer);
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
      fieldErrors.value = {};
      baseline = sent;
      if (snapshot(form.value) === sent) {
        dirty.value = false;
      }
      previewVersion.value++;
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
    clearTimeout(timer);
    busy.value = name;
    try {
      const data = await fn();
      applyServer(data);
      warnings.value = data.warnings || [];
      fieldErrors.value = {};
      previewVersion.value++;
      if (success) notify(success);
      return true;
    } catch (e) {
      notify(errorFrom(e), 'error');
      return false;
    } finally {
      busy.value = '';
    }
  }

  const publish = () =>
    run('publish', () => api.publish(payload(form.value)), t('sh_published', 'The header is published.'));
  const discard = () => run('discard', () => api.discard(), t('sh_discarded', 'Changes discarded.'));
  const reset = () =>
    run('reset', () => api.reset(), t('sh_reset_done', 'The default NADA header is back.'));

  async function upload(kind, file) {
    try {
      return await api.upload(kind, file);
    } catch (e) {
      notify(errorFrom(e), 'error');
      return null;
    }
  }

  function presetById(id) {
    return server.presets.find((p) => p.id === id) || null;
  }

  const hasDraft = computed(() => !!server.draft);
  const canPublish = computed(() => hasDraft.value || dirty.value);
  const hasMenuToken = computed(() => /\{\{\s*menu(\+[a-z_]+)*\s*\}\}/.test(form.value?.html || ''));

  return {
    server,
    form,
    loadError,
    saving,
    busy,
    dirty,
    warnings,
    fieldErrors,
    previewVersion,
    toast,
    hasDraft,
    canPublish,
    hasMenuToken,
    t,
    load,
    saveDraft,
    publish,
    discard,
    reset,
    upload,
    presetById,
    notify,
    message,
  };
}

/** Autosave: call once from the root component */
export function useSiteHeaderAutosave() {
  const store = useSiteHeaderStore();
  watch(
    form,
    () => {
      if (!form.value || snapshot(form.value) === baseline) {
        dirty.value = false;
        clearTimeout(timer);
        return;
      }
      dirty.value = true;
      clearTimeout(timer);
      timer = setTimeout(() => store.saveDraft(), AUTOSAVE_MS);
    },
    { deep: true }
  );
  return store;
}
