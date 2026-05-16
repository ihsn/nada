<template>
  <div class="catalog-results-list">
    <!-- Sort + count + page-size bar -->
    <div class="results-toolbar d-flex align-center flex-wrap mb-4" style="gap: 12px;">
      <!-- Count -->
      <div class="text-body-2 text-medium-emphasis flex-shrink-0">
        <strong class="text-high-emphasis">{{ fromNum.toLocaleString() }}–{{ toNum.toLocaleString() }}</strong>
        {{ t('of', 'of') }}
        <strong class="text-high-emphasis">{{ results.found.toLocaleString() }}</strong>
        {{ t('datasets', 'datasets') }}
      </div>

      <v-spacer />

      <!-- Sort dropdown -->
      <div class="d-flex align-center flex-shrink-0" style="gap: 8px;">
        <span class="text-caption text-medium-emphasis">{{ t('sort_results_by') }}:</span>
        <v-select
          :model-value="activeSortBy"
          :items="sortOptions"
          item-title="label"
          item-value="value"
          density="compact"
          variant="outlined"
          hide-details
          style="min-width: 130px; font-size: 0.75rem;"
          class="sort-select"
          @update:model-value="onSortSelect"
        >
          <template #append-inner>
            <v-icon
              size="16"
              class="ms-n1"
              :title="query.sort_order === 'asc' ? 'Ascending' : 'Descending'"
              style="cursor:pointer;"
              @click.stop="toggleOrder"
            >
              {{ query.sort_order === 'asc' ? 'mdi-arrow-up' : 'mdi-arrow-down' }}
            </v-icon>
          </template>
        </v-select>
      </div>

      <!-- Page size -->
      <div class="d-flex align-center flex-shrink-0" style="gap: 4px;">
        <span class="text-caption text-medium-emphasis me-1">{{ t('select_number_of_records_per_page', 'Per page') }}:</span>
        <v-btn
          v-for="size in [15, 30, 50]"
          :key="size"
          :variant="query.ps === size ? 'tonal' : 'text'"
          :color="query.ps === size ? 'primary' : 'default'"
          size="x-small"
          density="comfortable"
          class="sort-btn"
          @click="emit('page-size', size)"
        >
          {{ size }}
        </v-btn>
      </div>
    </div>

    <!-- Study cards -->
    <CatalogStudyCard
      v-for="row in results.rows"
      :key="row.id"
      :row="row"
      class="mb-3"
    />

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="d-flex justify-space-between align-center mt-6 flex-wrap" style="gap: 12px;">
      <div class="text-caption text-medium-emphasis">
        {{ t('showing_pages', 'Page') }} {{ query.page }} / {{ totalPages.toLocaleString() }}
      </div>
      <v-pagination
        :model-value="query.page"
        :length="totalPages"
        :total-visible="7"
        density="comfortable"
        rounded
        @update:model-value="onPage"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import CatalogStudyCard from './CatalogStudyCard.vue';

defineOptions({ name: 'CatalogResultsList' });

const props = defineProps({
  results: { type: Object, required: true },
  query:   { type: Object, required: true },
});
const emit = defineEmits(['sort', 'page', 'page-size']);

const { t } = useI18n();

const totalPages = computed(() =>
  props.results.found > 0 ? Math.ceil(props.results.found / props.query.ps) : 1
);

const fromNum = computed(() => (props.query.page - 1) * props.query.ps + 1);
const toNum   = computed(() =>
  Math.min(props.query.page * props.query.ps, props.results.found)
);

const activeSortBy = computed(() => props.query.sort_by || 'relevance');

const sortOptions = computed(() => [
  { value: 'relevance', label: t('Relevance', 'Relevance') },
  { value: 'title',     label: t('title',     'Title')     },
  { value: 'proddate',  label: t('year',      'Year')      },
  { value: 'nation',    label: t('country',   'Country')   },
  { value: 'popularity',label: t('popularity','Popularity')},
]);

function onSortSelect(by) {
  emit('sort', by, props.query.sort_order || 'desc');
}

function toggleOrder() {
  const newOrder = props.query.sort_order === 'asc' ? 'desc' : 'asc';
  emit('sort', activeSortBy.value, newOrder);
}

function onPage(p) {
  emit('page', p);
}
</script>

<style scoped>
.sort-select :deep(.v-field__input) {
  font-size: 0.75rem;
  padding-top: 2px;
  padding-bottom: 2px;
  min-height: unset;
}

.sort-select :deep(.v-field) {
  min-height: 28px;
}

.results-toolbar {
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
  padding-bottom: 12px;
}
</style>
