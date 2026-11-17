<template>
  <v-card class="sh-preview" border flat>
    <div class="sh-preview__bar">
      <div class="text-subtitle-2">{{ t('sh_preview', 'Preview') }}</div>
      <v-progress-circular v-if="preview.rendering" indeterminate size="14" width="2" color="primary" class="ml-1" />
      <v-spacer />
      <span class="text-caption text-medium-emphasis" :title="t('sh_zoom_help', 'The preview is shown at the real width of the device, scaled to fit.')">
        {{ deviceWidth }}px · {{ Math.round(scale * 100) }}%
      </span>
      <v-btn-toggle v-model="device" density="compact" variant="outlined" divided mandatory>
        <v-btn v-for="d in devices" :key="d.id" :value="d.id" size="small" :title="d.label">
          <v-icon :icon="d.icon" size="18" />
        </v-btn>
      </v-btn-toggle>
      <v-btn-toggle v-model="asGuest" density="compact" variant="outlined" divided mandatory>
        <v-btn :value="false" size="small" :title="t('sh_signed_in_help', 'As a signed-in user')">{{ t('sh_signed_in', 'Signed in') }}</v-btn>
        <v-btn :value="true" size="small" :title="t('sh_signed_out_help', 'As a visitor')">{{ t('sh_signed_out', 'Signed out') }}</v-btn>
      </v-btn-toggle>
      <v-btn icon="mdi-refresh" size="small" variant="text" :title="t('sh_reload', 'Reload')" @click="reload" />
    </div>
    <div ref="stage" class="sh-preview__stage">
      <div class="sh-preview__frame" :style="{ width: `${deviceWidth * scale}px`, height: `${frameHeight * scale}px` }">
        <iframe
          ref="frame"
          :key="frameKey"
          :src="src"
          :style="{ width: `${deviceWidth}px`, height: `${frameHeight}px`, transform: `scale(${scale})` }"
          :title="t('sh_preview', 'Preview')"
          @load="onLoad"
        />
      </div>
    </div>
    <div v-if="editorView === 'builder'" class="sh-preview__hint text-caption text-medium-emphasis">
      <v-icon icon="mdi-cursor-default-click-outline" size="14" />
      {{ t('sh_click_row', 'Click a row in the preview to edit it.') }}
    </div>
  </v-card>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useSiteHeaderStore } from '../composables/useSiteHeaderStore';

defineOptions({ name: 'ShPreviewPanel' });

const emit = defineEmits(['edit-row']);

const { config } = useAppConfig();
const { preview, asGuest, form, editorView, t } = useSiteHeaderStore();

const devices = [
  { id: 'desktop', icon: 'mdi-monitor', width: 1280, label: 'Desktop' },
  { id: 'tablet', icon: 'mdi-tablet', width: 768, label: 'Tablet' },
  { id: 'phone', icon: 'mdi-cellphone', width: 390, label: 'Phone' },
];
const device = ref('desktop');
const frame = ref(null);
const stage = ref(null);
const stageWidth = ref(1000);
let observer = null;
const frameKey = ref(0);
const headerHeight = ref(120);
const ready = ref(false);

const origin = window.location.origin;
const src = computed(() => (config.value?.previewBaseUrl ? `${config.value.previewBaseUrl}/header` : ''));
const deviceWidth = computed(() => devices.find((d) => d.id === device.value)?.width || 1280);
// the device's real width, scaled down to fit the panel
const scale = computed(() => Math.min(1, Math.max(0.3, (stageWidth.value - 24) / deviceWidth.value)));
// the header plus a strip of page; room for an open dropdown
const frameHeight = computed(() => Math.min(Math.max(headerHeight.value + 190, 260), 720));

function push() {
  if (!ready.value || !preview.html || !frame.value?.contentWindow) return;
  frame.value.contentWindow.postMessage(
    { type: 'sh-header', html: preview.html, editRows: editorView.value === 'builder' },
    origin
  );
}

function onMessage(e) {
  if (e.origin !== origin || e.source !== frame.value?.contentWindow || !e.data) return;
  if (e.data.type === 'sh-ready') {
    ready.value = true;
    push();
  } else if (e.data.type === 'sh-size' && Number.isFinite(e.data.height)) {
    headerHeight.value = e.data.height;
  } else if (e.data.type === 'sh-edit-row' && ['top', 'main', 'bottom'].includes(e.data.row)) {
    emit('edit-row', e.data.row);
  }
}

function onLoad() {
  // the page posts sh-ready; this covers a ready message sent before the listener saw it
  ready.value = true;
  push();
}

function reload() {
  ready.value = false;
  frameKey.value++;
}

watch(() => preview.version, push);

onMounted(() => {
  window.addEventListener('message', onMessage);
  if (window.ResizeObserver && stage.value) {
    observer = new ResizeObserver(([entry]) => {
      stageWidth.value = entry.contentRect.width + 24;
    });
    observer.observe(stage.value);
  }
});
onBeforeUnmount(() => {
  window.removeEventListener('message', onMessage);
  observer?.disconnect();
});
</script>

<style scoped>
.sh-preview__bar {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 6px;
  padding: 8px 12px;
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
.sh-preview__stage {
  background: #cfd4da;
  padding: 12px;
  overflow-x: auto;
}
.sh-preview__frame {
  margin: 0 auto;
  overflow: hidden;
  background: #fff;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.12);
}
.sh-preview__frame iframe {
  display: block;
  border: 0;
  transform-origin: 0 0;
}
.sh-preview__hint {
  padding: 6px 12px;
  border-top: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
</style>
