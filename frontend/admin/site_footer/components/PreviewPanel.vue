<template>
  <v-card class="sf-preview" border flat>
    <div class="sf-preview__bar">
      <div class="text-subtitle-2">{{ t('sf_preview', 'Preview') }}</div>
      <v-progress-circular v-if="preview.rendering" indeterminate size="14" width="2" color="primary" class="ml-1" />
      <v-spacer />
      <span
        class="text-caption text-medium-emphasis"
        :title="t('sf_zoom_help', 'The preview is shown at the real width of the device, scaled to fit.')"
      >
        {{ deviceWidth }}px · {{ Math.round(scale * 100) }}%
      </span>
      <v-btn-toggle v-model="device" density="compact" variant="outlined" divided mandatory>
        <v-btn v-for="d in devices" :key="d.id" :value="d.id" size="small" :title="d.label">
          <v-icon :icon="d.icon" size="18" />
        </v-btn>
      </v-btn-toggle>
      <v-btn icon="mdi-refresh" size="small" variant="text" :title="t('sf_reload', 'Reload')" @click="reload" />
    </div>
    <div ref="stage" class="sf-preview__stage">
      <div class="sf-preview__frame" :style="{ width: `${deviceWidth * scale}px`, height: `${frameHeight * scale}px` }">
        <iframe
          ref="frame"
          :key="frameKey"
          :src="src"
          :style="{ width: `${deviceWidth}px`, height: `${frameHeight}px`, transform: `scale(${scale})` }"
          :title="t('sf_preview', 'Preview')"
          @load="onLoad"
        />
      </div>
    </div>
  </v-card>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useSiteFooterStore } from '../composables/useSiteFooterStore';

defineOptions({ name: 'SfPreviewPanel' });

const { config } = useAppConfig();
const { preview, t } = useSiteFooterStore();

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
const footerHeight = ref(80);
const ready = ref(false);

const origin = window.location.origin;
const src = computed(() => (config.value?.previewBaseUrl ? `${config.value.previewBaseUrl}/footer` : ''));
const deviceWidth = computed(() => devices.find((d) => d.id === device.value)?.width || 1280);
const scale = computed(() => Math.min(1, Math.max(0.3, (stageWidth.value - 24) / deviceWidth.value)));
// Footer-only iframe (header/body hidden in the preview page); size to the footer.
const frameHeight = computed(() => Math.min(Math.max(footerHeight.value + 16, 64), 720));

function push() {
  if (!ready.value || !preview.html || !frame.value?.contentWindow) return;
  frame.value.contentWindow.postMessage({ type: 'sf-footer', html: preview.html }, origin);
}

function onMessage(e) {
  if (e.origin !== origin || e.source !== frame.value?.contentWindow || !e.data) return;
  if (e.data.type === 'sf-ready') {
    ready.value = true;
    push();
  } else if (e.data.type === 'sf-size' && Number.isFinite(e.data.height)) {
    footerHeight.value = e.data.height;
  }
}

function onLoad() {
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
.sf-preview__bar {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 6px;
  padding: 8px 12px;
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
.sf-preview__stage {
  background: #cfd4da;
  padding: 12px;
  overflow-x: auto;
}
.sf-preview__frame {
  margin: 0 auto;
  overflow: hidden;
  background: #fff;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.12);
}
.sf-preview__frame iframe {
  display: block;
  border: 0;
  transform-origin: 0 0;
}
</style>
