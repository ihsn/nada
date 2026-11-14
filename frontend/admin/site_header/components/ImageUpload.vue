<template>
  <div class="sh-image-upload">
    <div class="sh-image-upload__preview" :class="{ 'sh-image-upload__preview--checker': !!shownUrl }">
      <img v-if="shownUrl" :src="shownUrl" :alt="alt" />
      <v-icon v-else icon="mdi-image-outline" size="28" color="medium-emphasis" />
    </div>
    <div class="sh-image-upload__body">
      <div class="text-body-2">
        <template v-if="url">{{ fileName }}</template>
        <template v-else>{{ emptyText }}</template>
      </div>
      <div class="text-caption text-medium-emphasis mb-2">{{ hint }}</div>
      <div class="d-flex ga-2 flex-wrap">
        <v-btn size="small" variant="tonal" color="primary" prepend-icon="mdi-upload" :loading="uploading" @click="pick">
          {{ url ? t('sh_replace', 'Replace') : t('sh_upload', 'Upload') }}
        </v-btn>
        <v-btn v-if="url" size="small" variant="text" prepend-icon="mdi-close" @click="$emit('clear')">
          {{ t('sh_remove', 'Remove') }}
        </v-btn>
      </div>
      <input ref="input" type="file" class="d-none" :accept="accept" @change="onFile" />
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useSiteHeaderStore } from '../composables/useSiteHeaderStore';

defineOptions({ name: 'ShImageUpload' });

const props = defineProps({
  kind: { type: String, required: true },
  url: { type: String, default: '' },
  fallbackUrl: { type: String, default: '' },
  alt: { type: String, default: '' },
  accept: { type: String, default: 'image/png,image/jpeg,image/gif,image/webp' },
  hint: { type: String, default: '' },
  emptyText: { type: String, default: '' },
});
const emit = defineEmits(['uploaded', 'clear']);

const { upload, t } = useSiteHeaderStore();
const input = ref(null);
const uploading = ref(false);

const shownUrl = computed(() => props.url || props.fallbackUrl);
const fileName = computed(() => decodeURIComponent(String(props.url).split('/').pop() || ''));

function pick() {
  input.value?.click();
}

async function onFile(e) {
  const file = e.target.files?.[0];
  e.target.value = '';
  if (!file) return;
  uploading.value = true;
  const result = await upload(props.kind, file);
  uploading.value = false;
  if (result?.path) {
    emit('uploaded', result);
  }
}
</script>

<style scoped>
.sh-image-upload {
  display: flex;
  gap: 16px;
  align-items: flex-start;
}
.sh-image-upload__preview {
  width: 140px;
  height: 72px;
  flex: none;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px dashed rgba(var(--v-border-color), 0.4);
  border-radius: 6px;
  overflow: hidden;
  background: rgba(var(--v-theme-on-surface), 0.03);
}
.sh-image-upload__preview--checker {
  border-style: solid;
  background-color: #fff;
  background-image: linear-gradient(45deg, #f0f0f0 25%, transparent 25%), linear-gradient(-45deg, #f0f0f0 25%, transparent 25%),
    linear-gradient(45deg, transparent 75%, #f0f0f0 75%), linear-gradient(-45deg, transparent 75%, #f0f0f0 75%);
  background-size: 12px 12px;
  background-position: 0 0, 0 6px, 6px -6px, -6px 0;
}
.sh-image-upload__preview img {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}
.sh-image-upload__body {
  min-width: 0;
  word-break: break-all;
}
</style>
