<script setup>
import { ref, reactive, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { useRoute } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useSiteConfigurationsApi } from '../composables/useSiteConfigurationsApi';
import { SECTION_DEFS } from '../sections';
import '../site-config-layout.css';

defineOptions({ name: 'SiteConfigurationsPage' });

const route = useRoute();
const { config } = useAppConfig();
const { fetchSettings, fetchMeta, saveSettings, fetchTestEmailForm, sendTestEmail } =
  useSiteConfigurationsApi();

/** Matches legacy dropdown in application/views/site_configurations/test_email.php */
const testEmailUseragents = [
  { value: 'CodeIgniter', title: 'Default' },
  { value: 'PHPMailer', title: 'PHPMailer' },
];

const testEmailForm = reactive({
  smtp_host: '',
  smtp_port: '',
  smtp_auth: false,
  smtp_crypto: '',
  useragent: 'CodeIgniter',
  mail_from: '',
  smtp_user: '',
  smtp_pass: '',
  mail_to: '',
});
const testEmailLoading = ref(false);
const testEmailSending = ref(false);
const testEmailFullResponse = ref('');
const adminHeaderColorDialog = ref(false);
const adminHeaderDraftColor = ref('#212121');
const adminHeaderSwatches = [
  ['#212121', '#263238', '#1b1f3b', '#004d40', '#3e2723', '#4a148c', '#0d47a1', '#111827'],
];

function tr(key) {
  const v = config.value?.translations?.[key];
  return v !== undefined && v !== '' ? v : key;
}

const activeSection = computed(() =>
  typeof route.params.section === 'string' ? route.params.section : 'general',
);
const settings = ref({});
const meta = ref({});
const langRows = ref([]);
const loading = ref(true);
const saving = ref(false);
const snackbar = ref(false);
const snackbarText = ref('');
const snackbarIsError = ref(false);

const ui = computed(() => config.value?.ui || {});
const emailConfigFileExists = computed(() => !!meta.value?.email_config_file_exists);
const adminHeaderPreviewColor = computed(() => {
  const value = String(settings.value?.admin_header_background || '').trim();
  return /^#[0-9a-fA-F]{6}$/.test(value) ? value : '#212121';
});

function openAdminHeaderColorDialog() {
  adminHeaderDraftColor.value = adminHeaderPreviewColor.value;
  adminHeaderColorDialog.value = true;
}

function cancelAdminHeaderColorDialog() {
  adminHeaderColorDialog.value = false;
}

function applyAdminHeaderColorDialog() {
  settings.value.admin_header_background = adminHeaderDraftColor.value;
  adminHeaderColorDialog.value = false;
}

const editEmailHtml = computed(
  () =>
    config.value?.translations?.edit_email_settings ||
    'Email is configured in <code>application/config/email.php</code>.'
);

const isoSelectItems = computed(() => {
  const iso = meta.value?.iso_languages || {};
  return Object.keys(iso).map((code) => {
    const row = iso[code];
    const name = row?.name ?? code;
    const display = row?.display ?? '';
    return {
      value: code,
      title: `${name} — ${display} (${code})`,
    };
  });
});

const defaultLanguageItems = computed(() => {
  const folders = meta.value?.available_folders || [];
  const codes = meta.value?.language_codes || {};
  return folders.map((folder) => ({
    value: folder,
    title: codes[folder]?.display ? String(codes[folder].display) : folder.charAt(0).toUpperCase() + folder.slice(1),
  }));
});

function buildLangRows(folders, supported) {
  const sup = Array.isArray(supported) ? supported : [];
  return (folders || []).map((folder) => {
    const ex = sup.find((s) => s.folder === folder);
    return {
      folder,
      enabled: !!ex,
      code: ex?.code ?? '',
      display: ex?.display ?? '',
      direction: ex?.direction ?? '',
    };
  });
}

function onIsoChange(row) {
  const iso = meta.value?.iso_languages || {};
  const code = row.code;
  if (!code || !iso[code]) {
    row.display = '';
    row.direction = '';
    return;
  }
  row.display = iso[code].display ?? '';
  row.direction = iso[code].direction ?? 'ltr';
}

function supportedPayloadFromRows() {
  return langRows.value
    .filter((r) => r.enabled)
    .map((r) => ({
      folder: r.folder,
      display: r.display || (r.folder.charAt(0).toUpperCase() + r.folder.slice(1)),
      code: (r.code || '').trim(),
      direction: r.direction || 'ltr',
    }));
}

function pickSectionPayload(sectionId) {
  const def = SECTION_DEFS.find((s) => s.id === sectionId);
  if (!def) return {};
  const out = {};
  for (const k of def.keys) {
    if (k === 'supported_languages') {
      out[k] = supportedPayloadFromRows();
    } else if (settings.value[k] !== undefined) {
      out[k] = settings.value[k];
    }
  }
  return out;
}

async function reloadAll() {
  const [s, m] = await Promise.all([fetchSettings(), fetchMeta()]);
  settings.value = { ...s };
  meta.value = { ...m };
  langRows.value = buildLangRows(m.available_folders, settings.value.supported_languages);
}

async function loadTestEmailSection() {
  testEmailLoading.value = true;
  try {
    const f = await fetchTestEmailForm();
    Object.assign(testEmailForm, {
      smtp_host: String(f.smtp_host ?? ''),
      smtp_port: String(f.smtp_port ?? ''),
      smtp_auth: !!f.smtp_auth,
      smtp_crypto: String(f.smtp_crypto ?? ''),
      useragent: String(f.useragent ?? 'CodeIgniter'),
      mail_from: String(f.mail_from ?? ''),
      smtp_user: String(f.smtp_user ?? ''),
      smtp_pass: String(f.smtp_pass ?? ''),
      mail_to: '',
    });
    testEmailFullResponse.value = '';
  } catch (e) {
    snackbarIsError.value = true;
    snackbarText.value = e?.message || String(e);
    snackbar.value = true;
  } finally {
    testEmailLoading.value = false;
  }
}

async function submitTestEmail() {
  testEmailSending.value = true;
  try {
    const res = await sendTestEmail({
      smtp_host: testEmailForm.smtp_host,
      smtp_port: String(testEmailForm.smtp_port ?? ''),
      smtp_auth: testEmailForm.smtp_auth,
      smtp_crypto: testEmailForm.smtp_crypto,
      useragent: testEmailForm.useragent,
      mail_from: testEmailForm.mail_from,
      smtp_user: testEmailForm.smtp_user,
      smtp_pass: testEmailForm.smtp_pass,
      mail_to: testEmailForm.mail_to,
    });
    testEmailFullResponse.value = JSON.stringify(res, null, 2);
    snackbarIsError.value = !res.sent;
    snackbarText.value = res.sent ? tr('test_email_send_ok') : tr('test_email_send_failed');
    snackbar.value = true;
  } catch (e) {
    snackbarIsError.value = true;
    snackbarText.value = e?.message || String(e);
    snackbar.value = true;
    testEmailFullResponse.value = JSON.stringify(
      {
        status: 'error',
        message: e?.response?.data?.message || e?.message || String(e),
        response: e?.response?.data || null,
      },
      null,
      2,
    );
  } finally {
    testEmailSending.value = false;
  }
}

async function saveCurrentSection() {
  if (activeSection.value === 'mail' && emailConfigFileExists.value) {
    return;
  }
  saving.value = true;
  try {
    const payload = pickSectionPayload(activeSection.value);
    await saveSettings(payload);
    snackbarIsError.value = false;
    snackbarText.value = tr('form_update_success');
    snackbar.value = true;
    await reloadAll();
  } catch (e) {
    snackbarIsError.value = true;
    snackbarText.value = e?.response?.data?.message || e?.message || String(e);
    snackbar.value = true;
  } finally {
    saving.value = false;
  }
}

const currentTitle = computed(() => {
  const def = SECTION_DEFS.find((s) => s.id === activeSection.value);
  return def ? tr(def.titleKey) : '';
});

/** Hide Save for tooling-only sections and when mail uses config/email.php only */
const saveVisible = computed(() => {
  if (activeSection.value === 'mail' && emailConfigFileExists.value) return false;
  return true;
});

const pathsOk = computed(() => meta.value?.paths_ok || {});

const PAGE_ALERT_MS = 4000;
let pageAlertTimer;
watch([snackbar, snackbarText], () => {
  clearTimeout(pageAlertTimer);
  if (!snackbar.value) return;
  pageAlertTimer = window.setTimeout(() => {
    snackbar.value = false;
  }, PAGE_ALERT_MS);
});

onBeforeUnmount(() => clearTimeout(pageAlertTimer));

watch(
  () => activeSection.value,
  (id) => {
    if (id === 'mail') {
      loadTestEmailSection();
    }
  },
  { immediate: true },
);

onMounted(async () => {
  loading.value = true;
  try {
    await reloadAll();
  } catch (e) {
    snackbarIsError.value = true;
    snackbarText.value = e?.message || String(e);
    snackbar.value = true;
  } finally {
    loading.value = false;
  }
});

</script>

<template>
  <v-app class="site-config-app">
    <v-main class="site-config-page-bg">
      <v-container fluid class="site-config-main py-6">
        <header class="site-config-page-header">
          <h1 class="site-config-page-title text-h5 font-weight-medium">
            {{ tr('site_configurations') }}
          </h1>
          <v-alert
            v-if="snackbar"
            class="site-config-page-alert"
            :class="snackbarIsError ? 'site-config-page-alert--error' : 'site-config-page-alert--success'"
            variant="flat"
            density="comfortable"
            closable
            @click:close="snackbar = false"
          >
            {{ snackbarText }}
          </v-alert>
        </header>

        <div v-if="loading" class="d-flex justify-center py-12">
          <v-progress-circular indeterminate color="primary" />
        </div>

        <div v-else class="site-config-layout">
          <aside class="site-config-nav bg-surface">
            <v-list nav density="compact" class="pa-2">
              <v-list-item
                v-for="s in SECTION_DEFS"
                :key="s.id"
                :to="{ name: 'site-config-section', params: { section: s.id } }"
                :active="route.params.section === s.id"
                color="primary"
                rounded="lg"
              >
                <v-list-item-title class="text-body-2">
                  {{ tr(s.titleKey) }}
                </v-list-item-title>
              </v-list-item>
            </v-list>
          </aside>

          <div class="site-config-panels">
          <!-- General -->
          <v-card v-show="activeSection === 'general'" elevation="1" rounded="lg" class="bg-surface">
            <div class="pa-4 site-config-card-inner">
              <div class="site-config-card__header d-flex align-center justify-space-between flex-wrap gap-3">
                <h2 class="site-config-card__title">
                  {{ currentTitle }}
                </h2>
                <v-btn
                  v-if="saveVisible"
                  color="primary"
                  :loading="saving"
                  prepend-icon="mdi-content-save"
                  @click="saveCurrentSection"
                >
                  {{ tr('update') }}
                </v-btn>
              </div>
              <v-row dense>
              <v-col cols="12">
                <label class="site-config-field__label">{{ tr('website_title') }}</label>
                <v-text-field v-model="settings.website_title" variant="outlined" density="comfortable" hide-details="auto" />
              </v-col>
              <v-col cols="12">
                <label class="site-config-field__label">{{ tr('website_footer') }}</label>
                <v-textarea v-model="settings.website_footer" variant="outlined" rows="4" hide-details="auto" />
              </v-col>
              <v-col cols="12">
                <label class="site-config-field__label">{{ tr('default_home_page') }}</label>
                <v-text-field
                  v-model="settings.default_home_page"
                  variant="outlined"
                  density="comfortable"
                  hide-details="auto"
                  style="max-width: 300px;"
                />
                <div class="site-config-field__hint">{{ tr('instruction_default_home_page') }}</div>
              </v-col>
              <v-col cols="12">
                <label class="site-config-field__label">{{ tr('webmaster_name') }}</label>
                <v-text-field
                  v-model="settings.website_webmaster_name"
                  variant="outlined"
                  density="comfortable"
                  hide-details="auto"
                  style="max-width: 300px;"
                />
              </v-col>
              <v-col cols="12">
                <label class="site-config-field__label">{{ tr('webmaster_email') }}</label>
                <v-text-field
                  v-model="settings.website_webmaster_email"
                  variant="outlined"
                  density="comfortable"
                  hide-details="auto"
                  style="max-width: 300px;"
                />
              </v-col>
              <v-col cols="12">
                <label class="site-config-field__label">{{ tr('max_resource_upload_size') }}</label>
                <v-text-field
                  v-model="settings.max_resource_upload_size"
                  variant="outlined"
                  density="comfortable"
                  hide-details="auto"
                  style="max-width: 300px;"
                />
                <div class="site-config-field__hint">{{ tr('max_resource_upload_size_note') }}</div>
              </v-col>
              <v-col cols="12">
                <label class="site-config-field__label">Admin header background</label>
                <div class="d-flex align-center ga-2 flex-wrap">
                  <v-text-field
                    v-model="settings.admin_header_background"
                    variant="outlined"
                    density="comfortable"
                    hide-details="auto"
                    placeholder="#212121"
                    style="max-width: 300px;"
                  />
                  <div
                    class="rounded border"
                    role="button"
                    tabindex="0"
                    style="width: 46px; height: 46px;"
                    :style="{ backgroundColor: adminHeaderPreviewColor }"
                    aria-label="Admin header background preview"
                    title="Pick background color"
                    @click="openAdminHeaderColorDialog"
                    @keydown.enter.prevent="openAdminHeaderColorDialog"
                    @keydown.space.prevent="openAdminHeaderColorDialog"
                  />
                </div>
                <v-dialog v-model="adminHeaderColorDialog" max-width="460">
                  <v-card>
                    <v-card-title class="text-h6">Pick background color</v-card-title>
                    <v-card-text class="pa-0">
                      <div class="px-4 pt-2">
                        <v-color-picker
                          v-model="adminHeaderDraftColor"
                          width="100%"
                          class="w-100"
                          mode="hexa"
                          :modes="['hexa']"                        
                          hide-inputs
                        />
                      </div>
                    </v-card-text>
                    <v-card-actions class="justify-end px-6 pb-4 pt-5">
                      <v-btn variant="text" @click="cancelAdminHeaderColorDialog">Cancel</v-btn>
                      <v-btn color="primary" variant="flat" @click="applyAdminHeaderColorDialog">Apply</v-btn>
                    </v-card-actions>
                  </v-card>
                </v-dialog>
              </v-col>
            </v-row>
            </div>
          </v-card>

          <!-- Languages -->
          <v-card v-show="activeSection === 'languages'" elevation="1" rounded="lg" class="bg-surface">
            <div class="pa-4 site-config-card-inner">
              <div class="site-config-card__header d-flex align-center justify-space-between flex-wrap gap-3">
                <h2 class="site-config-card__title">
                  {{ currentTitle }}
                </h2>
                <v-btn
                  v-if="saveVisible"
                  color="primary"
                  :loading="saving"
                  prepend-icon="mdi-content-save"
                  @click="saveCurrentSection"
                >
                  {{ tr('update') }}
                </v-btn>
              </div>
              <div class="site-config-languages-fields">
              <v-row>
              <v-col cols="12" md="8">
                <label class="site-config-field__label">{{ tr('default_language') }}</label>
                <v-select
                  v-model="settings.language"
                  class="site-config-default-lang-select"
                  :items="defaultLanguageItems"
                  item-title="title"
                  item-value="value"
                  variant="outlined"
                  density="comfortable"
                  hide-details
                />
                <div class="site-config-field__hint">{{ tr('default_language_note') }}</div>
              </v-col>
            </v-row>
            <p class="text-body-2 text-medium-emphasis mb-0">
              {{ tr('enabled_languages') }}
            </p>
            <p class="text-caption text-medium-emphasis mb-0">
              {{ tr('enabled_languages_note') }}
            </p>
            <v-table class="border rounded site-config-enabled-lang-table">
              <thead>
                <tr>
                  <th class="text-center" style="width:56px">{{ tr('enabled') }}</th>
                  <th>{{ tr('folder') }}</th>
                  <th class="site-config-iso-col">{{ tr('iso_language') }}</th>
                  <th>{{ tr('display_name') }}</th>
                  <th>{{ tr('direction') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in langRows" :key="row.folder">
                  <td class="text-center">
                    <v-checkbox v-model="row.enabled" density="compact" hide-details />
                  </td>
                  <td><code>{{ row.folder }}</code></td>
                  <td class="site-config-iso-col">
                    <v-select
                      v-model="row.code"
                      class="site-config-iso-lang-select"
                      :items="isoSelectItems"
                      item-title="title"
                      item-value="value"
                      clearable
                      density="compact"
                      variant="outlined"
                      hide-details
                      @update:model-value="() => onIsoChange(row)"
                    />
                  </td>
                  <td>{{ row.display }}</td>
                  <td>{{ row.direction }}</td>
                </tr>
              </tbody>
            </v-table>
              </div>
            </div>
          </v-card>

          <!-- HTML editor -->
          <v-card v-show="activeSection === 'html_editor'" elevation="1" rounded="lg" class="bg-surface">
            <div class="pa-4 site-config-card-inner">
              <div class="site-config-card__header d-flex align-center justify-space-between flex-wrap gap-3">
                <h2 class="site-config-card__title">
                  {{ currentTitle }}
                </h2>
                <v-btn
                  v-if="saveVisible"
                  color="primary"
                  :loading="saving"
                  prepend-icon="mdi-content-save"
                  @click="saveCurrentSection"
                >
                  {{ tr('update') }}
                </v-btn>
              </div>
            <label class="site-config-field__label">{{ tr('use_html_editor') }}</label>
            <div class="d-flex align-center ga-3 mt-1 flex-wrap">
              <v-switch
                v-model="settings.use_html_editor"
                true-value="yes"
                false-value="no"
                color="primary"
                density="comfortable"
                hide-details
                inset
              />
              <span class="text-body-2 text-medium-emphasis">{{ settings.use_html_editor === 'yes' ? tr('yes') : tr('no') }}</span>
            </div>
            </div>
          </v-card>

          <!-- Catalog -->
          <v-card v-show="activeSection === 'catalog'" elevation="1" rounded="lg" class="bg-surface">
            <div class="pa-4 site-config-card-inner">
              <div class="site-config-card__header d-flex align-center justify-space-between flex-wrap gap-3">
                <h2 class="site-config-card__title">
                  {{ currentTitle }}
                </h2>
                <v-btn
                  v-if="saveVisible"
                  color="primary"
                  :loading="saving"
                  prepend-icon="mdi-content-save"
                  @click="saveCurrentSection"
                >
                  {{ tr('update') }}
                </v-btn>
              </div>
              <v-row dense>
              <v-col cols="12">
                <label class="site-config-field__label">{{ tr('catalog_folder') }}</label>
                <div class="d-flex align-start ga-2">
                  <div class="flex-grow-1">
                    <v-text-field
                      v-model="settings.catalog_root"
                      variant="outlined"
                      density="comfortable"
                      hide-details
                    />
                    <div class="site-config-field__hint">{{ tr('instruction_catalog_root') }}</div>
                  </div>
                  <v-icon v-if="pathsOk.catalog_root" icon="mdi-check-circle" class="site-config-path-ok mt-2" />
                  <v-icon v-else icon="mdi-close-circle" class="site-config-path-bad mt-2" />
                </div>
              </v-col>
              <v-col cols="12">
                <label class="site-config-field__label">{{ tr('ddi_import_folder') }}</label>
                <div class="d-flex align-start ga-2">
                  <div class="flex-grow-1">
                    <v-text-field
                      v-model="settings.ddi_import_folder"
                      variant="outlined"
                      density="comfortable"
                      hide-details
                    />
                    <div class="site-config-field__hint">{{ tr('instruction_ddi_import_folder') }}</div>
                  </div>
                  <v-icon v-if="pathsOk.ddi_import_folder" icon="mdi-check-circle" class="site-config-path-ok mt-2" />
                  <v-icon v-else icon="mdi-close-circle" class="site-config-path-bad mt-2" />
                </div>
              </v-col>
              <v-col cols="12" sm="4">
                <label class="site-config-field__label">{{ tr('data_catalog_page_size') }}</label>
                <v-text-field v-model="settings.catalog_records_per_page" variant="outlined" density="comfortable" hide-details />
                <div class="site-config-field__hint">{{ tr('instruction_catalog_records_per_page') }}</div>
              </v-col>
              <v-col cols="12">
                <label class="site-config-field__label">{{ tr('catalog_variable_view') }}</label>
                <div class="d-flex align-center ga-3 mt-1 flex-wrap">
                  <v-switch
                    v-model="settings.catalog_variable_view"
                    true-value="yes"
                    false-value="no"
                    color="primary"
                    density="comfortable"
                    hide-details
                    inset
                  />
                  <span class="text-body-2 text-medium-emphasis">{{ settings.catalog_variable_view === 'yes' ? tr('yes') : tr('no') }}</span>
                </div>
                <div class="site-config-field__hint">{{ tr('catalog_variable_view_note') }}</div>
              </v-col>
              <v-col cols="12">
                <label class="site-config-field__label">{{ tr('catalog_show_abstract') }}</label>
                <div class="d-flex align-center ga-3 mt-1 flex-wrap">
                  <v-switch
                    v-model="settings.catalog_show_abstract"
                    true-value="yes"
                    false-value="no"
                    color="primary"
                    density="comfortable"
                    hide-details
                    inset
                  />
                  <span class="text-body-2 text-medium-emphasis">{{ settings.catalog_show_abstract === 'yes' ? tr('yes') : tr('no') }}</span>
                </div>
                <div class="site-config-field__hint">{{ tr('catalog_show_abstract_note') }}</div>
              </v-col>
              <v-col cols="12">
                <label class="site-config-field__label">{{ tr('data_types_nav_bar') }}</label>
                <div class="d-flex align-center ga-3 mt-1 flex-wrap">
                  <v-switch
                    v-model="settings.data_types_nav_bar"
                    true-value="yes"
                    false-value="no"
                    color="primary"
                    density="comfortable"
                    hide-details
                    inset
                  />
                  <span class="text-body-2 text-medium-emphasis">{{ settings.data_types_nav_bar === 'yes' ? tr('yes') : tr('no') }}</span>
                </div>
                <div class="site-config-field__hint">{{ tr('data_types_nav_bar_note') }}</div>
              </v-col>
              <v-col cols="12">
                <label class="site-config-field__label">{{ tr('guests_hide_microdata_tab') }}</label>
                <div class="d-flex align-center ga-3 mt-1 flex-wrap">
                  <v-switch
                    v-model="settings.guests_hide_microdata_tab"
                    true-value="yes"
                    false-value="no"
                    color="primary"
                    density="comfortable"
                    hide-details
                    inset
                  />
                  <span class="text-body-2 text-medium-emphasis">{{ settings.guests_hide_microdata_tab === 'yes' ? tr('yes') : tr('no') }}</span>
                </div>
                <div class="site-config-field__hint">{{ tr('guests_hide_microdata_tab_note') }}</div>
              </v-col>
              <v-col cols="12" md="6">
                <label class="site-config-field__label">{{ tr('catalog_default_sort_by') }}</label>
                <v-select
                  v-model="settings.catalog_default_sort_by"
                  :items="ui.catalog_sort_by_options || []"
                  item-title="label"
                  item-value="value"
                  variant="outlined"
                  density="comfortable"
                  hide-details
                />
                <div class="site-config-field__hint">{{ tr('catalog_default_sort_by_note') }}</div>
              </v-col>
              <v-col cols="12" md="6">
                <label class="site-config-field__label">{{ tr('catalog_default_sort_order') }}</label>
                <v-select
                  v-model="settings.catalog_default_sort_order"
                  :items="ui.catalog_sort_order_options || []"
                  item-title="label"
                  item-value="value"
                  variant="outlined"
                  density="comfortable"
                  hide-details
                />
              </v-col>
            </v-row>
            </div>
          </v-card>

          <!-- Search -->
          <v-card v-show="activeSection === 'search'" elevation="1" rounded="lg" class="bg-surface">
            <div class="pa-4 site-config-card-inner">
              <div class="site-config-card__header d-flex align-center justify-space-between flex-wrap gap-3">
                <h2 class="site-config-card__title">
                  {{ currentTitle }}
                </h2>
                <v-btn
                  v-if="saveVisible"
                  color="primary"
                  :loading="saving"
                  prepend-icon="mdi-content-save"
                  @click="saveCurrentSection"
                >
                  {{ tr('update') }}
                </v-btn>
              </div>
            <label class="site-config-field__label">{{ tr('search_provider') }}</label>
            <v-radio-group v-model="settings.search_provider" class="mt-1">
              <v-radio value="db" :label="tr('search_provider_db')" />
              <v-radio value="opensearch" :label="tr('search_provider_opensearch')" />
              <v-radio value="solr" :label="tr('search_provider_solr')" />
            </v-radio-group>
            <div class="site-config-field__hint mt-2">{{ tr('search_provider_note') }}</div>
            </div>
          </v-card>

          <!-- Login -->
          <v-card v-show="activeSection === 'login'" elevation="1" rounded="lg" class="bg-surface">
            <div class="pa-4 site-config-card-inner">
              <div class="site-config-card__header d-flex align-center justify-space-between flex-wrap gap-3">
                <h2 class="site-config-card__title">
                  {{ currentTitle }}
                </h2>
                <v-btn
                  v-if="saveVisible"
                  color="primary"
                  :loading="saving"
                  prepend-icon="mdi-content-save"
                  @click="saveCurrentSection"
                >
                  {{ tr('update') }}
                </v-btn>
              </div>
            <label class="site-config-field__label">{{ tr('password_protect_website') }}</label>
            <v-radio-group v-model="settings.site_password_protect" class="mt-1">
              <v-radio value="yes" :label="tr('require_all_users_to_login')" />
              <v-radio value="no" :label="tr('login_not_required')" />
            </v-radio-group>
            <v-row dense class="mt-4">
              <v-col cols="12" sm="6">
                <label class="site-config-field__label">{{ tr('login_timeout_in_min') }}</label>
                <v-text-field v-model="settings.login_timeout" variant="outlined" density="comfortable" type="number" hide-details />
              </v-col>
              <v-col cols="12" sm="6">
                <label class="site-config-field__label">{{ tr('min_password_length') }}</label>
                <v-text-field v-model="settings.min_password_length" variant="outlined" density="comfortable" type="number" hide-details />
              </v-col>
            </v-row>
            </div>
          </v-card>

          <!-- Analytics -->
          <v-card v-show="activeSection === 'analytics'" elevation="1" rounded="lg" class="bg-surface">
            <div class="pa-4 site-config-card-inner">
              <div class="site-config-card__header d-flex align-center justify-space-between flex-wrap gap-3">
                <h2 class="site-config-card__title">
                  {{ currentTitle }}
                </h2>
                <v-btn
                  v-if="saveVisible"
                  color="primary"
                  :loading="saving"
                  prepend-icon="mdi-content-save"
                  @click="saveCurrentSection"
                >
                  {{ tr('update') }}
                </v-btn>
              </div>
            <label class="site-config-field__label">Google Analytics UA code</label>
            <v-text-field
              v-model="settings.google_ua_code"
              variant="outlined"
              density="comfortable"
              placeholder="UA-XXXXXXXX-X"
              hide-details
            />
            <div class="site-config-field__hint">Legacy Universal Analytics property ID</div>
            </div>
          </v-card>

          <!-- Mail -->
          <v-card v-show="activeSection === 'mail'" elevation="1" rounded="lg" class="bg-surface">
            <div class="pa-4 site-config-card-inner">
              <div class="site-config-card__header d-flex align-center justify-space-between flex-wrap gap-3 mb-4">
                <h2 class="site-config-card__title">
                  {{ currentTitle }}
                </h2>
                <v-btn
                  v-if="saveVisible"
                  color="primary"
                  :loading="saving"
                  prepend-icon="mdi-content-save"
                  @click="saveCurrentSection"
                >
                  {{ tr('update') }}
                </v-btn>
              </div>            
            <div v-if="emailConfigFileExists" style="margin-top: 12px; margin-bottom: 16px;">
              <v-alert type="info" variant="tonal" class="py-2">
                <span class="d-block" v-html="editEmailHtml" />
              </v-alert>
            </div>

            <template v-else>
              <v-row dense>
                <v-col cols="12" md="6">
                  <label class="site-config-field__label">Email driver</label>
                  <v-select
                    v-model="settings.email_driver"
                    :items="[
                      { value: 'smtp', title: 'SMTP' },
                      { value: 'sendmail', title: 'Sendmail' },
                      { value: 'sendgrid', title: 'SendGrid' },
                      { value: 'microsoft_graph', title: 'Microsoft Graph' },
                    ]"
                    item-title="title"
                    item-value="value"
                    variant="outlined"
                    density="comfortable"
                    hide-details
                  />
                </v-col>
                <v-col cols="12">
                  <label class="site-config-field__label">{{ tr('select_mail_protocol') }}</label>
                  <v-radio-group v-model="settings.mail_protocol" inline class="mt-1">
                    <v-radio value="mail" :label="tr('use_php_mail')" />
                    <v-radio value="smtp" :label="tr('use_smtp')" />
                  </v-radio-group>
                </v-col>
              </v-row>

              <v-divider class="my-4" />

              <div v-show="settings.email_driver === 'smtp' || settings.email_driver === undefined || settings.email_driver === ''">
                <div class="text-body-2 font-weight-bold text-medium-emphasis mb-2">SMTP</div>
                <v-row dense>
                  <v-col cols="12" md="8">
                    <label class="site-config-field__label">{{ tr('smtp_host') }}</label>
                    <v-text-field v-model="settings.smtp_host" variant="outlined" density="comfortable" hide-details />
                  </v-col>
                  <v-col cols="12" md="4">
                    <label class="site-config-field__label">{{ tr('smtp_port') }}</label>
                    <v-text-field v-model="settings.smtp_port" variant="outlined" density="comfortable" hide-details />
                  </v-col>
                  <v-col cols="12" md="8">
                    <label class="site-config-field__label">{{ tr('smtp_user') }}</label>
                    <v-text-field v-model="settings.smtp_user" variant="outlined" density="comfortable" hide-details />
                  </v-col>
                  <v-col cols="12" md="8">
                    <label class="site-config-field__label">{{ tr('smtp_password') }}</label>
                    <v-text-field v-model="settings.smtp_pass" variant="outlined" density="comfortable" type="password" autocomplete="new-password" hide-details />
                  </v-col>
                  <v-col cols="12" md="4">
                    <label class="site-config-field__label">{{ tr('smtp_auth') }}</label>
                    <v-select
                      v-model="settings.smtp_auth"
                      :items="[{ value: '', title: 'Auto' }, { value: '1', title: tr('yes') }, { value: '0', title: tr('no') }]"
                      item-value="value"
                      item-title="title"
                      variant="outlined"
                      density="comfortable"
                      hide-details
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <label class="site-config-field__label">{{ tr('smtp_crypto') }}</label>
                    <v-select
                      v-model="settings.smtp_crypto"
                      :items="[{ value: '', title: 'None' }, { value: 'tls', title: 'TLS' }, { value: 'ssl', title: 'SSL' }]"
                      item-value="value"
                      item-title="title"
                      variant="outlined"
                      density="comfortable"
                      hide-details
                    />
                  </v-col>
                </v-row>
              </div>

              <div v-show="settings.email_driver === 'sendgrid'" class="mt-4">
                <div class="text-body-2 font-weight-bold text-medium-emphasis mb-3">SendGrid</div>
                <label class="site-config-field__label">{{ tr('sendgrid_api_key') }}</label>
                <v-text-field v-model="settings.sendgrid_api_key" variant="outlined" density="comfortable" type="password" autocomplete="new-password" hide-details />
              </div>

              <div v-show="settings.email_driver === 'microsoft_graph'" class="mt-4">
                <div class="text-body-2 font-weight-bold text-medium-emphasis mb-3">Microsoft Graph</div>
                <v-row dense>
                  <v-col cols="12">
                    <label class="site-config-field__label">{{ tr('microsoft_graph_client_id') }}</label>
                    <v-text-field v-model="settings.microsoft_graph_client_id" variant="outlined" density="comfortable" hide-details />
                  </v-col>
                  <v-col cols="12">
                    <label class="site-config-field__label">{{ tr('microsoft_graph_client_secret') }}</label>
                    <v-text-field v-model="settings.microsoft_graph_client_secret" variant="outlined" density="comfortable" type="password" autocomplete="new-password" hide-details />
                  </v-col>
                  <v-col cols="12">
                    <label class="site-config-field__label">{{ tr('microsoft_graph_tenant_id') }}</label>
                    <v-text-field v-model="settings.microsoft_graph_tenant_id" variant="outlined" density="comfortable" hide-details />
                  </v-col>
                </v-row>
              </div>

              <div v-show="settings.email_driver === 'sendmail'" class="mt-4 text-medium-emphasis text-body-2">
                Sendmail uses the server sendmail path configured in PHP.
              </div>

            </template>

            <div class="text-h6 font-weight-bold mt-8" style="margin-bottom: 28px;">{{ tr('test_email_configurations') }}</div>
            <v-progress-linear v-if="testEmailLoading" indeterminate color="primary" class="my-4" />

            <form v-show="!testEmailLoading" class="mt-3" @submit.prevent="submitTestEmail">
              <v-row dense>
                <v-col cols="12">
                  <label class="site-config-field__label d-block mb-2">{{ tr('smtp_host') }}</label>
                  <v-text-field
                    v-model="testEmailForm.smtp_host"
                    variant="outlined"
                    density="comfortable"
                    hide-details="auto"
                    autocomplete="off"
                  />
                </v-col>
                <v-col cols="12" sm="4">
                  <label class="site-config-field__label">{{ tr('smtp_port') }}</label>
                  <v-text-field
                    v-model="testEmailForm.smtp_port"
                    type="number"
                    variant="outlined"
                    density="comfortable"
                    hide-details="auto"
                    autocomplete="off"
                  />
                </v-col>
                <v-col cols="12">
                  <label class="site-config-field__label">{{ tr('test_email_use_smtp_authentication') }}</label>
                  <div class="mt-2">
                    <v-switch v-model="testEmailForm.smtp_auth" color="primary" density="comfortable" hide-details inset />
                  </div>
                </v-col>
                <v-col cols="12">
                  <label class="site-config-field__label">{{ tr('test_email_secure_connection') }}</label>
                  <v-radio-group v-model="testEmailForm.smtp_crypto" inline density="compact" class="mt-2">
                    <v-radio value="" :label="tr('smtp_crypto_none')" />
                    <v-radio value="tls" label="TLS" />
                    <v-radio value="ssl" label="SSL" />
                  </v-radio-group>
                </v-col>
                <v-col cols="12">
                  <label class="site-config-field__label">{{ tr('test_email_library') }}</label>
                  <v-select
                    v-model="testEmailForm.useragent"
                    :items="testEmailUseragents"
                    item-title="title"
                    item-value="value"
                    variant="outlined"
                    density="comfortable"
                    hide-details="auto"
                    class="mt-1"
                  />
                  <div class="site-config-field__hint">{{ tr('test_email_library_note') }}</div>
                </v-col>
                <v-col cols="12">
                  <label class="site-config-field__label">{{ tr('smtp_user') }}</label>
                  <v-text-field
                    v-model="testEmailForm.smtp_user"
                    type="email"
                    variant="outlined"
                    density="comfortable"
                    hide-details="auto"
                    autocomplete="username"
                  />
                </v-col>
                <v-col cols="12">
                  <label class="site-config-field__label">{{ tr('smtp_password') }}</label>
                  <v-text-field
                    v-model="testEmailForm.smtp_pass"
                    type="password"
                    variant="outlined"
                    density="comfortable"
                    hide-details="auto"
                    autocomplete="new-password"
                  />
                  <div class="site-config-field__hint">{{ tr('test_email_password_hint') }}</div>
                </v-col>
                <v-col cols="12">
                  <label class="site-config-field__label">{{ tr('test_email_from') }}</label>
                  <v-text-field v-model="testEmailForm.mail_from" type="email" variant="outlined" density="comfortable" hide-details="auto" />
                  <div class="site-config-field__hint">{{ tr('test_email_from_hint') }}</div>
                </v-col>
                <v-col cols="12">
                  <label class="site-config-field__label">{{ tr('test_email_to') }}</label>
                  <v-text-field
                    v-model="testEmailForm.mail_to"
                    type="email"
                    variant="outlined"
                    density="comfortable"
                    hide-details="auto"
                    :placeholder="tr('test_email_to_placeholder')"
                    autocomplete="off"
                  />
                </v-col>
                <v-col cols="12" class="mt-2">
                  <v-btn type="submit" color="primary" :loading="testEmailSending" prepend-icon="mdi-send">
                    {{ tr('test_email_send') }}
                  </v-btn>
                </v-col>
              </v-row>
            </form>

            <div v-if="testEmailFullResponse" class="mt-8">
              <div class="text-subtitle-2 font-weight-medium mb-2 mt-5 pt-5">Response</div>
              <v-textarea
                v-model="testEmailFullResponse"
                readonly
                variant="outlined"
                rows="12"
                hide-details
                class="test-email-response"
              />
            </div>
            </div>
          </v-card>

          </div>
        </div>
      </v-container>
    </v-main>
  </v-app>
</template>
