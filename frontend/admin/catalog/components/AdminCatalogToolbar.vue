<template>
  <div class="d-flex flex-wrap align-center justify-end catalog-toolbar-actions__inner">
    <v-menu location="bottom end">
      <template #activator="{ props: menuProps }">
        <v-btn
          v-bind="menuProps"
          color="primary"
          size="small"
          variant="tonal"
          class="text-none catalog-toolbar-btn"
          append-icon="mdi-chevron-down"
        >
          {{ t('bulk_tools', 'Bulk tools') }}
        </v-btn>
      </template>
      <v-list density="compact" class="catalog-bulk-menu">
        <v-list-item :href="bulkHref('batch-import')" :title="t('import_ddi', 'Bulk import')" />
        <v-list-item :href="bulkHref('batch-refresh')" :title="t('ddi_batch_refresh_title', 'Batch refresh DDI')" />
        <v-list-item :href="bulkHref('batch-generate')" :title="t('Batch generate DDI', 'Batch generate DDI')" />
      </v-list>
    </v-menu>
    <v-btn
      color="primary"
      size="small"
      variant="elevated"
      class="text-none catalog-toolbar-btn"
      :href="uploadUrl"
      :title="t('upload_ddi_hover', 'Upload a DDI file')"
    >
      {{ t('upload_ddi', 'Add study') }}
    </v-btn>
    <v-btn
      color="primary"
      size="small"
      variant="elevated"
      class="text-none catalog-toolbar-btn"
      :href="exportCsvUrl"
      :title="t('export_to_csv', 'Export to CSV')"
    >
      {{ t('export_to_csv', 'Export CSV') }}
    </v-btn>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from '@/shared/composables/useI18n';
import { useAppConfig } from '@/shared/composables/useAppConfig';

defineOptions({ name: 'AdminCatalogToolbar' });

const props = defineProps({
  ownerRepo: { type: String, default: '' },
});

const { t } = useI18n();
const { siteUrl } = useAppConfig();
const router = useRouter();

function actionHref(path) {
  const base = (siteUrl.value || '').replace(/\/$/, '');
  const segment = path.replace(/^\//, '');
  return `${base}/${segment}`;
}

const uploadUrl = computed(() => actionHref('admin/catalog/upload'));
const exportCsvUrl = computed(() => {
  const base = actionHref('admin/catalog/export_csv');
  const orVal = String(props.ownerRepo || '').trim();
  if (!orVal) {
    return base;
  }
  const sep = base.includes('?') ? '&' : '?';
  return `${base}${sep}owner_repo=${encodeURIComponent(orVal)}`;
});

function bulkHref(routeName) {
  const orVal = String(props.ownerRepo || '').trim();
  const query = orVal ? { owner_repo: orVal } : {};
  return router.resolve({ name: routeName, query }).href;
}
</script>

<style scoped>
.catalog-toolbar-actions__inner {
  gap: 8px;
}

/*
 * Admin layout (Bootstrap) sets global `a { color; }` / `a:hover`. v-btn with href
 * renders as <a>, which stole link colors onto primary buttons — poor contrast.
 */
.catalog-toolbar-actions__inner :deep(a.catalog-toolbar-btn.v-btn) {
  color: rgb(var(--v-theme-on-primary)) !important;
  text-decoration: none !important;
}

.catalog-toolbar-actions__inner :deep(a.catalog-toolbar-btn.v-btn:hover),
.catalog-toolbar-actions__inner :deep(a.catalog-toolbar-btn.v-btn:focus-visible) {
  color: rgb(var(--v-theme-on-primary)) !important;
  text-decoration: none !important;
}
</style>
