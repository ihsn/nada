<template>
  <div class="dd-files">
    <p class="text-body-2 text-medium-emphasis mb-4">{{ lbl.filesHelp }}</p>

    <input
      ref="fileInput"
      type="file"
      class="dd-files-input"
      multiple
      tabindex="-1"
      aria-hidden="true"
      @change="onFileInputChange"
    >

    <v-card
      v-if="canEdit"
      variant="outlined"
      class="dd-files-drop text-center"
      :class="{ 'dd-files-drop--active': dragDepth > 0 }"
      role="button"
      :aria-label="lbl.dropZoneHint"
      @click="openFilePicker"
      @dragenter.prevent="onDragEnter"
      @dragleave.prevent="onDragLeave"
      @dragover.prevent
      @drop.prevent="onDrop"
    >
      <v-icon size="40" color="primary" class="dd-files-drop-icon">mdi-tray-arrow-up</v-icon>
      <div class="text-body-2 font-weight-medium">{{ lbl.dropZoneHint }}</div>
      <div v-if="allowedLabel" class="text-caption text-medium-emphasis mt-3">
        {{ lbl.allowedTypes }}: {{ allowedLabel }}
      </div>
      <div class="text-caption text-medium-emphasis mt-1">
        {{ lbl.maxFileSize }}: {{ maxUploadLabel }}
      </div>
    </v-card>

    <div v-if="queue.length" class="dd-files-queue">
      <div
        v-for="item in queue"
        :key="item.key"
        class="dd-files-queue-row"
      >
        <div class="dd-files-queue-name">
          <div class="font-weight-medium text-body-2">{{ item.name }}</div>
          <div class="text-caption text-medium-emphasis">{{ formatBytes(item.sizeBytes) }}</div>
          <div v-if="item.error" class="text-caption text-error mt-1">{{ item.error }}</div>
        </div>
        <div class="dd-files-queue-status">
          <v-progress-linear
            v-if="item.status !== 'error'"
            :model-value="item.progress"
            :indeterminate="item.status === 'pending'"
            color="primary"
            height="6"
            rounded
          />
          <div class="dd-files-actions">
            <v-btn
              v-if="item.status === 'error'"
              class="dd-files-icon-btn"
              size="small"
              variant="text"
              icon
              :title="lbl.retryUpload"
              @click="retryItem(item.key)"
            >
              <v-icon size="small">mdi-refresh</v-icon>
            </v-btn>
            <v-btn
              class="dd-files-icon-btn"
              size="small"
              variant="text"
              icon
              color="error"
              :disabled="item.status === 'uploading'"
              :title="lbl.removeFromQueue"
              @click="removeQueued(item.key)"
            >
              <v-icon size="small">mdi-close</v-icon>
            </v-btn>
          </div>
        </div>
      </div>
    </div>

    <div class="dd-files-header d-flex align-center justify-space-between flex-wrap ga-2">
      <h2 class="text-subtitle-1 font-weight-medium mb-0">
        {{ lbl.uploadedFiles }}
        <span class="text-caption text-medium-emphasis font-weight-regular">{{ files.length }}</span>
      </h2>
      <v-btn
        v-if="canEdit && selected.length"
        color="error"
        size="small"
        variant="tonal"
        class="text-none"
        :loading="deleting"
        :title="lbl.deleteSelected"
        @click="batchDelete"
      >
        <v-icon start size="small">mdi-delete</v-icon>
        {{ lbl.deleteSelected }}
      </v-btn>
    </div>

    <v-data-table
      v-model="selected"
      :headers="headers"
      :items="files"
      item-value="id"
      :show-select="canEdit"
      density="compact"
      class="dd-files-table elevation-0"
      :items-per-page="-1"
      hide-default-footer
      :loading="loading && !files.length"
    >
      <template #item.filename="{ item }">
        <div class="dd-files-name-cell">
          <button
            v-if="canEdit"
            type="button"
            class="dd-files-name"
            :title="item.filename"
            @click.stop="openMeta(item)"
          >
            {{ item.filename }}
          </button>
          <div v-else class="dd-files-name-static" :title="item.filename">{{ item.filename }}</div>
          <div v-if="item.title && item.title !== item.filename" class="dd-files-title text-caption text-medium-emphasis">
            {{ item.title }}
          </div>
        </div>
      </template>
      <template #item.filesize="{ item }">
        {{ formatBytes(item.filesize) }}
      </template>
      <template #item.dctype="{ item }">
        <v-chip v-if="item.dctype_title || item.dctype" size="x-small" variant="tonal" color="primary">
          {{ item.dctype_title || item.dctype }}
        </v-chip>
        <span v-else class="text-caption text-medium-emphasis">{{ lbl.noDetails }}</span>
      </template>
      <template #item.actions="{ item }">
        <div class="dd-files-actions">
          <v-btn
            v-if="canEdit"
            class="dd-files-icon-btn"
            size="x-small"
            variant="text"
            icon
            :title="lbl.edit"
            @click="openMeta(item)"
          >
            <v-icon size="small">mdi-pencil</v-icon>
          </v-btn>
          <v-btn
            v-if="item.download_url"
            class="dd-files-icon-btn"
            size="x-small"
            variant="text"
            icon
            :title="lbl.downloadFile"
            @click="downloadFile(item)"
          >
            <v-icon size="small">mdi-download</v-icon>
          </v-btn>
          <v-btn
            v-if="canEdit"
            class="dd-files-icon-btn"
            size="x-small"
            variant="text"
            icon
            color="error"
            :title="lbl.deleteFile"
            :loading="deletingId === item.id"
            @click="confirmDeleteOne(item)"
          >
            <v-icon size="small">mdi-delete</v-icon>
          </v-btn>
        </div>
      </template>
      <template #no-data>
        <div class="text-body-2 text-medium-emphasis py-8">{{ lbl.noFiles }}</div>
      </template>
    </v-data-table>

    <v-dialog v-model="metaOpen" max-width="560" scrollable>
      <v-card>
        <v-card-title class="text-subtitle-1">{{ lbl.resourceDetails }}</v-card-title>
        <v-card-subtitle v-if="metaForm.filename" class="text-wrap">{{ metaForm.filename }}</v-card-subtitle>
        <v-card-text>
          <div class="dd-files-field">
            <label class="dd-files-label" for="dd-file-dctype">{{ lbl.resourceType }}</label>
            <v-select
              id="dd-file-dctype"
              v-model="metaForm.dctype"
              :items="dctypeItems"
              :placeholder="lbl.selectType"
              variant="outlined"
              density="comfortable"
              clearable
              hide-details="auto"
            />
          </div>
          <div class="dd-files-field">
            <label class="dd-files-label" for="dd-file-title">{{ lbl.resourceTitle }}</label>
            <v-text-field
              id="dd-file-title"
              v-model="metaForm.title"
              variant="outlined"
              density="comfortable"
              hide-details="auto"
            />
          </div>
          <div class="dd-files-field">
            <label class="dd-files-label" for="dd-file-description">{{ lbl.resourceDescription }}</label>
            <v-textarea
              id="dd-file-description"
              v-model="metaForm.description"
              variant="outlined"
              density="comfortable"
              auto-grow
              rows="3"
              hide-details="auto"
            />
          </div>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" class="text-none" @click="metaOpen = false">{{ lbl.cancel }}</v-btn>
          <v-btn color="primary" class="text-none" :loading="savingMeta" @click="saveMeta">
            {{ lbl.saveDetails }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="4500">
      {{ snackbar.text }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useDepositFilesApi } from '../composables/useDepositFilesApi';

const props = defineProps({
  canEdit: { type: Boolean, default: true },
});

const emit = defineEmits(['updated']);

const { config } = useAppConfig();
const { fetchFiles, uploadFile, saveFile, deleteFiles, allowedExtensions } = useDepositFilesApi();

const lbl = computed(() => {
  const labels = config.value?.labels || {};
  return {
    filesHelp:
      labels.filesHelp ||
      'Upload data files, questionnaires, reports, and other materials. After a file is uploaded, you can optionally add a type, title, and description.',
    dropZoneHint: labels.dropZoneHint || 'Drop files here or click to browse',
    allowedTypes: labels.allowedTypes || 'Allowed file types',
    uploadedFiles: labels.uploadedFiles || 'Uploaded files',
    edit: labels.editStep || labels.edit || 'Edit',
    resourceDetails: labels.resourceDetails || 'Resource details',
    resourceType: labels.resourceType || 'Type',
    resourceTitle: labels.resourceTitle || 'Title',
    resourceDescription: labels.resourceDescription || 'Description',
    noDetails: labels.noDetails || 'No details',
    downloadFile: labels.downloadFile || 'Download',
    deleteFile: labels.deleteFile || 'Delete',
    deleteSelected: labels.deleteSelected || 'Delete selected',
    confirmDeleteFile: labels.confirmDeleteFile || 'Delete this file?',
    confirmDeleteFiles: labels.confirmDeleteFiles || 'Delete the selected files?',
    noSelection: labels.noSelection || 'No files selected.',
    uploadFailed: labels.uploadFailed || 'Upload failed',
    fileTypeNotAllowed: labels.fileTypeNotAllowed || 'This file type is not allowed.',
    fileTooLarge: labels.fileTooLarge || 'This file is larger than the allowed maximum.',
    maxFileSize: labels.maxFileSize || 'Max file size',
    retryUpload: labels.retryUpload || 'Retry',
    removeFromQueue: labels.removeFromQueue || 'Remove',
    saveDetails: labels.saveDetails || labels.save || 'Save',
    cancel: labels.cancel || 'Cancel',
    selectType: labels.selectType || 'Select type',
    noFiles: labels.noFiles || 'No files uploaded.',
    saved: labels.saved || 'Saved',
    fileSize: labels.fileSize || 'Size',
  };
});

const loading = ref(false);
const files = ref([]);
const dctypes = ref([]);
const selected = ref([]);
const queue = ref([]);
const dragDepth = ref(0);
const pumping = ref(false);
const deleting = ref(false);
const deletingId = ref(null);
const fileInput = ref(null);
const metaOpen = ref(false);
const savingMeta = ref(false);
const metaForm = ref({
  id: 0,
  filename: '',
  dctype: '',
  title: '',
  description: '',
});
const snackbar = ref({ show: false, text: '', color: 'success' });

const allowed = computed(() => allowedExtensions());
const allowedLabel = computed(() => allowed.value.join(', '));
const maxUploadMb = computed(() => {
  const n = Number(config.value?.depositMaxUploadMb);
  return Number.isFinite(n) && n > 0 ? n : 2048;
});
const maxUploadBytes = computed(() => maxUploadMb.value * 1024 * 1024);
const maxUploadLabel = computed(() => `${maxUploadMb.value} MB`);
const dctypeItems = computed(() =>
  (dctypes.value || []).map((row) => ({
    title: row.title,
    value: row.value,
  }))
);

const headers = computed(() => [
  { title: 'Name', key: 'filename', sortable: true },
  { title: lbl.value.fileSize || 'Size', key: 'filesize', width: '88px' },
  { title: lbl.value.resourceType, key: 'dctype', width: '160px' },
  { title: '', key: 'actions', sortable: false, align: 'end', width: '120px' },
]);

function formatBytes(n) {
  const num = Number(n);
  if (!Number.isFinite(num) || num < 0) return '—';
  const u = ['B', 'KB', 'MB', 'GB'];
  let v = num;
  let i = 0;
  while (v >= 1024 && i < u.length - 1) {
    v /= 1024;
    i += 1;
  }
  return `${v < 10 && i > 0 ? v.toFixed(1) : Math.round(v)} ${u[i]}`;
}

function showSnack(text, color = 'success') {
  snackbar.value = { show: true, text, color };
}

function downloadFile(item) {
  const url = item?.download_url;
  if (url) {
    window.location.assign(url);
  }
}

function fileExt(name) {
  const parts = String(name || '').split('.');
  return parts.length > 1 ? parts.pop().toLowerCase() : '';
}

function applyFilesPayload(data) {
  files.value = Array.isArray(data?.files) ? data.files : [];
  if (Array.isArray(data?.dctypes)) {
    dctypes.value = data.dctypes;
  }
  emit('updated', files.value);
}

async function load() {
  loading.value = true;
  try {
    applyFilesPayload(await fetchFiles());
  } catch (e) {
    showSnack(String(e?.message || e), 'error');
    files.value = [];
  } finally {
    loading.value = false;
  }
}

function openFilePicker() {
  fileInput.value?.click();
}

function onDragEnter() {
  dragDepth.value += 1;
}

function onDragLeave() {
  dragDepth.value = Math.max(0, dragDepth.value - 1);
}

function onDrop(ev) {
  dragDepth.value = 0;
  if (ev.dataTransfer?.files?.length) {
    enqueueFiles(ev.dataTransfer.files);
  }
}

function onFileInputChange(ev) {
  const input = ev.target;
  if (input.files?.length) {
    enqueueFiles(input.files);
  }
  input.value = '';
}

function enqueueFiles(list) {
  if (!props.canEdit || !list?.length) return;
  const allow = allowed.value;
  const maxBytes = maxUploadBytes.value;
  const sigs = new Set(queue.value.map((p) => `${p.file.name}\0${p.file.size}\0${p.file.lastModified}`));
  const next = [...queue.value];
  let rejectedType = 0;
  let rejectedSize = 0;
  for (let i = 0; i < list.length; i += 1) {
    const file = list[i];
    if (!file || file.size === 0) continue;
    if (allow.length && !allow.includes(fileExt(file.name))) {
      rejectedType += 1;
      continue;
    }
    if (maxBytes > 0 && file.size > maxBytes) {
      rejectedSize += 1;
      continue;
    }
    const sig = `${file.name}\0${file.size}\0${file.lastModified}`;
    if (sigs.has(sig)) continue;
    sigs.add(sig);
    next.push({
      key: `${Date.now()}-${i}-${Math.random().toString(36).slice(2, 9)}`,
      file,
      name: file.name,
      sizeBytes: file.size,
      status: 'pending',
      progress: 0,
      error: '',
    });
  }
  queue.value = next;
  if (rejectedType) {
    showSnack(lbl.value.fileTypeNotAllowed, 'warning');
  } else if (rejectedSize) {
    showSnack(lbl.value.fileTooLarge, 'warning');
  }
  pumpQueue();
}

function uploadErrorMessage(err) {
  const raw = String(err?.message || err || '');
  if (raw === 'FILE_TOO_LARGE' || raw.startsWith('FILE_TOO_LARGE')) {
    return lbl.value.fileTooLarge;
  }
  if (raw === 'FILE_TYPE_NOT_ALLOWED' || raw.startsWith('FILE_TYPE_NOT_ALLOWED')) {
    return lbl.value.fileTypeNotAllowed;
  }
  return raw || lbl.value.uploadFailed;
}

function removeQueued(key) {
  queue.value = queue.value.filter((item) => item.key !== key || item.status === 'uploading');
}

function retryItem(key) {
  const item = queue.value.find((row) => row.key === key);
  if (!item) return;
  item.status = 'pending';
  item.error = '';
  item.progress = 0;
  pumpQueue();
}

async function pumpQueue() {
  if (pumping.value) return;
  pumping.value = true;
  try {
    while (true) {
      const item = queue.value.find((row) => row.status === 'pending');
      if (!item) break;
      item.status = 'uploading';
      item.progress = 0;
      try {
        await uploadFile(item.file, ({ loaded, total }) => {
          const t = total || item.sizeBytes || 1;
          item.progress = Math.min(100, Math.round((loaded / t) * 100));
        });
        queue.value = queue.value.filter((row) => row.key !== item.key);
        await load();
      } catch (e) {
        item.status = 'error';
        item.error = uploadErrorMessage(e);
        showSnack(item.error, 'error');
      }
    }
  } finally {
    pumping.value = false;
  }
}

function openMeta(item) {
  metaForm.value = {
    id: item.id,
    filename: item.filename || '',
    dctype: item.dctype || null,
    title: item.title || '',
    description: item.description || '',
  };
  metaOpen.value = true;
}

async function saveMeta() {
  if (!metaForm.value.id) return;
  savingMeta.value = true;
  try {
    const data = await saveFile(metaForm.value.id, {
      dctype: metaForm.value.dctype || '',
      title: String(metaForm.value.title || '').trim(),
      description: String(metaForm.value.description || '').trim(),
    });
    applyFilesPayload(data);
    metaOpen.value = false;
    showSnack(lbl.value.saved, 'success');
  } catch (e) {
    showSnack(String(e?.message || e), 'error');
  } finally {
    savingMeta.value = false;
  }
}

async function confirmDeleteOne(item) {
  if (!window.confirm(lbl.value.confirmDeleteFile)) return;
  deletingId.value = item.id;
  try {
    const data = await deleteFiles([item.id]);
    applyFilesPayload(data);
    selected.value = selected.value.filter((id) => id !== item.id);
    showSnack(lbl.value.saved, 'success');
  } catch (e) {
    showSnack(String(e?.message || e), 'error');
  } finally {
    deletingId.value = null;
  }
}

async function batchDelete() {
  if (!selected.value.length) {
    showSnack(lbl.value.noSelection, 'warning');
    return;
  }
  if (!window.confirm(lbl.value.confirmDeleteFiles)) return;
  deleting.value = true;
  try {
    const data = await deleteFiles([...selected.value]);
    applyFilesPayload(data);
    selected.value = [];
    showSnack(lbl.value.saved, 'success');
  } catch (e) {
    showSnack(String(e?.message || e), 'error');
  } finally {
    deleting.value = false;
  }
}

onMounted(() => {
  load();
});
</script>

<style scoped>
.dd-files-input {
  position: absolute;
  width: 0;
  height: 0;
  opacity: 0;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  clip-path: inset(50%);
  border: 0;
}
.dd-files-drop {
  cursor: pointer;
  border-style: dashed !important;
  border-width: 1px !important;
  border-color: rgba(var(--v-theme-on-surface), 0.16) !important;
  background-color: rgba(var(--v-theme-on-surface), 0.03);
  padding: 2rem 1.5rem;
  margin-bottom: 1.25rem;
  transition: background-color 0.15s ease, border-color 0.15s ease;
}
.dd-files-drop:hover,
.dd-files-drop--active {
  background-color: rgba(var(--v-theme-primary), 0.06);
  border-color: rgba(var(--v-theme-primary), 0.4) !important;
}
.dd-files-drop-icon {
  display: block;
  margin: 0 auto 0.75rem;
}
.dd-files-queue {
  margin-bottom: 1.25rem;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
  border-radius: 8px;
  overflow: hidden;
}
.dd-files-queue-row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(140px, 220px);
  gap: 12px;
  padding: 10px 12px;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08);
}
.dd-files-queue-row:last-child {
  border-bottom: 0;
}
.dd-files-header {
  margin-bottom: 8px;
}
.dd-files-table {
  width: 100%;
  max-width: 100%;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
  border-radius: 8px;
  overflow: hidden;
}
.dd-files-table :deep(.v-table__wrapper) {
  overflow-x: hidden !important;
}
.dd-files-table :deep(table) {
  table-layout: fixed;
  width: 100%;
}
.dd-files-table :deep(th),
.dd-files-table :deep(td) {
  font-size: 13px;
  overflow: hidden;
  min-width: 0;
}
.dd-files-table :deep(.v-data-table-column--align-end) {
  width: 120px;
  white-space: nowrap;
}
.dd-files-table :deep(.v-chip) {
  max-width: 100%;
}
.dd-files-table :deep(.v-chip__content) {
  overflow: hidden;
  text-overflow: ellipsis;
}
.dd-files-actions {
  display: flex;
  flex-wrap: nowrap;
  align-items: center;
  justify-content: flex-end;
  gap: 0;
  white-space: nowrap;
}
.dd-files-icon-btn {
  background: transparent !important;
  box-shadow: none !important;
  border-radius: 4px !important;
}
.dd-files-icon-btn :deep(.v-btn__overlay),
.dd-files-actions :deep(.dd-files-icon-btn .v-btn__overlay) {
  opacity: 0;
}
.dd-files-icon-btn:hover {
  background: rgba(var(--v-theme-on-surface), 0.08) !important;
}
.dd-files-header .v-btn {
  white-space: nowrap;
}
.dd-files-name-cell {
  min-width: 0;
  overflow: hidden;
}
.dd-files-title {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.dd-files-name {
  display: block;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  padding: 0;
  border: 0;
  background: none;
  font: inherit;
  font-weight: 500;
  color: rgb(var(--v-theme-primary));
  text-align: left;
  cursor: pointer;
}
.dd-files-name:hover {
  text-decoration: underline;
}
.dd-files-name-static {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-weight: 500;
}
.dd-files-field {
  margin-bottom: 16px;
}
.dd-files-field:last-child {
  margin-bottom: 0;
}
.dd-files-label {
  display: block;
  margin-bottom: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  color: rgba(var(--v-theme-on-surface), 0.87);
}
</style>
