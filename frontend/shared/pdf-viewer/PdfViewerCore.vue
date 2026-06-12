<template>
  <div
    class="pdf-viewer-core"
    :class="{
      'pdf-viewer-core--embedded': embedded,
      'pdf-viewer-core--failed': !!error,
    }"
  >
    <div v-if="error" class="pdf-viewer-core__error">
      <v-alert type="error" variant="tonal" class="pdf-viewer-core__error-alert">
        {{ error }}
      </v-alert>
    </div>

    <template v-else>
    <header class="pdf-viewer-core__toolbar">
      <div v-if="!embedded" class="pdf-viewer-core__title" :title="title">
        {{ title || t('pdf_preview', 'PDF preview') }}
      </div>

      <div class="pdf-viewer-core__nav">
        <v-btn
          icon="mdi-chevron-left"
          variant="text"
          size="small"
          :disabled="currentPage <= 1 || loading"
          @click="goToPage(currentPage - 1)"
        />
        <span class="pdf-viewer-core__page-indicator">
          <input
            v-model.number="pageInput"
            type="number"
            min="1"
            :max="totalPages || 1"
            class="pdf-viewer-core__page-input"
            :disabled="loading || !totalPages"
            @change="onPageInput"
            @keyup.enter="onPageInput"
          />
          <span class="pdf-viewer-core__page-total">/ {{ totalPages || '—' }}</span>
        </span>
        <v-btn
          icon="mdi-chevron-right"
          variant="text"
          size="small"
          :disabled="!totalPages || currentPage >= totalPages || loading"
          @click="goToPage(currentPage + 1)"
        />
      </div>

      <div class="pdf-viewer-core__zoom">
        <v-btn icon="mdi-magnify-minus-outline" variant="text" size="small" :disabled="loading" @click="zoomOut" />
        <span class="pdf-viewer-core__zoom-label">{{ Math.round(scale * 100) }}%</span>
        <v-btn icon="mdi-magnify-plus-outline" variant="text" size="small" :disabled="loading" @click="zoomIn" />
      </div>
    </header>

    <div v-if="pageChips.length" class="pdf-viewer-core__chips">
      <span class="pdf-viewer-core__chips-label">{{ t('Found on pages', 'Found on pages') }}:</span>
      <v-chip
        v-for="chipPage in pageChips"
        :key="chipPage"
        size="small"
        :color="chipPage === currentPage ? 'primary' : undefined"
        :variant="chipPage === currentPage ? 'flat' : 'tonal'"
        class="pdf-viewer-core__chip"
        @click="goToPage(chipPage)"
      >
        {{ chipPage }}
      </v-chip>
    </div>

    <div v-if="loading" class="pdf-viewer-core__loading">
      <v-progress-circular indeterminate color="primary" />
    </div>

    <div ref="scrollRef" class="pdf-viewer-core__canvas-wrap">
      <canvas ref="canvasRef" class="pdf-viewer-core__canvas" />
    </div>
    </template>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import { setupPdfJs } from './setupPdfJs';

defineOptions({ name: 'PdfViewerCore' });

const props = defineProps({
  streamUrl:   { type: String, required: true },
  initialPage: { type: Number, default: 1 },
  pageChips:   { type: Array, default: () => [] },
  title:       { type: String, default: '' },
  embedded:    { type: Boolean, default: false },
});

const { t } = useI18n();

const pdfjsLib = setupPdfJs();

const canvasRef = ref(null);
const scrollRef = ref(null);
const loading = ref(true);
const error = ref('');
const totalPages = ref(0);
const currentPage = ref(Math.max(1, props.initialPage || 1));
const pageInput = ref(currentPage.value);
const scale = ref(1.1);

/** @type {import('pdfjs-dist').PDFDocumentProxy|null} */
let pdfDoc = null;
let renderTask = null;
let renderGeneration = 0;

function formatLoadError(e) {
  const msg = String(e?.message || '');
  if (/unexpected server response|invalid pdf|missing pdf|404|403|500|network|failed to fetch/i.test(msg)) {
    return t(
      'pdf_load_failed',
      'This PDF could not be loaded. The file may be missing or you may not have access.'
    );
  }
  return t('pdf_load_failed_generic', 'This PDF could not be loaded.');
}

function formatRenderError(e) {
  const msg = String(e?.message || '');
  if (msg) {
    return t('pdf_render_failed', 'This page could not be displayed.');
  }
  return t('pdf_render_failed', 'This page could not be displayed.');
}

async function loadDocument() {
  loading.value = true;
  error.value = '';

  try {
    if (pdfDoc) {
      await pdfDoc.destroy();
      pdfDoc = null;
    }

    const task = pdfjsLib.getDocument({
      url: props.streamUrl,
      withCredentials: true,
    });
    pdfDoc = await task.promise;
    totalPages.value = pdfDoc.numPages;

    const startPage = Math.min(Math.max(1, props.initialPage || 1), pdfDoc.numPages);
    await goToPage(startPage);
  } catch (e) {
    error.value = formatLoadError(e);
    loading.value = false;
  }
}

async function renderPage(pageNum) {
  if (!pdfDoc || !canvasRef.value) return;

  const generation = ++renderGeneration;
  if (renderTask) {
    try {
      await renderTask.cancel();
    } catch {
      /* ignore */
    }
    renderTask = null;
  }

  const page = await pdfDoc.getPage(pageNum);
  if (generation !== renderGeneration) return;

  const viewport = page.getViewport({ scale: scale.value });
  const canvas = canvasRef.value;
  const context = canvas.getContext('2d');

  canvas.width = viewport.width;
  canvas.height = viewport.height;
  canvas.style.width = `${viewport.width}px`;
  canvas.style.height = `${viewport.height}px`;

  renderTask = page.render({ canvasContext: context, viewport });
  await renderTask.promise;
  renderTask = null;
  loading.value = false;
}

async function goToPage(pageNum) {
  if (!pdfDoc) return;
  const n = Math.min(Math.max(1, Math.round(pageNum)), pdfDoc.numPages);
  if (n === currentPage.value && !loading.value) return;

  loading.value = true;
  currentPage.value = n;
  pageInput.value = n;

  try {
    await renderPage(n);
    scrollRef.value?.scrollTo({ top: 0, behavior: 'smooth' });
  } catch (e) {
    if (e?.name !== 'RenderingCancelledException') {
      error.value = formatRenderError(e);
    }
    loading.value = false;
  }
}

function onPageInput() {
  const n = parseInt(String(pageInput.value), 10);
  if (!Number.isFinite(n)) {
    pageInput.value = currentPage.value;
    return;
  }
  goToPage(n);
}

function zoomIn() {
  scale.value = Math.min(scale.value + 0.15, 3);
  if (pdfDoc) {
    loading.value = true;
    renderPage(currentPage.value);
  }
}

function zoomOut() {
  scale.value = Math.max(scale.value - 0.15, 0.5);
  if (pdfDoc) {
    loading.value = true;
    renderPage(currentPage.value);
  }
}

watch(
  () => props.streamUrl,
  () => loadDocument()
);

watch(
  () => props.initialPage,
  (page) => {
    if (pdfDoc && Number.isFinite(page) && page >= 1) {
      goToPage(page);
    }
  }
);

onMounted(() => {
  loadDocument();
});

onUnmounted(async () => {
  renderGeneration++;
  if (renderTask) {
    try {
      await renderTask.cancel();
    } catch {
      /* ignore */
    }
  }
  if (pdfDoc) {
    await pdfDoc.destroy();
    pdfDoc = null;
  }
});
</script>

<style scoped>
.pdf-viewer-core {
  display: flex;
  flex-direction: column;
  height: 100vh;
  background: #525659;
}

.pdf-viewer-core--embedded {
  height: 100%;
  min-height: 0;
}

.pdf-viewer-core--failed {
  background: var(--catalog-surface, #fff);
}

.pdf-viewer-core--failed.pdf-viewer-core--embedded {
  background: var(--catalog-surface, #fff);
}

.pdf-viewer-core__error {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  min-height: 0;
}

.pdf-viewer-core__error-alert {
  max-width: 520px;
  width: 100%;
}

.pdf-viewer-core__toolbar {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  padding: 8px 12px;
  background: #323639;
  color: #f1f1f1;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.pdf-viewer-core__title {
  flex: 1 1 180px;
  min-width: 0;
  font-weight: 600;
  font-size: 0.95rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.pdf-viewer-core__nav,
.pdf-viewer-core__zoom {
  display: flex;
  align-items: center;
  gap: 4px;
}

.pdf-viewer-core__page-indicator {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 0.875rem;
}

.pdf-viewer-core__page-input {
  width: 52px;
  padding: 4px 6px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 4px;
  background: #1e1e1e;
  color: #fff;
  text-align: center;
}

.pdf-viewer-core__zoom-label {
  min-width: 44px;
  text-align: center;
  font-size: 0.875rem;
}

.pdf-viewer-core__chips {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  background: #3a3d40;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.pdf-viewer-core__chips-label {
  font-size: 0.8rem;
  color: rgba(255, 255, 255, 0.75);
  margin-right: 4px;
}

.pdf-viewer-core__chip {
  cursor: pointer;
}

.pdf-viewer-core__loading {
  display: flex;
  justify-content: center;
  padding: 24px;
}

.pdf-viewer-core__canvas-wrap {
  flex: 1;
  overflow: auto;
  padding: 16px;
  display: flex;
  justify-content: center;
}

.pdf-viewer-core__canvas {
  display: block;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.45);
  background: #fff;
}
</style>
