<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="tr-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <div class="d-flex flex-wrap align-start justify-space-between ga-3 mb-4">
      <div>
        <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-1">
          {{ t('translate', 'Translate') }}
        </h1>
        <p class="text-body-2 text-medium-emphasis mb-0">
          {{ t('base_language', 'Base language') }}: English
        </p>
      </div>
      <div class="d-flex flex-wrap ga-2">
        <v-btn variant="outlined" prepend-icon="mdi-file-import-outline" :to="{ name: 'language-import' }">
          {{ t('import_language', 'Import language') }}
        </v-btn>
        <v-btn color="primary" prepend-icon="mdi-plus" @click="createDialog.open = true">
          {{ t('create_new_language', 'Create new language') }}
        </v-btn>
      </div>
    </div>

    <v-alert v-if="loadError" type="error" class="mb-4" closable @click:close="loadError = ''">
      {{ loadError }}
    </v-alert>

    <v-card elevation="1">
      <div class="d-flex align-center ga-3 px-4 py-3 flex-wrap">
        <v-text-field
          v-model="search"
          prepend-inner-icon="mdi-magnify"
          :placeholder="t('search', 'Search')"
          variant="outlined"
          density="compact"
          hide-details
          clearable
          style="max-width: 320px"
        />
        <v-spacer />
        <span class="text-caption text-medium-emphasis">
          {{ languages.length }} {{ t('all_languages', 'languages') }}
        </span>
      </div>
      <v-divider />
      <v-data-table
        :headers="headers"
        :items="languages"
        :search="search"
        :loading="loading"
        :items-per-page="50"
        :items-per-page-options="[25, 50, 100, -1]"
        density="comfortable"
        class="tr-table text-body-2"
        item-value="code"
      >
        <template #item.name="{ item }">
          <router-link
            :to="{ name: 'language-edit-root', params: { lang: item.code } }"
            class="tr-link font-weight-medium"
          >
            {{ item.name }}
          </router-link>
        </template>
        <template #item.source="{ item }">
          <v-chip size="small" :color="sourceColor(item.source)" variant="tonal">
            {{ sourceLabel(item) }}
          </v-chip>
        </template>
        <template #item.completeness="{ item }">
          <template v-if="item.is_base">—</template>
          <div v-else-if="item.completeness" style="min-width: 160px">
            <v-progress-linear
              :model-value="item.completeness.percent"
              :color="progressColor(item.completeness.percent)"
              height="10"
              rounded
              class="mb-1"
            />
            <div class="text-caption text-medium-emphasis">
              {{ item.completeness.translated }}/{{ item.completeness.total }}
              {{ t('keys_translated', 'keys') }}
              <span v-if="item.completeness.missing_files?.length" class="text-error">
                — {{ item.completeness.missing_files.length }}
                {{ t('files_missing', 'files missing') }}
              </span>
            </div>
          </div>
        </template>
        <template #item.actions="{ item }">
          <v-tooltip :text="t('edit', 'Edit')" location="top">
            <template #activator="{ props: tip }">
              <v-btn
                v-bind="tip"
                icon
                variant="text"
                size="small"
                :to="{ name: 'language-edit-root', params: { lang: item.code } }"
              >
                <v-icon size="20" icon="mdi-pencil-outline" />
              </v-btn>
            </template>
          </v-tooltip>
          <v-tooltip :text="t('search_all_files', 'Search all files')" location="top">
            <template #activator="{ props: tip }">
              <v-btn
                v-bind="tip"
                icon
                variant="text"
                size="small"
                :to="{ name: 'language-search', params: { lang: item.code } }"
              >
                <v-icon size="20" icon="mdi-magnify" />
              </v-btn>
            </template>
          </v-tooltip>
          <v-tooltip :text="t('download', 'Download')" location="top">
            <template #activator="{ props: tip }">
              <v-btn v-bind="tip" icon variant="text" size="small" :href="api.exportUrl(item.code)">
                <v-icon size="20" icon="mdi-download-outline" />
              </v-btn>
            </template>
          </v-tooltip>
          <v-tooltip
            v-if="item.source === 'custom'"
            :text="t('delete', 'Delete')"
            location="top"
          >
            <template #activator="{ props: tip }">
              <v-btn
                v-bind="tip"
                icon
                variant="text"
                size="small"
                color="error"
                @click="openDelete(item)"
              >
                <v-icon size="20" icon="mdi-delete-outline" />
              </v-btn>
            </template>
          </v-tooltip>
        </template>
        <template #no-data>
          <div class="text-medium-emphasis py-6">{{ t('no_records_found', 'No records found') }}</div>
        </template>
      </v-data-table>
    </v-card>

    <v-dialog v-model="createDialog.open" max-width="480">
      <v-card>
        <v-card-title class="text-h6">{{ t('create_new_language', 'Create new language') }}</v-card-title>
        <v-card-text>
          <v-alert v-if="createDialog.error" type="error" class="mb-3" density="compact">
            {{ createDialog.error }}
          </v-alert>
          <v-text-field
            v-model="createDialog.name"
            :label="t('language_name', 'Language name')"
            :hint="t('language_name_hint', 'Enter a valid language code (e.g. arabic)')"
            persistent-hint
            variant="outlined"
            density="comfortable"
            autocomplete="off"
          />
        </v-card-text>
        <v-divider />
        <v-card-actions class="pa-3">
          <v-spacer />
          <v-btn variant="text" @click="createDialog.open = false">{{ t('cancel', 'Cancel') }}</v-btn>
          <v-btn color="primary" variant="flat" :loading="createDialog.loading" @click="confirmCreate">
            {{ t('create_new_language', 'Create new language') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="deleteDialog.open" max-width="460">
      <v-card>
        <v-card-title class="text-h6">{{ t('delete', 'Delete') }}</v-card-title>
        <v-card-text>
          <v-alert v-if="deleteDialog.error" type="error" class="mb-3" density="compact">
            {{ deleteDialog.error }}
          </v-alert>
          <p class="mb-0">
            {{ t('js_confirm_delete', 'Are you sure you want to delete the selected item(s)?') }}
            <strong v-if="deleteDialog.item">“{{ deleteDialog.item.name }}”</strong>
          </p>
        </v-card-text>
        <v-divider />
        <v-card-actions class="pa-3">
          <v-spacer />
          <v-btn variant="text" @click="deleteDialog.open = false">{{ t('cancel', 'Cancel') }}</v-btn>
          <v-btn color="error" variant="flat" :loading="deleteDialog.loading" @click="confirmDelete">
            {{ t('delete', 'Delete') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="toast.open" :color="toast.color" location="bottom right" :timeout="4000">
      {{ toast.message }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useI18n } from '@/shared/composables/useI18n';
import { useTranslateApi } from '../composables/useTranslateApi';
import { useFlashToast } from '../composables/useFlashToast';

defineOptions({ name: 'LanguagesListPage' });

const { t } = useI18n();
const { config } = useAppConfig();
const api = useTranslateApi();
const router = useRouter();
const { toast, showToast, consumeFlash } = useFlashToast();

const loading = ref(false);
const loadError = ref('');
const search = ref('');
const languages = ref([]);

const createDialog = reactive({ open: false, name: '', loading: false, error: '' });
const deleteDialog = reactive({ open: false, item: null, loading: false, error: '' });

const breadcrumbItems = computed(() => [
  { title: t('Home', 'Home'), href: config.value.siteUrl || '/' },
  { title: t('translate', 'Translate'), disabled: true },
]);

const headers = computed(() => [
  { title: t('Language', 'Language'), key: 'name' },
  { title: t('source', 'Source'), key: 'source', sortable: true },
  { title: t('completion', 'Completion'), key: 'completeness', sortable: false },
  { title: t('actions', 'Actions'), key: 'actions', sortable: false, class: 'tr-col-actions' },
]);

function sourceColor(source) {
  if (source === 'base') return 'secondary';
  if (source === 'official') return 'default';
  if (source === 'modified') return 'info';
  return 'warning';
}

function sourceLabel(item) {
  if (item.source === 'base') return t('base_language', 'Base language');
  if (item.source === 'official') return t('lang_official', 'Official');
  if (item.source === 'modified') return t('lang_modified', 'Modified');
  return t('lang_custom', 'Custom');
}

function progressColor(percent) {
  if (percent >= 100) return 'success';
  if (percent >= 50) return 'warning';
  return 'error';
}

async function load() {
  loading.value = true;
  loadError.value = '';
  try {
    const data = await api.listLanguages();
    languages.value = data.languages || [];
  } catch (e) {
    loadError.value = e.message;
  } finally {
    loading.value = false;
  }
}

function openDelete(item) {
  deleteDialog.item = item;
  deleteDialog.error = '';
  deleteDialog.open = true;
}

async function confirmDelete() {
  if (!deleteDialog.item) return;
  deleteDialog.loading = true;
  deleteDialog.error = '';
  try {
    await api.deleteLanguage(deleteDialog.item.code);
    deleteDialog.open = false;
    showToast(t('success', 'Deleted'));
    await load();
  } catch (e) {
    deleteDialog.error = e.message;
  } finally {
    deleteDialog.loading = false;
  }
}

async function confirmCreate() {
  createDialog.loading = true;
  createDialog.error = '';
  try {
    const data = await api.createLanguage(createDialog.name.trim());
    createDialog.open = false;
    createDialog.name = '';
    await router.push({
      name: 'language-edit-root',
      params: { lang: data.language },
      state: { flashSuccess: data.message || t('success', 'Created') },
    });
  } catch (e) {
    createDialog.error = e.message;
  } finally {
    createDialog.loading = false;
  }
}

onMounted(() => {
  consumeFlash();
  load();
});
</script>
