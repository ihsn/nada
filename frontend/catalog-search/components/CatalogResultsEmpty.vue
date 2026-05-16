<template>
  <div class="empty-state text-center pa-12">
    <v-icon size="72" color="grey-lighten-2" class="mb-4">mdi-text-search-variant</v-icon>
    <div class="text-h6 font-weight-medium mb-2">
      {{ keyword ? t('search_no_results_title', 'No results found') : t('no_results_found', 'No results found') }}
    </div>
    <div v-if="keyword" class="text-body-2 text-medium-emphasis mb-1">
      {{ t('search_no_keyword_hint', 'No datasets matched') }} <strong>&ldquo;{{ keyword }}&rdquo;</strong>
    </div>
    <div class="text-body-2 text-medium-emphasis mb-6">
      {{ t('search_suggestions', 'Try different keywords, or clear your filters to browse all datasets.') }}
    </div>
    <v-btn
      color="primary"
      variant="tonal"
      prepend-icon="mdi-refresh"
      @click="emit('reset')"
    >
      {{ t('reset_search') }}
    </v-btn>
  </div>
</template>

<script setup>
import { useI18n } from '@/shared/composables/useI18n';

defineOptions({ name: 'CatalogResultsEmpty' });

defineProps({
  keyword: { type: String, default: '' },
});
const emit = defineEmits(['reset']);

const { t } = useI18n();
</script>

<style scoped>
.empty-state {
  border: 1px dashed rgba(0, 0, 0, 0.12);
  border-radius: 12px;
  background: #fafafa;
}
</style>
