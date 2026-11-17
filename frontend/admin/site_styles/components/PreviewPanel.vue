<template>
  <v-card class="ss-preview" border flat>
    <div class="ss-preview__bar">
      <div class="text-subtitle-2">{{ t('ss_preview', 'Preview') }}</div>
      <v-progress-circular v-if="preview.rendering" indeterminate size="14" width="2" color="primary" class="ml-1" />
      <v-spacer />
      <span
        class="text-caption text-medium-emphasis"
        :title="t('ss_zoom_help', 'The preview is shown at the real width of the device, scaled to fit.')"
      >
        {{ deviceWidth }}px · {{ Math.round(scale * 100) }}%
      </span>
      <v-btn-toggle v-model="device" density="compact" variant="outlined" divided mandatory>
        <v-btn v-for="d in devices" :key="d.id" :value="d.id" size="small" :title="d.label">
          <v-icon :icon="d.icon" size="18" />
        </v-btn>
      </v-btn-toggle>
      <v-btn icon="mdi-refresh" size="small" variant="text" :title="t('ss_reload', 'Reload')" @click="reload" />
    </div>
    <div ref="stage" class="ss-preview__stage">
      <div class="ss-preview__frame" :style="{ width: `${deviceWidth * scale}px`, height: `${frameHeight * scale}px` }">
        <iframe
          ref="frame"
          :key="frameKey"
          :src="src"
          :style="{ width: `${deviceWidth}px`, height: `${frameHeight}px`, transform: `scale(${scale})` }"
          :title="t('ss_preview', 'Preview')"
          @load="onLoad"
        />
      </div>
    </div>
  </v-card>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useSiteStylesStore } from '../composables/useSiteStylesStore';

defineOptions({ name: 'SsPreviewPanel' });

const { config } = useAppConfig();
const { preview, t } = useSiteStylesStore();

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
const ready = ref(false);
const frameHeight = 520;

const origin = window.location.origin;
const src = computed(() => (config.value?.previewBaseUrl ? `${config.value.previewBaseUrl}/styles` : ''));
const deviceWidth = computed(() => devices.find((d) => d.id === device.value)?.width || 1280);
const scale = computed(() => Math.min(1, stageWidth.value / deviceWidth.value));

function postCss() {
  const win = frame.value?.contentWindow;
  if (!win || !ready.value) return;
  win.postMessage({ type: 'ss-styles', css: preview.css || '' }, origin);
}

function onLoad() {
  ready.value = true;
  postCss();
}

function reload() {
  ready.value = false;
  frameKey.value += 1;
}

function onMessage(e) {
  if (e.origin !== origin || e.source !== frame.value?.contentWindow || !e.data) return;
  if (e.data.type === 'ss-ready') {
    ready.value = true;
    postCss();
  }
}

function measure() {
  if (stage.value) stageWidth.value = stage.value.clientWidth || 1000;
}

watch(() => preview.version, postCss);
watch(device, () => {
  /* scale updates via computed */
});

onMounted(() => {
  window.addEventListener('message', onMessage);
  measure();
  if (window.ResizeObserver && stage.value) {
    observer = new ResizeObserver(measure);
    observer.observe(stage.value);
  }
});

onBeforeUnmount(() => {
  window.removeEventListener('message', onMessage);
  observer?.disconnect();
});
</script>

<style scoped>
.ss-preview {
  background: #fff;
  overflow: hidden;
}
.ss-preview__bar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px;
  padding: 8px 10px;
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
.ss-preview__stage {
  padding: 12px;
  background: #e8eaed;
  overflow: auto;
  min-height: 360px;
}
.ss-preview__frame {
  margin: 0 auto;
  overflow: hidden;
  background: #fff;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.12);
}
.ss-preview__frame iframe {
  border: 0;
  display: block;
  transform-origin: 0 0;
}
</style>
