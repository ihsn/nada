<template>
  <div v-if="pages.length" class="study-semantic-pages-block" @click.stop>
    <div class="study-semantic-pages-block__bar">
      <button
        type="button"
        class="study-semantic-pages-block__toggle"
        :aria-expanded="expanded"
        @click="onToggle"
      >
        <v-icon
          size="18"
          class="study-semantic-pages-block__heading-icon"
        >
          $mdi-file-pdf-box
        </v-icon>
        <span class="study-semantic-pages-block__heading">
          {{ summaryLabel }}
        </span>
        <v-icon
          :icon="expanded ? '$mdi-chevron-down' : '$mdi-chevron-right'"
          size="18"
          class="study-semantic-pages-block__chevron"
        />
      </button>
      <button
        type="button"
        class="study-semantic-pages-block__preview-link"
        @click.stop="openTopPage"
      >
        <v-icon size="14" class="study-semantic-pages-block__preview-link-icon">
          $mdi-eye-outline
        </v-icon>
        {{ `${t('preview')} ${t('download_pdf')}` }}
      </button>
    </div>

    <div v-if="expanded" class="study-semantic-pages-block__panel">
      <ul class="study-semantic-pages-block__list">
        <li
          v-for="pageHit in pages"
          :key="pageHit.page_index"
          class="study-semantic-pages-block__item"
        >
          <button
            type="button"
            class="study-semantic-pages-block__hit"
            @click="openPage(pageHit)"
          >
            <span class="study-semantic-pages-block__page">
              <v-icon size="14" class="study-semantic-pages-block__page-icon">$mdi-file-pdf-box</v-icon>
              {{ pageTitle(pageHit) }}
            </span>
            <span v-if="hitExcerpt(pageHit)" class="study-semantic-pages-block__excerpt">
              {{ hitExcerpt(pageHit) }}
            </span>
          </button>
        </li>
      </ul>
    </div>

    <CatalogDocumentPdfDialog
      v-if="pdfDialogOpen"
      v-model="pdfDialogOpen"
      :row="row"
      :page-hit="pdfPageHit"
    />
  </div>
</template>

<script setup>
import { computed, defineAsyncComponent, ref, watch } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';

const CatalogDocumentPdfDialog = defineAsyncComponent(
  () => import('./CatalogDocumentPdfDialog.vue')
);

defineOptions({ name: 'CatalogStudySemanticPages' });

const props = defineProps({
  row: { type: Object, required: true },
});

const { t } = useI18n();

const expanded = ref(false);
const pdfDialogOpen = ref(false);
const pdfPageHit = ref(null);

const pages = computed(() => {
  const list = props.row?.semantic_document_pages;
  return Array.isArray(list) ? list : [];
});

const topPageHit = computed(() => pages.value[0] ?? null);

const summaryLabel = computed(() =>
  t('semantic_pdf_match_count', 'Matched in PDF — %d page(s)', pages.value.length)
);

function pageTitle(pageHit) {
  const n = pageHit?.page;
  if (n == null || Number.isNaN(Number(n))) return t('pdf_preview');
  if (pageHit.total_pages > 0) {
    return t('pdf_page_of', 'Page %d of %d', n, pageHit.total_pages);
  }
  return t('pdf_page', 'Page %d', n);
}

function normalizeExcerpt(text) {
  return String(text ?? '').replace(/\s+/g, ' ').trim();
}

function hitExcerpt(pageHit) {
  return normalizeExcerpt(pageHit?.excerpt);
}

function openPage(pageHit) {
  pdfPageHit.value = pageHit;
  pdfDialogOpen.value = true;
}

function openTopPage() {
  if (topPageHit.value) {
    openPage(topPageHit.value);
  }
}

function onToggle() {
  expanded.value = !expanded.value;
}

watch(
  () => props.row?.id,
  () => {
    expanded.value = false;
    pdfDialogOpen.value = false;
    pdfPageHit.value = null;
  }
);
</script>

<style scoped>
.study-semantic-pages-block {
  margin-top: 0;
  padding: 10px 16px 12px;
  background: var(--study-semantic-pages-bg, #f4f6f8);
  border-top: 1px solid var(--catalog-border-subtle, rgba(15, 23, 42, 0.1));
  border-radius: 0;
}

.study-semantic-pages-block__bar {
  display: flex;
  align-items: center;
  gap: 8px;
}

.study-semantic-pages-block__toggle {
  display: flex;
  align-items: center;
  gap: 8px;
  flex: 1;
  min-width: 0;
  padding: 6px 8px;
  border: 0;
  border-radius: 6px;
  background: transparent;
  text-align: left;
  cursor: pointer;
  color: var(--catalog-text-secondary, rgba(26, 35, 50, 0.82));
  font-size: 0.875rem;
  line-height: 1.4;
  transition: background-color 0.15s ease, color 0.15s ease;
}

.study-semantic-pages-block__toggle:hover {
  color: #1565c0;
  background: rgba(255, 255, 255, 0.55);
}

.study-semantic-pages-block__heading {
  flex: 1;
  min-width: 0;
  font-weight: 600;
}

.study-semantic-pages-block__heading-icon {
  flex-shrink: 0;
  color: #c62828;
  opacity: 0.9;
}

.study-semantic-pages-block__chevron {
  flex-shrink: 0;
  opacity: 0.6;
  margin-inline-start: auto;
}

.study-semantic-pages-block__preview-link {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  flex-shrink: 0;
  border: 0;
  padding: 4px 6px;
  background: transparent;
  cursor: pointer;
  color: #1565c0;
  font-weight: 500;
  font-size: 0.75rem;
  line-height: 1.3;
  white-space: nowrap;
}

.study-semantic-pages-block__preview-link:hover {
  text-decoration: underline;
}

.study-semantic-pages-block__preview-link-icon {
  flex-shrink: 0;
  color: inherit;
}

.study-semantic-pages-block__panel {
  margin-top: 6px;
  max-height: 320px;
  overflow-y: auto;
  border: 1px solid rgba(15, 23, 42, 0.08);
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.72);
  padding: 6px;
}

.study-semantic-pages-block__list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.study-semantic-pages-block__item {
  margin: 0;
}

.study-semantic-pages-block__hit {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 4px;
  width: 100%;
  padding: 8px 10px;
  border: 1px solid var(--catalog-border-subtle, rgba(15, 23, 42, 0.1));
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.72);
  text-align: left;
  cursor: pointer;
  transition: border-color 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
}

.study-semantic-pages-block__hit:hover {
  border-color: rgba(21, 101, 192, 0.35);
  background: #fff;
  box-shadow: 0 1px 4px rgba(15, 23, 42, 0.06);
}

.study-semantic-pages-block__page {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 0.8125rem;
  font-weight: 600;
  color: #1565c0;
  line-height: 1.3;
}

.study-semantic-pages-block__page-icon {
  flex-shrink: 0;
  opacity: 0.85;
}

.study-semantic-pages-block__excerpt {
  font-size: 0.8125rem;
  line-height: 1.45;
  color: var(--catalog-text-secondary, rgba(26, 35, 50, 0.82));
  overflow-wrap: anywhere;
}
</style>
