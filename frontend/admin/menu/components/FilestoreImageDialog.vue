<template>
  <v-dialog :model-value="modelValue" max-width="820" @update:model-value="emit('update:modelValue', $event)">
    <v-card>
      <v-card-title class="d-flex align-center justify-space-between pa-4">
        <span class="text-h6">Select or upload image</span>
        <v-btn icon variant="text" size="small" @click="close">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-card-title>
      <v-divider />

      <v-tabs v-model="tab" color="primary" class="px-2">
        <v-tab value="select">Select existing</v-tab>
        <v-tab value="upload">Upload new</v-tab>
      </v-tabs>

      <v-card-text class="pa-4">
        <v-window v-model="tab">
          <v-window-item value="select">
            <div class="d-flex ga-2 mb-3">
              <v-text-field
                v-model="search"
                density="compact"
                hide-details
                clearable
                placeholder="Search images"
                prepend-inner-icon="mdi-magnify"
                @keyup.enter="loadImages(1)"
              />
              <v-btn variant="tonal" @click="loadImages(1)">Search</v-btn>
              <v-btn icon variant="text" @click="loadImages(page)">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </div>

            <v-alert v-if="listError" type="error" density="compact" class="mb-3">{{ listError }}</v-alert>
            <div v-if="listLoading" class="text-center py-8">
              <v-progress-circular indeterminate color="primary" />
            </div>
            <div v-else-if="!images.length" class="text-medium-emphasis text-center py-8">
              No images found.
            </div>
            <v-row v-else dense>
              <v-col v-for="img in images" :key="img.file_name" cols="4" sm="3" md="2">
                <v-card
                  :variant="selected?.file_name === img.file_name ? 'outlined' : 'flat'"
                  :color="selected?.file_name === img.file_name ? 'primary' : undefined"
                  class="image-card"
                  @click="selected = img"
                >
                  <v-img :src="imageUrl(img)" aspect-ratio="1" cover />
                  <div class="text-caption text-truncate px-1 py-1">{{ img.file_name }}</div>
                </v-card>
              </v-col>
            </v-row>

            <div v-if="totalPages > 1" class="d-flex justify-center mt-4">
              <v-pagination v-model="page" :length="totalPages" density="compact" @update:model-value="loadImages" />
            </div>
          </v-window-item>

          <v-window-item value="upload">
            <v-file-input
              v-model="uploadFile"
              accept="image/jpeg,image/jpg,image/png,image/gif,image/bmp"
              label="Choose image"
              prepend-icon="mdi-image"
              show-size
              :error-messages="uploadError ? [uploadError] : []"
            />
            <v-progress-linear v-if="uploading" indeterminate color="primary" class="mb-3" />
            <v-btn color="primary" :disabled="!uploadFile || uploading" :loading="uploading" @click="upload">
              Upload image
            </v-btn>
          </v-window-item>
        </v-window>
      </v-card-text>

      <v-divider />
      <v-card-actions class="pa-3">
        <v-spacer />
        <v-btn variant="text" @click="close">Cancel</v-btn>
        <v-btn color="primary" variant="flat" :disabled="!selected" @click="insert">
          Insert selected image
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, computed, watch, inject } from 'vue';
import axios from 'axios';
import { APP_CONFIG_KEY, useAppConfig } from '@/shared/composables/useAppConfig';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue', 'select']);

const config = inject(APP_CONFIG_KEY, window.APP_CONFIG || {});
const { csrfToken, csrfTokenName } = useAppConfig();
const filestoreApi = computed(() => String(config.filestoreApiUrl || '').replace(/\/$/, ''));
const filesPublicUrl = computed(() => String(config.filesPublicUrl || '').replace(/\/$/, ''));

function csrfHeaders() {
  const name = csrfTokenName.value || 'ncsrf';
  const tok = csrfToken.value || '';
  return tok ? { [name]: tok } : {};
}

const tab = ref('select');
const search = ref('');
const images = ref([]);
const selected = ref(null);
const listLoading = ref(false);
const listError = ref(null);
const page = ref(1);
const total = ref(0);
const perPage = 24;
const totalPages = computed(() => Math.max(1, Math.ceil(total.value / perPage)));

const uploadFile = ref(null);
const uploadError = ref(null);
const uploading = ref(false);

function imageUrl(file) {
  const filePath = String(file.file_path || '').replace(/^\/+|\/+$/g, '');
  const path = filePath ? `${filePath}/` : '';
  return `${filesPublicUrl.value}/${path}${encodeURIComponent(file.file_name)}`;
}

async function loadImages(p = 1) {
  page.value = p;
  listLoading.value = true;
  listError.value = null;
  try {
    const { data } = await axios.get(filestoreApi.value, {
      params: {
        limit: perPage,
        offset: (p - 1) * perPage,
        filter_images: 1,
        search: search.value || undefined,
        field: 'file_name',
        sort_by: 'changed',
        sort_order: 'desc',
      },
      withCredentials: true,
    });
    if (data.status !== 'success') throw new Error(data.message || 'Failed to load images');
    images.value = data.files || [];
    total.value = Number(data.total) || 0;
  } catch (e) {
    listError.value = e?.response?.data?.message || e.message || 'Failed to load images';
    images.value = [];
  } finally {
    listLoading.value = false;
  }
}

async function upload() {
  const file = Array.isArray(uploadFile.value) ? uploadFile.value[0] : uploadFile.value;
  if (!file) return;
  uploadError.value = null;
  uploading.value = true;
  try {
    const fd = new FormData();
    fd.append('file', file);
    const { data } = await axios.post(filestoreApi.value, fd, {
      withCredentials: true,
      headers: { ...csrfHeaders() },
    });
    if (data.status !== 'success') throw new Error(data.message || 'Upload failed');
    const result = data.result || data.file || {};
    const uploaded = {
      file_name: result.file_name || file.name,
      file_path: result.file_path || '',
    };
    selected.value = uploaded;
    tab.value = 'select';
    uploadFile.value = null;
    await loadImages(1);
  } catch (e) {
    uploadError.value = e?.response?.data?.message || e.message || 'Upload failed';
  } finally {
    uploading.value = false;
  }
}

function insert() {
  if (!selected.value) return;
  emit('select', imageUrl(selected.value));
  close();
}

function close() {
  emit('update:modelValue', false);
}

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      selected.value = null;
      tab.value = 'select';
      loadImages(1);
    }
  }
);
</script>

<style scoped>
.image-card {
  cursor: pointer;
  overflow: hidden;
}
.image-card:hover {
  outline: 2px solid rgba(var(--v-theme-primary), 0.4);
}
</style>
