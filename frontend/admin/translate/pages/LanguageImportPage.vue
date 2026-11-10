<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="tr-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-4">
      {{ t('import_language', 'Import language') }}
    </h1>

    <v-card elevation="1" max-width="560">
      <v-card-text>
        <p class="text-body-2 text-medium-emphasis mb-4">
          {{
            t(
              'import_language_hint',
              'Upload a zip exported from this application. It must contain a top-level folder named after the language with JSON translation files inside.'
            )
          }}
        </p>
        <v-alert v-if="error" type="error" class="mb-3" density="compact">
          {{ error }}
          <ul v-if="skipped.length" class="mt-2 mb-0 pl-4">
            <li v-for="(s, i) in skipped" :key="i">{{ s }}</li>
          </ul>
        </v-alert>
        <v-file-input
          v-model="files"
          :label="t('import_zip_label', 'Language zip file')"
          accept=".zip,application/zip"
          variant="outlined"
          density="comfortable"
          prepend-icon="mdi-zip-box-outline"
          show-size
        />
      </v-card-text>
      <v-divider />
      <v-card-actions class="pa-3">
        <v-btn variant="text" :to="{ name: 'languages-list' }">{{ t('cancel', 'Cancel') }}</v-btn>
        <v-spacer />
        <v-btn
          color="primary"
          variant="flat"
          :loading="loading"
          :disabled="!files?.length"
          @click="submit"
        >
          {{ t('import_language', 'Import language') }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useI18n } from '@/shared/composables/useI18n';
import { useTranslateApi } from '../composables/useTranslateApi';

defineOptions({ name: 'LanguageImportPage' });

const { t } = useI18n();
const { config } = useAppConfig();
const api = useTranslateApi();
const router = useRouter();

const files = ref([]);
const loading = ref(false);
const error = ref('');
const skipped = ref([]);

const breadcrumbItems = computed(() => [
  { title: t('Home', 'Home'), href: config.value.siteUrl || '/' },
  { title: t('translate', 'Translate'), to: { name: 'languages-list' } },
  { title: t('import_language', 'Import language'), disabled: true },
]);

async function submit() {
  const file = Array.isArray(files.value) ? files.value[0] : files.value;
  if (!file) return;
  loading.value = true;
  error.value = '';
  skipped.value = [];
  try {
    const data = await api.importZip(file);
    await router.push({
      name: 'language-edit-root',
      params: { lang: data.language },
      state: { flashSuccess: data.message || t('success', 'Imported') },
    });
  } catch (e) {
    error.value = e.message;
    skipped.value = e.skipped || [];
  } finally {
    loading.value = false;
  }
}
</script>
