<template>
  <v-dialog
    :model-value="modelValue"
    max-width="920"
    @update:model-value="onDialogToggle"
  >
    <v-card class="variable-detail-dialog" density="compact">
      <div class="variable-detail-dialog__header">
        <a
          v-if="fullPageUrl"
          :href="fullPageUrl"
          target="_blank"
          rel="noopener noreferrer"
          class="variable-detail-dialog__title-link"
          :title="t('open_in_new_window', 'Open in new window')"
          @click.stop
        >
          <span class="variable-detail-dialog__title-text text-truncate">{{ dialogTitle }}</span>
          <v-icon size="16" class="variable-detail-dialog__title-icon">$mdi-open-in-new</v-icon>
        </a>
        <span v-else class="variable-detail-dialog__title-text text-truncate">
          {{ dialogTitle }}
        </span>
        <v-btn
          icon
          variant="text"
          size="x-small"
          density="compact"
          class="variable-detail-dialog__close"
          :aria-label="t('cancel')"
          @click="close"
        >
          <v-icon size="18">$mdi-close</v-icon>
        </v-btn>
      </div>

      <v-divider />

      <v-card-text class="variable-detail-dialog__body">
        <div v-if="loading" class="variable-detail-dialog__loading text-center">
          <v-progress-circular indeterminate size="22" width="2" />
          <div class="text-caption text-medium-emphasis mt-2">{{ t('js_loading') }}</div>
        </div>

        <v-alert
          v-else-if="error"
          type="error"
          variant="tonal"
          class="mb-0"
          :text="error"
        />

        <div
          v-else-if="htmlContent"
          class="variable-detail-html"
          v-html="htmlContent"
        />
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import DOMPurify from 'dompurify';
import { useI18n } from '@/shared/composables/useI18n';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { joinSiteUrl } from '../catalogUrls';

defineOptions({ name: 'CatalogVariableDetailDialog' });

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  studyId:    { type: [Number, String], default: null },
  variable:   { type: Object, default: null },
});

const emit = defineEmits(['update:modelValue']);

const { t } = useI18n();
const { siteUrl } = useAppConfig();

const loading = ref(false);
const error = ref(null);
const htmlContent = ref('');

let fetchController = null;

const dialogTitle = computed(() => {
  const v = props.variable;
  if (!v) return t('variable_info');
  if (v.labl && v.name) return `${v.labl} (${v.name})`;
  return v.labl || v.name || t('variable_info');
});

const fullPageUrl = computed(() => {
  if (!props.studyId || !props.variable?.vid) return null;
  return joinSiteUrl(siteUrl.value, `catalog/${props.studyId}/variable/${props.variable.vid}`);
});

const ajaxUrl = computed(() => {
  if (!fullPageUrl.value) return null;
  const sep = fullPageUrl.value.includes('?') ? '&' : '?';
  return `${fullPageUrl.value}${sep}ajax=1`;
});

function close() {
  emit('update:modelValue', false);
}

function onDialogToggle(open) {
  if (!open) close();
}

function resetContent() {
  loading.value = false;
  error.value = null;
  htmlContent.value = '';
  if (fetchController) {
    fetchController.abort();
    fetchController = null;
  }
}

async function loadHtml() {
  if (!ajaxUrl.value) {
    error.value = t('error_invalid_parameters', 'Invalid parameters');
    return;
  }

  if (fetchController) {
    fetchController.abort();
  }
  fetchController = new AbortController();
  const { signal } = fetchController;

  loading.value = true;
  error.value = null;
  htmlContent.value = '';

  try {
    const res = await fetch(ajaxUrl.value, {
      credentials: 'same-origin',
      signal,
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    htmlContent.value = DOMPurify.sanitize(await res.text());
  } catch (err) {
    if (err.name === 'AbortError') return;
    error.value = err.message || t('error_invalid_parameters');
  } finally {
    loading.value = false;
    fetchController = null;
  }
}

watch(
  () => [props.modelValue, props.studyId, props.variable?.vid],
  ([open]) => {
    resetContent();
    if (open && props.variable?.vid) {
      loadHtml();
    }
  }
);
</script>

<style scoped>
.variable-detail-dialog {
  display: flex;
  flex-direction: column;
  max-height: min(85vh, 720px);
  overflow: hidden;
}

.variable-detail-dialog__header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 8px 6px 12px;
  min-height: 0;
  flex-shrink: 0;
}

.variable-detail-dialog__title-link {
  flex: 1;
  min-width: 0;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: inherit;
  text-decoration: none;
}

.variable-detail-dialog__title-link:hover {
  color: #1565c0;
}

.variable-detail-dialog__title-link:hover .variable-detail-dialog__title-text {
  text-decoration: underline;
}

.variable-detail-dialog__title-text {
  min-width: 0;
  font-size: 0.875rem;
  font-weight: 600;
  line-height: 1.3;
}

.variable-detail-dialog__title-icon {
  flex-shrink: 0;
  opacity: 0.55;
}

.variable-detail-dialog__title-link:hover .variable-detail-dialog__title-icon {
  opacity: 1;
}

.variable-detail-dialog__close {
  flex-shrink: 0;
  margin-inline-end: -2px;
}

.variable-detail-dialog__body {
  flex: 1 1 auto;
  min-height: 0;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 10px 12px !important;
}

.variable-detail-dialog__loading {
  padding: 16px 0;
}

.variable-detail-html {
  max-width: 100%;
  overflow-x: hidden;
}

.variable-detail-html :deep(h2) {
  font-size: 1rem;
  margin: 0 0 0.35rem;
  line-height: 1.3;
  overflow-wrap: anywhere;
}

.variable-detail-html :deep(h3),
.variable-detail-html :deep(h5) {
  font-size: 0.8125rem;
  margin: 0.65rem 0 0.35rem;
  line-height: 1.3;
}

.variable-detail-html :deep(.var-file) {
  margin: 0 0 0.5rem;
  font-size: 0.8125rem;
}

.variable-detail-html :deep(.fld-inline) {
  margin-bottom: 0.2rem;
  font-size: 0.8125rem;
  line-height: 1.35;
}

.variable-detail-html :deep(.fld-name) {
  color: rgba(26, 35, 50, 0.55);
}

.variable-detail-html :deep(a) {
  color: #1565c0;
  overflow-wrap: anywhere;
}

.variable-detail-html :deep(.variable-container) {
  margin: 0;
  max-width: 100%;
}

.variable-detail-html :deep(.xsl-subtitle) {
  margin-top: 0.5rem;
}

/* Bootstrap rows in legacy variable HTML use negative margins that overflow the dialog. */
.variable-detail-html :deep(.row) {
  margin-left: 0;
  margin-right: 0;
}

.variable-detail-html :deep(.row > [class*='col-']) {
  padding-left: 0;
  padding-right: 0;
  max-width: 100%;
}

.variable-detail-html :deep(.field),
.variable-detail-html :deep(.field-value),
.variable-detail-html :deep(.table-responsive) {
  width: 100%;
  max-width: 100%;
}

.variable-detail-html :deep(.table-responsive) {
  overflow-x: auto;
}

.variable-detail-html :deep(table),
.variable-detail-html :deep(.xsl-table) {
  width: 100%;
  max-width: 100%;
  border-collapse: collapse;
  table-layout: auto;
}

.variable-detail-html :deep(.bar-container) {
  min-width: 120px;
}

.variable-detail-html :deep(pre) {
  max-width: 100%;
  overflow-x: auto;
}
</style>
