<template>
  <v-row class="admin-catalog-featured-page-row" justify="center">
    <v-col cols="12" lg="11" xl="10">
      <v-breadcrumbs :items="breadcrumbItems" class="admin-catalog-breadcrumbs px-0 pt-0">
        <template #divider>
          <v-icon icon="mdi-chevron-right" size="16" />
        </template>
      </v-breadcrumbs>

      <h1 class="text-h5 font-weight-semibold mb-6">{{ t('featured_studies', 'Featured studies') }}</h1>

      <v-alert v-if="error" type="error" density="compact" class="mb-4" closable @click:close="error = ''">
        {{ error }}
      </v-alert>

      <v-card variant="outlined" rounded="lg">
        <v-data-table
          :headers="headers"
          :items="studies"
          :loading="loading"
          item-value="id"
          density="comfortable"
          class="elevation-0"
          :items-per-page="25"
        >
          <template #item.title="{ item }">
            <a :href="editUrl(item)" class="text-primary text-decoration-none">{{ item.title }}</a>
          </template>
          <template #item.actions="{ item }">
            <v-btn
              size="small"
              variant="text"
              color="error"
              :loading="removingId === item.id"
              :disabled="removingId != null"
              @click="removeFeatured(item)"
            >
              {{ t('remove', 'Remove') }}
            </v-btn>
          </template>
          <template #no-data>
            <div class="text-body-2 text-medium-emphasis pa-4">
              {{ t('no_records_found', 'No records found') }}
            </div>
          </template>
        </v-data-table>
      </v-card>
    </v-col>
  </v-row>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useCatalogApi } from '../composables/useCatalogApi';

defineOptions({ name: 'FeaturedStudiesPage' });

const { t } = useI18n();
const { siteUrl } = useAppConfig();
const { listFeaturedStudies, updateOptions } = useCatalogApi();

const studies = ref([]);
const loading = ref(false);
const error = ref('');
const removingId = ref(null);

const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));

const headers = computed(() => [
  { title: t('repository', 'Collection'), key: 'repositoryid', sortable: true },
  { title: t('title', 'Title'), key: 'title', sortable: true },
  { title: t('country', 'Country'), key: 'nation', sortable: true },
  { title: t('year', 'Year'), key: 'year_start', sortable: true },
  { title: '', key: 'actions', sortable: false, align: 'end' },
]);

const breadcrumbItems = computed(() => [
  { title: t('home', 'Home'), href: `${siteBaseUrl.value}/admin` },
  { title: t('catalog_admin', 'Catalog admin'), href: `${siteBaseUrl.value}/admin/catalog` },
  { title: t('featured_studies', 'Featured studies'), disabled: true },
]);

function editUrl(item) {
  return `${siteBaseUrl.value}/admin/catalog/edit/${item.id}`;
}

async function load() {
  loading.value = true;
  error.value = '';
  try {
    studies.value = await listFeaturedStudies();
  } catch (e) {
    error.value = e?.message || 'Failed to load featured studies';
    studies.value = [];
  } finally {
    loading.value = false;
  }
}

async function removeFeatured(item) {
  if (!item?.id) return;
  removingId.value = item.id;
  error.value = '';
  try {
    await updateOptions(item.id, { featured: 0 });
    studies.value = studies.value.filter((s) => s.id !== item.id);
  } catch (e) {
    error.value = e?.message || 'Failed to remove featured study';
  } finally {
    removingId.value = null;
  }
}

onMounted(load);
</script>

<style scoped>
.admin-catalog-breadcrumbs {
  font-size: 0.8125rem;
  margin-bottom: 1rem;
}
.admin-catalog-breadcrumbs :deep(.v-breadcrumbs-item),
.admin-catalog-breadcrumbs :deep(.v-breadcrumbs-divider) {
  font-size: 0.8125rem;
}
</style>
