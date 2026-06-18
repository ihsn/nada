<template>
  <v-dialog
    :model-value="modelValue"
    fullscreen
    content-class="catalog-pdf-dialog-overlay"
    @update:model-value="onDialogToggle"
  >
    <v-card class="catalog-pdf-dialog">
      <div class="catalog-pdf-dialog__header">
        <span class="catalog-pdf-dialog__title text-truncate">{{ displayTitle }}</span>
        <div class="catalog-pdf-dialog__actions">
          <v-btn
            v-if="fullViewerUrl"
            :href="fullViewerUrl"
            target="_blank"
            rel="noopener noreferrer"
            variant="text"
            size="x-small"
            density="compact"
            prepend-icon="mdi-open-in-new"
            @click.stop
          >
            {{ t('open_in_new_window', 'Open in new window') }}
          </v-btn>
          <v-btn
            icon
            variant="text"
            size="x-small"
            density="compact"
            class="catalog-pdf-dialog__close"
            :aria-label="t('cancel')"
            @click="close"
          >
            <v-icon size="18">mdi-close</v-icon>
          </v-btn>
        </div>
      </div>

      <div class="catalog-pdf-dialog__body">
        <div v-if="loading" class="catalog-pdf-dialog__loading">
          <v-progress-circular indeterminate color="primary" />
          <div class="text-caption text-medium-emphasis mt-2">{{ t('js_loading') }}</div>
        </div>

        <v-alert
          v-else-if="error"
          type="error"
          variant="tonal"
          class="ma-4"
          :text="error"
        />

        <iframe
          v-else-if="iframeUrl"
          :key="viewerKey"
          :src="iframeUrl"
          :title="displayTitle || t('pdf_preview', 'PDF preview')"
          class="catalog-pdf-dialog__iframe"
        />
      </div>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { resolvePdfViewerTitle } from '@/shared/pdf-viewer/resolvePdfViewerTitle';
import { joinSiteUrl } from '../catalogUrls';
import {
  buildPdfViewerUrlFromContext,
  resolveSemanticPdfContext,
} from '../catalogPdfViewer';

defineOptions({ name: 'CatalogDocumentPdfDialog' });

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  row:        { type: Object, default: null },
  pageHit:    { type: Object, default: null },
});

const emit = defineEmits(['update:modelValue']);

const { t } = useI18n();
const { config, siteUrl } = useAppConfig();

const loading = ref(false);
const error = ref(null);
const context = ref(null);
const displayTitle = ref('');
const viewerKey = ref(0);

function catalogApiBaseUrl() {
  const fromConfig = config.value?.apiBaseUrl;
  if (fromConfig) {
    return String(fromConfig).replace(/\/?$/, '/');
  }
  return joinSiteUrl(siteUrl.value, 'api/catalog/');
}

const iframeUrl = computed(() =>
  buildPdfViewerUrlFromContext(siteUrl.value, context.value, { embed: true })
);

const fullViewerUrl = computed(() =>
  buildPdfViewerUrlFromContext(siteUrl.value, context.value)
);

function close() {
  emit('update:modelValue', false);
}

function onDialogToggle(open) {
  if (!open) close();
}

function resetState() {
  loading.value = false;
  error.value = null;
  context.value = null;
  displayTitle.value = '';
}

async function loadViewer() {
  if (!props.row) {
    error.value = t('pdf_preview');
    return;
  }

  loading.value = true;
  error.value = null;
  context.value = null;

  try {
    const resolved = await resolveSemanticPdfContext({
      row: props.row,
      pageHit: props.pageHit,
      siteUrl: siteUrl.value,
      apiBaseUrl: catalogApiBaseUrl(),
    });

    if (!resolved) {
      error.value = t('RESOURCE_NOT_PDF_STREAMABLE');
      return;
    }

    context.value = resolved;
    viewerKey.value += 1;

    displayTitle.value = await resolvePdfViewerTitle({
      siteUrl: siteUrl.value,
      sid: resolved.sid,
      idno: resolved.idno,
      resourceId: resolved.resourceId,
    });
  } catch (e) {
    error.value = e?.message || t('pdf_preview', 'PDF preview');
  } finally {
    loading.value = false;
  }
}

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      loadViewer();
    } else {
      resetState();
    }
  }
);

watch(
  () => props.pageHit,
  () => {
    if (!props.modelValue || !props.pageHit || !context.value) return;
    const page = Number(props.pageHit.page);
    if (Number.isFinite(page) && page > 0) {
      context.value = {
        ...context.value,
        initialPage: page,
      };
      viewerKey.value += 1;
    }
  }
);
</script>

<style scoped>
:global(.catalog-pdf-dialog-overlay) {
  margin: 15px !important;
  width: calc(100vw - 30px) !important;
  height: calc(100vh - 30px) !important;
  max-width: none !important;
  max-height: none !important;
}

.catalog-pdf-dialog {
  display: flex;
  flex-direction: column;
  width: 100%;
  height: 100%;
  overflow: hidden;
}

.catalog-pdf-dialog__header {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 8px 5px 12px;
  min-height: 0;
  flex-shrink: 0;
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.catalog-pdf-dialog__title {
  flex: 1;
  min-width: 0;
  font-weight: 600;
  font-size: 0.875rem;
  line-height: 1.3;
}

.catalog-pdf-dialog__actions {
  display: flex;
  align-items: center;
  gap: 2px;
  flex-shrink: 0;
}

.catalog-pdf-dialog__close {
  margin-inline-end: -2px;
}

.catalog-pdf-dialog__body {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: #525659;
}

.catalog-pdf-dialog__loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex: 1;
  padding: 32px;
}

.catalog-pdf-dialog__iframe {
  flex: 1;
  width: 100%;
  min-height: 0;
  border: 0;
  display: block;
  background: #525659;
}
</style>
