<template>
  <v-dialog
    :model-value="modelValue"
    fullscreen
    scroll-strategy="none"
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
            prepend-icon="$mdi-open-in-new"
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
            <v-icon size="18">$mdi-close</v-icon>
          </v-btn>
        </div>
      </div>

      <div class="catalog-pdf-dialog__body">
        <iframe
          v-if="iframeUrl"
          :key="iframeUrl"
          :src="iframeUrl"
          :title="displayTitle || t('pdf_preview', 'PDF preview')"
          class="catalog-pdf-dialog__iframe"
        />

        <v-alert
          v-else
          type="error"
          variant="tonal"
          class="ma-4"
          :text="t('RESOURCE_NOT_PDF_STREAMABLE')"
        />
      </div>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { buildPdfViewerUrlFromContext, semanticPdfContext } from '../catalogPdfViewer';

defineOptions({ name: 'CatalogDocumentPdfDialog' });

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  row:        { type: Object, default: null },
  pageHit:    { type: Object, default: null },
});

const emit = defineEmits(['update:modelValue']);

const { t } = useI18n();
const { siteUrl } = useAppConfig();

// the matched page names its PDF (resource) and page: nothing to look up
const context = computed(() => semanticPdfContext(props.row, props.pageHit));

const displayTitle = computed(() => context.value?.title || '');

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

.catalog-pdf-dialog__iframe {
  flex: 1;
  width: 100%;
  min-height: 0;
  border: 0;
  display: block;
  background: #525659;
}
</style>
