<template>
  <div class="catalog-results-list">
    <!-- View options + sort bar -->
    <div class="results-toolbar d-flex align-center flex-wrap mb-4" style="gap: 12px;">
      <div class="text-body-2 text-medium-emphasis flex-shrink-0">
        {{ resultsCountLabel }}
      </div>

      <v-spacer />

      <CatalogImageViewToggle
        v-if="isImageTab"
        :model-value="imageViewMode"
        @update:model-value="onImageViewChange"
      />

      <CatalogSortSelect :query="query" @sort="(by, order) => emit('sort', by, order)" />

    </div>

    <CatalogImageGallery
      v-if="isImageTab && isGalleryMode"
      :rows="results.rows"
    />

    <template v-else>
      <div
        v-for="row in results.rows"
        :key="`${row.id}-${row.featured ? 'f' : 'r'}`"
        class="study-card-wrap mb-3"
      >
        <CatalogStudyCard
          :row="row"
          :collections="collectionsForRow(row)"
          :citations="citations"
          :data-classifications="dataClassifications"
          :show-abstract="showAbstract"
          :search-keyword="query.sk"
          :class="{ 'study-card--with-debug': showDebug && results.debug && row.semantic_hit }"
        />
        <SemanticHitDebug
          v-if="showDebug && results.debug"
          :hit="row.semantic_hit"
        />
      </div>
    </template>

    <div v-if="totalPages > 1" class="d-flex justify-space-between align-center mt-6 flex-wrap" style="gap: 12px;">
      <div class="text-caption text-medium-emphasis">
        {{ t('showing_pages', 'Page %s of %s', query.page, totalPages.toLocaleString()) }}
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

    <CatalogPageSizeSelect
      :model-value="query.ps"
      @update:model-value="(size) => emit('page-size', size)"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import CatalogStudyCard from './CatalogStudyCard.vue';
import CatalogSortSelect from './CatalogSortSelect.vue';
import SemanticHitDebug from './SemanticHitDebug.vue';
import CatalogPageSizeSelect from './CatalogPageSizeSelect.vue';
import CatalogImageViewToggle from './CatalogImageViewToggle.vue';
import CatalogImageGallery from './CatalogImageGallery.vue';
import { catalogResultsRange } from '../catalogResultsRange';
import {
  isImageTab as checkImageTab,
  isImageGalleryMode,
  imageViewToggleValue,
} from '../catalogImageView';

defineOptions({ name: 'CatalogResultsList' });

const props = defineProps({
  results:              { type: Object, required: true },
  query:                { type: Object, required: true },
  relatedCollections:   { type: Object, default: () => ({}) },
  citations:            { type: Object, default: null },
  dataClassifications:  { type: Object, default: null },
  showAbstract:         { type: Boolean, default: false },
  showDebug:            { type: Boolean, default: false },
});
const emit = defineEmits(['sort', 'page', 'page-size', 'image-view']);

const { t } = useI18n();

const totalPages = computed(() =>
  props.results.found > 0 ? Math.ceil(props.results.found / props.query.ps) : 1
);

const range = computed(() => catalogResultsRange(props.results, props.query));
const fromNum = computed(() => range.value.from);
const toNum = computed(() => range.value.to);
const displayTotal = computed(() => range.value.total);

const resultsCountLabel = computed(() =>
  t(
    'results_count_studies',
    '%s–%s of %s datasets',
    fromNum.value.toLocaleString(),
    toNum.value.toLocaleString(),
    displayTotal.value.toLocaleString()
  )
);

const isImageTab = computed(() => checkImageTab(props.query.tab_type));
const isGalleryMode = computed(() => isImageGalleryMode(props.query.image_view));
const imageViewMode = computed(() => imageViewToggleValue(props.query.image_view));

function onImageViewChange(mode) {
  emit('image-view', mode);
}

function collectionsForRow(row) {
  const out = [];
  if (row.repo_title && row.repositoryid) {
    out.push({ repositoryid: row.repositoryid, title: row.repo_title, type: 'owner' });
  }
  const related = props.relatedCollections[row.id];
  if (Array.isArray(related)) {
    out.push(...related);
  }
  return out;
}

function onPage(p) {
  emit('page', p);
}
</script>

<style scoped>
.results-toolbar {
  border-bottom: 1px solid var(--catalog-border-subtle, rgba(15, 23, 42, 0.11));
  padding-bottom: 12px;
}

:deep(.study-card.study-card--with-debug) {
  border-bottom-left-radius: 0 !important;
  border-bottom-right-radius: 0 !important;
}
</style>
