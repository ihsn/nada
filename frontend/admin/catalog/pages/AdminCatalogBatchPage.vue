<template>
  <v-row class="admin-catalog-batch-page-row" justify="center">
    <v-col cols="12" lg="11" xl="10">
      <v-breadcrumbs :items="breadcrumbItems" class="admin-catalog-breadcrumbs px-0 pt-0">
        <template #divider>
          <v-icon icon="mdi-chevron-right" size="16" />
        </template>
      </v-breadcrumbs>

      <h1 class="text-h4 font-weight-bold mb-8">{{ pageTitle }}</h1>

      <AdminCatalogBatchTools :tool="tool" :owner-repo="ownerRepo" />
    </v-col>
  </v-row>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from '@/shared/composables/useI18n';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import AdminCatalogBatchTools from '../components/AdminCatalogBatchTools.vue';

defineOptions({ name: 'AdminCatalogBatchPage' });

const props = defineProps({
  tool: { type: String, required: true },
});

const route = useRoute();
const { t } = useI18n();
const { siteUrl } = useAppConfig();

const ownerRepo = computed(() => String(route.query.owner_repo ?? '').trim());
const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));

const pageTitle = computed(() => {
  if (props.tool === 'batch-import') return t('import_ddi', 'Bulk import');
  if (props.tool === 'batch-refresh') return t('ddi_batch_refresh_title', 'Batch refresh DDI');
  if (props.tool === 'batch-generate') return t('Batch generate DDI', 'Batch generate DDI');
  return t('catalog_admin', 'Catalog admin');
});

const breadcrumbItems = computed(() => [
  {
    title: t('home', 'Home'),
    href: `${siteBaseUrl.value}/admin`,
  },
  {
    title: t('catalog_admin', 'Catalog admin'),
    href: `${siteBaseUrl.value}/admin/catalog`,
  },
  {
    title: pageTitle.value,
    disabled: true,
  },
]);
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
