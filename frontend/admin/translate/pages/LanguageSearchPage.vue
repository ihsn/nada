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
          {{ t('translate', 'Translate') }} — {{ t('search_all_files', 'Search all files') }}
        </h1>
        <p class="text-body-2 text-medium-emphasis mb-0">
          {{ t('search_all_files_help', 'Search key, English text and translation across all files.') }}
        </p>
      </div>
      <v-btn variant="outlined" :to="{ name: 'language-edit-root', params: { lang } }">
        {{ t('edit', 'Edit') }} {{ langLabel }}
      </v-btn>
    </div>

    <v-card elevation="1" class="mb-4">
      <v-card-text class="d-flex flex-wrap ga-3 align-center">
        <v-select
          v-model="selectedLang"
          :items="languageItems"
          item-title="name"
          item-value="code"
          density="compact"
          variant="outlined"
          hide-details
          style="max-width: 180px"
          @update:model-value="onLangChange"
        />
        <v-text-field
          v-model="q"
          :placeholder="t('translate_search_placeholder', 'Search key, English text or translation')"
          density="compact"
          variant="outlined"
          hide-details
          clearable
          style="min-width: 280px; max-width: 420px"
          @keyup.enter="runSearch"
        />
        <v-checkbox
          v-model="onlyMissing"
          :label="t('show_only_missing', 'Show only missing translations')"
          density="compact"
          hide-details
        />
        <v-btn color="primary" :loading="loading" @click="runSearch">
          {{ t('search', 'Search') }}
        </v-btn>
      </v-card-text>
    </v-card>

    <v-alert v-if="error" type="error" class="mb-4" closable @click:close="error = ''">
      {{ error }}
    </v-alert>

    <v-alert v-if="!searched" type="info" variant="tonal" class="mb-4">
      {{ t('search_all_files_help', 'Enter text or tick missing-only to search.') }}
    </v-alert>

    <template v-else>
      <v-alert v-if="truncated" type="warning" variant="tonal" class="mb-3">
        {{ t('search_results_limited', 'Showing the first %d results. Refine your search to narrow them down.', limit) }}
      </v-alert>
      <v-card elevation="1">
        <div class="px-4 py-3 text-caption text-medium-emphasis">
          {{ results.length }} {{ t('search_results_count', 'results') }}
        </div>
        <v-divider />
        <v-data-table
          :headers="headers"
          :items="results"
          :loading="loading"
          :items-per-page="50"
          density="comfortable"
          class="tr-table text-body-2"
          item-value="key"
        >
          <template #item.file="{ item }">
            <router-link
              class="tr-link"
              :to="{
                name: 'language-edit',
                params: { lang, file: item.file },
                query: { q: item.key },
              }"
            >
              {{ item.file }}
            </router-link>
            <div class="tr-key text-medium-emphasis">{{ item.key }}</div>
          </template>
          <template #item.english="{ item }">
            <div class="tr-master">{{ item.english }}</div>
          </template>
          <template #item.translation="{ item }">
            <em v-if="item.missing" class="text-error">
              {{ t('search_not_translated', 'Not translated') }}
            </em>
            <div v-else class="tr-master mb-0">{{ item.translation }}</div>
          </template>
          <template #no-data>
            <div class="text-medium-emphasis py-6">
              {{ t('search_no_results', 'No matching translations found.') }}
            </div>
          </template>
        </v-data-table>
      </v-card>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useI18n } from '@/shared/composables/useI18n';
import { useTranslateApi } from '../composables/useTranslateApi';

defineOptions({ name: 'LanguageSearchPage' });

const props = defineProps({
  lang: { type: String, required: true },
});

const { t } = useI18n();
const { config } = useAppConfig();
const api = useTranslateApi();
const route = useRoute();
const router = useRouter();

const selectedLang = ref(props.lang);
const languageItems = ref([]);
const q = ref(typeof route.query.q === 'string' ? route.query.q : '');
const onlyMissing = ref(route.query.missing === '1');
const loading = ref(false);
const error = ref('');
const searched = ref(false);
const truncated = ref(false);
const limit = ref(500);
const results = ref([]);

const langLabel = computed(() => props.lang.charAt(0).toUpperCase() + props.lang.slice(1));

const breadcrumbItems = computed(() => [
  { title: t('Home', 'Home'), href: config.value.siteUrl || '/' },
  { title: t('translate', 'Translate'), to: { name: 'languages-list' } },
  { title: langLabel.value, to: { name: 'language-edit-root', params: { lang: props.lang } } },
  { title: t('search_all_files', 'Search'), disabled: true },
]);

const headers = computed(() => [
  { title: t('search_col_file_key', 'File / Key'), key: 'file', sortable: true },
  { title: 'English', key: 'english', sortable: false },
  { title: langLabel.value, key: 'translation', sortable: false },
]);

async function loadLanguages() {
  try {
    const data = await api.listLanguages();
    languageItems.value = (data.languages || []).map((l) => ({
      code: l.code,
      name: l.name,
    }));
  } catch {
    languageItems.value = [{ code: props.lang, name: langLabel.value }];
  }
}

async function runSearch() {
  loading.value = true;
  error.value = '';
  try {
    await router.replace({
      name: 'language-search',
      params: { lang: props.lang },
      query: {
        ...(q.value ? { q: q.value } : {}),
        ...(onlyMissing.value ? { missing: '1' } : {}),
      },
    });
    const data = await api.search(props.lang, {
      q: q.value,
      missing: onlyMissing.value,
    });
    searched.value = data.searched;
    results.value = data.results || [];
    truncated.value = !!data.truncated;
    limit.value = data.limit || 500;
  } catch (e) {
    error.value = e.message;
  } finally {
    loading.value = false;
  }
}

function onLangChange(code) {
  router.push({
    name: 'language-search',
    params: { lang: code },
    query: {
      ...(q.value ? { q: q.value } : {}),
      ...(onlyMissing.value ? { missing: '1' } : {}),
    },
  });
}

watch(
  () => props.lang,
  (code) => {
    selectedLang.value = code;
    if (q.value || onlyMissing.value) runSearch();
  }
);

onMounted(async () => {
  await loadLanguages();
  if (q.value || onlyMissing.value) {
    await runSearch();
  }
});
</script>
