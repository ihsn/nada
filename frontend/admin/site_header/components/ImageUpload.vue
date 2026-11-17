<template>
  <div class="sh-image-upload" :class="{ 'sh-image-upload--empty': !url }">
    <button
      v-if="!url"
      type="button"
      class="sh-image-upload__drop"
      :disabled="uploading"
      @click="pick"
    >
      <v-progress-circular v-if="uploading" indeterminate size="22" width="2" color="primary" />
      <template v-else>
        <v-icon icon="mdi-image-plus-outline" size="26" class="sh-image-upload__drop-icon" />
        <span class="sh-image-upload__drop-title">{{ emptyText || t('sh_upload', 'Upload') }}</span>
        <span v-if="hint" class="sh-image-upload__drop-hint">{{ hint }}</span>
      </template>
    </button>

    <template v-else>
      <div class="sh-image-upload__preview sh-image-upload__preview--checker">
        <img :src="url" :alt="alt" />
      </div>
      <div class="sh-image-upload__body">
        <div class="sh-image-upload__name text-body-2 mb-2">{{ fileName }}</div>
        <div class="d-flex ga-2 flex-wrap">
          <v-btn size="small" variant="tonal" color="primary" prepend-icon="mdi-upload" :loading="uploading" @click="pick">
            {{ t('sh_replace', 'Replace') }}
          </v-btn>
          <v-btn size="small" variant="text" prepend-icon="mdi-close" :disabled="uploading" @click="$emit('clear')">
            {{ t('sh_remove', 'Remove') }}
          </v-btn>
        </div>
      </div>
    </template>

    <input ref="input" type="file" class="d-none" :accept="accept" @change="onFile" />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useSiteHeaderStore } from '../composables/useSiteHeaderStore';

defineOptions({ name: 'ShImageUpload' });

const props = defineProps({
  kind: { type: String, required: true },
  url: { type: String, default: '' },
  alt: { type: String, default: '' },
  accept: { type: String, default: 'image/png,image/jpeg,image/gif,image/webp' },
  hint: { type: String, default: '' },
  emptyText: { type: String, default: '' },
});
const emit = defineEmits(['uploaded', 'clear']);

const { upload, t } = useSiteHeaderStore();
const input = ref(null);
const uploading = ref(false);

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
  flex-direction: column;
  gap: 10px;
  align-items: stretch;
}
.sh-image-upload--empty {
  display: block;
}
.sh-image-upload__drop {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  width: 100%;
  min-height: 96px;
  padding: 16px 14px;
  border: 1px dashed #cfd4da;
  border-radius: 8px;
  background: #f7f8f9;
  color: rgba(0, 0, 0, 0.72);
  cursor: pointer;
  text-align: center;
  transition: background 0.15s ease, border-color 0.15s ease;
}
.sh-image-upload__drop:hover:not(:disabled) {
  background: #eef0f2;
  border-color: #b8bec6;
}
.sh-image-upload__drop:disabled {
  cursor: wait;
  opacity: 0.85;
}
.sh-image-upload__drop-icon {
  color: rgba(0, 0, 0, 0.55);
  margin-bottom: 2px;
}
.sh-image-upload__drop-title {
  font-size: 0.8125rem;
  font-weight: 600;
  color: rgba(0, 0, 0, 0.88);
  line-height: 1.3;
}
.sh-image-upload__drop-hint {
  max-width: 28rem;
  font-size: 0.75rem;
  line-height: 1.35;
  color: rgba(0, 0, 0, 0.52);
}
.sh-image-upload__preview {
  width: 100%;
  height: 88px;
  flex: none;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid #cfd4da;
  border-radius: 8px;
  overflow: hidden;
  background: #fff;
}
.sh-image-upload__preview--checker {
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
  flex: 1;
}
.sh-image-upload__name {
  word-break: break-all;
  font-weight: 500;
  margin-bottom: 2px;
}
</style>
