<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="facets-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <div class="d-flex align-center mb-4">
      <v-btn icon="mdi-arrow-left" variant="text" size="small" @click="router.push('/')" />
      <h1 class="text-h5 font-weight-medium ml-2">Create Facet</h1>
    </div>

    <v-alert v-if="error" type="error" class="mb-4" density="compact" closable @click:close="error = null">
      {{ error }}
    </v-alert>

    <FacetForm :form="form" />

    <div class="d-flex justify-end ga-2" style="margin-top: 32px">
      <v-btn variant="text" @click="router.push('/')">Cancel</v-btn>
      <v-btn color="primary" variant="flat" prepend-icon="mdi-content-save" :loading="saving" @click="submit">
        Save
      </v-btn>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, inject } from 'vue';
import { useRouter } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { APP_CONFIG_KEY } from '@/shared/composables/useAppConfig';
import { useFacetsApi } from '../composables/useFacetsApi';
import FacetForm from '../components/FacetForm.vue';

const router = useRouter();
const { siteUrl } = useAppConfig();
const { saveFacet } = useFacetsApi();

const config    = inject(APP_CONFIG_KEY, window.APP_CONFIG || {});
const dataTypes = config.dataTypes || [];

const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const breadcrumbItems = computed(() => [
  { title: 'Admin',  href: `${siteBaseUrl.value}/admin` },
  { title: 'Facets', href: `${siteBaseUrl.value}/admin/facets` },
  { title: 'Create', disabled: true },
]);

function buildEmptyOptions() {
  const opts = {};
  for (const dtype of dataTypes) {
    opts[dtype] = { field: '', subfield: '', filter: '', filter_value: '' };
  }
  return opts;
}

const saving = ref(false);
const error  = ref(null);

const form = reactive({
  name:    '',
  title:   '',
  enabled: '1',
  options: buildEmptyOptions(),
});

async function submit() {
  saving.value = true;
  error.value  = null;
  try {
    await saveFacet({
      name:       form.name,
      title:      form.title,
      facet_type: 'user',
      enabled:    form.enabled,
      mappings:   form.options,
    });
    router.push('/');
  } catch (e) {
    error.value = e?.response?.data?.message || e.message || 'Save failed.';
  } finally {
    saving.value = false;
  }
}
</script>

<style scoped>
.facets-breadcrumbs {
  font-size: 0.8125rem;
  margin-bottom: 0.5rem;
}
.facets-breadcrumbs :deep(.v-breadcrumbs-item),
.facets-breadcrumbs :deep(.v-breadcrumbs-divider) {
  font-size: 0.8125rem;
}
</style>
