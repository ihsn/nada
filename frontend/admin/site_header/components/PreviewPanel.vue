<template>
  <v-card class="sh-preview" border flat>
    <div class="sh-preview__bar">
      <div class="text-subtitle-2 mr-2">{{ t('sh_preview', 'Preview') }}</div>
      <v-chip size="x-small" :color="hasDraft || dirty ? 'warning' : 'success'" variant="tonal" label>
        {{ hasDraft || dirty ? t('sh_preview_draft', 'Draft, not published') : t('sh_preview_live', 'Same as the live site') }}
      </v-chip>
      <v-spacer />
      <v-btn-toggle v-model="device" density="compact" variant="outlined" divided mandatory class="mr-2">
        <v-btn v-for="d in devices" :key="d.id" :value="d.id" size="small" :title="d.label">
          <v-icon :icon="d.icon" size="18" />
        </v-btn>
      </v-btn-toggle>
      <v-btn-toggle v-model="as" density="compact" variant="outlined" divided mandatory class="mr-2">
        <v-btn value="user" size="small">{{ t('sh_signed_in', 'Signed in') }}</v-btn>
        <v-btn value="guest" size="small">{{ t('sh_signed_out', 'Signed out') }}</v-btn>
      </v-btn-toggle>
      <v-btn icon="mdi-refresh" size="small" variant="text" :title="t('sh_reload', 'Reload')" @click="bump++" />
      <v-btn icon="mdi-open-in-new" size="small" variant="text" :href="src" target="_blank" :title="t('sh_open_tab', 'Open in a new tab')" />
    </div>
    <div class="sh-preview__stage">
      <div class="sh-preview__frame" :style="{ width: frameWidth }">
        <iframe :key="src" :src="src" :title="t('sh_preview', 'Preview')" loading="lazy" />
        <div v-if="saving" class="sh-preview__saving">
          <v-progress-circular indeterminate size="22" width="2" color="primary" />
        </div>
      </div>
    </div>
  </v-card>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useSiteHeaderStore } from '../composables/useSiteHeaderStore';

defineOptions({ name: 'ShPreviewPanel' });

const { config } = useAppConfig();
const { previewVersion, saving, dirty, hasDraft, t } = useSiteHeaderStore();

const devices = [
  { id: 'desktop', icon: 'mdi-monitor', width: '100%', label: 'Desktop' },
  { id: 'tablet', icon: 'mdi-tablet', width: '768px', label: 'Tablet' },
  { id: 'phone', icon: 'mdi-cellphone', width: '390px', label: 'Phone' },
];
const device = ref('desktop');
const as = ref('user');
const bump = ref(0);

const frameWidth = computed(() => devices.find((d) => d.id === device.value)?.width || '100%');
const src = computed(() => {
  const base = config.value?.previewUrl || '';
  const q = new URLSearchParams({ v: `${previewVersion.value}.${bump.value}` });
  if (as.value === 'guest') q.set('as', 'guest');
  return `${base}?${q.toString()}`;
});
</script>

<style scoped>
.sh-preview__bar {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 4px;
  padding: 8px 12px;
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
.sh-preview__stage {
  background: #e9ecef;
  padding: 12px;
  overflow-x: auto;
}
.sh-preview__frame {
  position: relative;
  margin: 0 auto;
  max-width: 100%;
  transition: width 0.2s ease;
  background: #fff;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.12);
}
.sh-preview__frame iframe {
  display: block;
  width: 100%;
  height: 380px;
  border: 0;
}
.sh-preview__saving {
  position: absolute;
  top: 8px;
  right: 8px;
}
</style>
