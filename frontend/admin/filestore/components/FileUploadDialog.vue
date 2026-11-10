<template>
  <v-dialog :model-value="modelValue" max-width="640" :persistent="uploading" @update:model-value="close">
    <v-card>
      <v-card-title class="text-h6">Upload files</v-card-title>
      <v-card-text>
        <v-file-input
          v-model="picked"
          label="Select files"
          multiple
          chips
          variant="outlined"
          density="compact"
          prepend-icon="mdi-paperclip"
          :disabled="uploading"
          hide-details
          @update:model-value="onPick"
        />
        <v-checkbox
          v-model="overwrite"
          label="Overwrite files that already exist"
          density="compact"
          hide-details
          :disabled="uploading"
          class="mt-2"
        />
        <p class="text-caption text-medium-emphasis mt-1">
          File names are cleaned on upload: only letters, numbers, dashes and underscores are kept.
        </p>

        <v-list v-if="items.length" density="compact" class="mt-2 border rounded">
          <v-list-item v-for="(item, i) in items" :key="i">
            <v-list-item-title>{{ item.file.name }}</v-list-item-title>
            <v-list-item-subtitle>
              <span v-if="item.error" class="text-error">{{ item.error }}</span>
              <span v-else-if="item.done">Uploaded as {{ item.storedName }}</span>
              <span v-else>{{ formatFileSize(item.file.size) }}</span>
            </v-list-item-subtitle>
            <v-progress-linear
              v-if="item.uploading"
              :model-value="item.progress"
              color="primary"
              height="4"
              class="mt-1"
            />
            <template #append>
              <v-icon v-if="item.done" color="success" icon="mdi-check-circle" />
              <v-icon v-else-if="item.error" color="error" icon="mdi-alert-circle" />
              <v-btn
                v-else-if="!uploading"
                icon="mdi-close"
                size="x-small"
                variant="text"
                @click="removeItem(i)"
              />
            </template>
          </v-list-item>
        </v-list>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" :disabled="uploading" @click="close(false)">{{ anyDone ? 'Close' : 'Cancel' }}</v-btn>
        <v-btn color="primary" variant="flat" :loading="uploading" :disabled="!pending.length" @click="uploadAll">
          Upload {{ pending.length || '' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useFilestoreApi } from '../composables/useFilestoreApi';
import { formatFileSize } from '../utils/files';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue', 'uploaded']);

const { upload } = useFilestoreApi();

const picked = ref([]);
const items = ref([]);
const overwrite = ref(false);
const uploading = ref(false);

const pending = computed(() => items.value.filter((i) => !i.done));
const anyDone = computed(() => items.value.some((i) => i.done));

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      picked.value = [];
      items.value = [];
      overwrite.value = false;
    }
  }
);

function onPick(files) {
  const list = Array.isArray(files) ? files : files ? [files] : [];
  items.value = list.map((file) => ({
    file,
    uploading: false,
    progress: 0,
    done: false,
    error: '',
    storedName: '',
  }));
}

function removeItem(index) {
  items.value.splice(index, 1);
  picked.value = items.value.map((i) => i.file);
}

async function uploadAll() {
  uploading.value = true;
  let uploaded = 0;
  for (const item of pending.value) {
    item.uploading = true;
    item.error = '';
    item.progress = 0;
    try {
      const result = await upload(item.file, {
        overwrite: overwrite.value,
        onProgress: (p) => {
          item.progress = p;
        },
      });
      item.done = true;
      item.storedName = result?.file_name || item.file.name;
      uploaded += 1;
    } catch (e) {
      item.error = e.message;
    } finally {
      item.uploading = false;
    }
  }
  uploading.value = false;
  if (uploaded) emit('uploaded', uploaded);
  if (!items.value.some((i) => i.error)) close(false);
}

function close(value) {
  if (uploading.value) return;
  emit('update:modelValue', !!value);
}
</script>
