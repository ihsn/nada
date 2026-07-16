<template>
  <v-dialog
    :model-value="modelValue"
    max-width="640"
    scrollable
    transition="scale-transition"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <v-card class="indicator-embed-dialog rounded-lg">
      <v-card-title class="indicator-embed-dialog__title">{{ dialogTitle }}</v-card-title>
      <v-card-text class="indicator-embed-dialog__body pa-4 pt-2">
        <v-row dense class="mb-4">
          <v-col cols="12" sm="6">
            <div class="indicator-embed-dialog__label mb-1">Width</div>
            <v-text-field
              v-model="embedWidth"
              density="compact"
              variant="outlined"
              hide-details="auto"
              autocomplete="off"
            />
          </v-col>
          <v-col cols="12" sm="6">
            <div class="indicator-embed-dialog__label mb-1">Height (px)</div>
            <v-text-field
              v-model.number="embedHeightPx"
              type="number"
              :min="EMBED_IFRAME_HEIGHT_MIN"
              :max="EMBED_IFRAME_HEIGHT_MAX"
              step="1"
              density="compact"
              variant="outlined"
              hide-details="auto"
            />
          </v-col>
        </v-row>
        <div class="indicator-embed-dialog__section-label mb-1">HTML (iframe)</div>
        <v-textarea
          :model-value="iframeHtml"
          readonly
          auto-grow
          variant="outlined"
          density="compact"
          rows="6"
          class="indicator-embed-dialog__textarea embed-code-textarea font-monospace"
        />
        <div class="d-flex flex-wrap gap-2 mt-3">
          <v-btn color="primary" variant="flat" size="x-small" rounded="lg" class="text-none" @click="onCopyIframe">
            Copy embed code
          </v-btn>
          <v-btn variant="tonal" size="x-small" rounded="lg" class="text-none" @click="onCopyUrl">
            Copy link only
          </v-btn>
        </div>
        <v-alert
          v-if="notice"
          :type="noticeIsError ? 'warning' : 'success'"
          variant="tonal"
          density="compact"
          rounded="lg"
          class="indicator-embed-dialog__notice mt-3 mb-0 py-2"
        >
          {{ notice }}
        </v-alert>
        <div class="indicator-embed-dialog__section-label mt-4 mb-1">Direct URL</div>
        <v-sheet rounded="lg" border class="indicator-embed-dialog__url-sheet pa-2 text-break">
          <a :href="embedUrl" target="_blank" rel="noopener noreferrer">{{ embedUrl }}</a>
        </v-sheet>
      </v-card-text>
      <v-card-actions class="indicator-embed-dialog__actions px-4 pb-3">
        <v-spacer />
        <v-btn variant="text" size="x-small" rounded="lg" class="text-none" @click="$emit('update:modelValue', false)">
          Close
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

defineOptions({ name: 'IndicatorEmbedDialog' });

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  embedUrl: { type: String, default: '' },
  iframeTitle: { type: String, default: 'Indicator' },
  dialogTitle: { type: String, default: 'Embed' },
});

defineEmits(['update:modelValue']);

const EMBED_IFRAME_HEIGHT_MIN = 200;
const EMBED_IFRAME_HEIGHT_MAX = 3000;
const embedWidth = ref('100%');
const embedHeightPx = ref(520);

const notice = ref('');
const noticeIsError = ref(false);
let noticeTimer = null;

function escapeHtmlAttr(s) {
  return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
}

function normalizeWidth(raw) {
  const s = String(raw ?? '').trim().slice(0, 120);
  if (!s) return '100%';
  if (/^\d+$/.test(s)) return `${s}px`;
  return s;
}

function clampHeight(n) {
  let h = Number(n);
  if (!Number.isFinite(h)) h = 520;
  return Math.min(EMBED_IFRAME_HEIGHT_MAX, Math.max(EMBED_IFRAME_HEIGHT_MIN, Math.round(h)));
}

const iframeHtml = computed(() => {
  const url = props.embedUrl;
  if (!url) return '';
  const w = normalizeWidth(embedWidth.value);
  const h = clampHeight(embedHeightPx.value);
  const style = `border:0;display:block;width:${w};height:${h}px;max-width:100%`;
  return `<iframe src="${escapeHtmlAttr(url)}" style="${escapeHtmlAttr(style)}" title="${escapeHtmlAttr(props.iframeTitle)}" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>`;
});

function showNotice(text, isError = false) {
  notice.value = text;
  noticeIsError.value = isError;
  if (noticeTimer) { clearTimeout(noticeTimer); noticeTimer = null; }
  noticeTimer = setTimeout(() => { notice.value = ''; noticeTimer = null; }, 5000);
}

async function copyText(text) {
  if (!text) return false;
  try {
    if (typeof navigator !== 'undefined' && navigator.clipboard?.writeText) {
      await navigator.clipboard.writeText(text);
      return true;
    }
  } catch { /* fall through */ }
  try {
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.setAttribute('readonly', '');
    ta.style.position = 'fixed';
    ta.style.left = '-9999px';
    document.body.appendChild(ta);
    ta.select();
    const ok = document.execCommand('copy');
    document.body.removeChild(ta);
    return ok;
  } catch { return false; }
}

async function onCopyIframe() {
  const ok = await copyText(iframeHtml.value);
  showNotice(ok ? 'Copied.' : 'Could not copy.', !ok);
}

async function onCopyUrl() {
  const ok = await copyText(props.embedUrl);
  showNotice(ok ? 'Link copied.' : 'Could not copy.', !ok);
}

watch(() => props.modelValue, (open) => {
  if (!open) {
    notice.value = '';
    if (noticeTimer) { clearTimeout(noticeTimer); noticeTimer = null; }
  }
});
</script>

<style scoped>
.indicator-embed-dialog {
  font-size: 0.8125rem;
  line-height: 1.45;
}

.indicator-embed-dialog__title {
  font-size: 0.9375rem;
  font-weight: 600;
  letter-spacing: 0.01em;
  line-height: 1.3;
  padding: 10px 16px 6px !important;
  min-height: 0 !important;
}

.indicator-embed-dialog__label {
  font-size: 0.8125rem;
  font-weight: 500;
  color: rgba(var(--v-theme-on-surface), 0.85);
}

.indicator-embed-dialog__section-label {
  font-size: 0.6875rem;
  font-weight: 500;
  letter-spacing: 0.03em;
  text-transform: uppercase;
  color: rgba(var(--v-theme-on-surface), 0.55);
}

.indicator-embed-dialog__url-sheet {
  font-size: 0.75rem;
}

.indicator-embed-dialog__url-sheet a {
  font-size: inherit;
}

.indicator-embed-dialog :deep(.v-field .v-field__input) {
  font-size: 0.8125rem;
  min-height: 34px;
}

.indicator-embed-dialog :deep(.v-messages__message) {
  font-size: 0.6875rem;
  line-height: 1.35;
}

.indicator-embed-dialog__textarea :deep(textarea),
.indicator-embed-dialog__textarea :deep(.v-field__input) {
  font-size: 0.75rem !important;
  line-height: 1.4;
  min-height: 7.5rem;
}

.indicator-embed-dialog :deep(.v-btn) {
  font-size: 0.6875rem;
  letter-spacing: 0.02em;
  min-height: 28px !important;
}

.indicator-embed-dialog__actions :deep(.v-btn) {
  font-size: 0.6875rem;
  min-height: 28px !important;
}

.indicator-embed-dialog__notice {
  font-size: 0.75rem;
  line-height: 1.35;
}
</style>
