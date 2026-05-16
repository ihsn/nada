<template>
  <v-app>
    <v-main class="catalog-study-files-vue">
      <v-overlay :model-value="loading" class="align-center justify-center" persistent>
        <v-progress-circular indeterminate size="48" />
      </v-overlay>

      <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="5000">
        {{ snackbar.text }}
      </v-snackbar>

      <input
        ref="fileInput"
        type="file"
        class="csf-file-input"
        multiple
        tabindex="-1"
        aria-hidden="true"
        @change="onFileInputChange"
      >

      <v-card
        variant="outlined"
        class="csf-drop-zone text-center"
        :class="{ 'csf-drop-zone--active': dragDepth > 0 }"
        @click="openFilePicker"
        @dragenter.prevent="onDragEnter"
        @dragleave.prevent="onDragLeave"
        @dragover.prevent="onDragOver"
        @drop.prevent="onDrop"
      >
        <v-icon size="40" color="primary" class="csf-drop-icon">mdi-tray-arrow-up</v-icon>
        <div class="text-body-2 font-weight-medium csf-drop-title">{{ lbl.drop_zone_hint }}</div>
        <div class="text-caption text-medium-emphasis csf-drop-sub">
          <span v-if="pendingFiles.length === 0">{{ lbl.queue_empty }}</span>
          <span v-else>{{ pendingFiles.length }} {{ lbl.files_queued_suffix }}</span>
        </div>
      </v-card>

      <div v-if="pendingFiles.length > 0" class="csf-queue-block">
        <div class="text-subtitle-2 csf-queue-heading">{{ lbl.upload_queue_title }}</div>
        <div class="csf-queue-table-shell">
          <v-data-table
            :headers="pendingHeaders"
            :items="pendingFiles"
            item-value="key"
            density="compact"
            class="csf-queue-table elevation-0"
            :items-per-page="-1"
            hide-default-footer
          >
            <template #item.sizeBytes="{ item }">
              {{ formatBytes(item.sizeBytes) }}
            </template>
            <template #item.actions="{ item }">
              <v-btn
                size="x-small"
                variant="text"
                color="error"
                icon="mdi-close"
                :disabled="uploading"
                :title="lbl.remove_from_queue"
                @click.stop="removePending(item.key)"
              />
            </template>
          </v-data-table>
          <div class="csf-queue-table-footer d-flex flex-wrap align-center justify-end gap-2">
            <v-btn
              size="small"
              variant="text"
              :disabled="uploading"
              @click="clearPendingQueue"
            >
              {{ lbl.clear_queue }}
            </v-btn>
            <v-btn
              color="primary"
              size="small"
              variant="flat"
              class="text-none"
              :disabled="uploading"
              :loading="uploading"
              @click="runUploadQueue"
            >
              <v-icon start size="small">mdi-cloud-upload</v-icon>
              {{ lbl.start_upload }}
            </v-btn>
          </div>
        </div>
      </div>

      <v-progress-linear
        v-if="uploading && uploadProgressTotal > 0"
        class="csf-progress"
        height="8"
        rounded
        color="primary"
        :model-value="(uploadProgressLoaded / uploadProgressTotal) * 100"
      />

      <div class="csf-server-header-block">
        <div class="csf-server-header-row d-flex flex-wrap align-center justify-space-between gap-2">
          <div class="text-subtitle-2 csf-server-heading mb-0">{{ lbl.study_folder_title || 'Files in study folder' }}</div>
          <span class="text-caption text-medium-emphasis csf-server-total">{{ lbl.total_files }} {{ total }}</span>
        </div>
        <div v-if="selected.length > 0" class="csf-server-delete-row">
          <v-btn
            color="error"
            size="small"
            variant="tonal"
            :disabled="deletingBatch"
            :loading="deletingBatch"
            @click="batchDelete"
          >
            <v-icon start size="small">mdi-delete</v-icon>
            {{ lbl.delete_selection }}
          </v-btn>
        </div>
      </div>

      <v-data-table
        v-model="selected"
        :headers="headers"
        :items="files"
        item-value="base64"
        show-select
        :item-selectable="(item) => !item?.is_ddi_locked"
        density="compact"
        class="csf-table csf-server-table elevation-0 border rounded"
        :items-per-page="-1"
        hide-default-footer
      >
        <template #item.name="{ item }">
          <div>
            <span :class="rowNameClass(item)">{{ item.name }}</span>
            <div v-if="item.relative" class="text-caption text-medium-emphasis">{{ item.relative }}</div>
          </div>
        </template>

        <template #item.resource="{ item }">
          <v-chip v-if="!item.resource" size="x-small" variant="tonal">{{ lbl.not_linked }}</v-chip>
          <v-chip v-else-if="item.resource?.ismicro" size="x-small" color="deep-purple" variant="tonal">{{ lbl.data_files }}</v-chip>
          <v-chip v-else size="x-small" color="success" variant="tonal">{{ lbl.other_resources }}</v-chip>
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex align-center flex-wrap gap-1">
            <v-btn
              v-if="managefilesEditBase"
              :href="editUrl(item)"
              size="x-small"
              variant="text"
              icon="mdi-pencil"
              :title="lbl.edit_resource"
            />
            <v-btn
              :href="downloadHref(item.base64)"
              size="x-small"
              variant="text"
              icon="mdi-download"
              :title="lbl.download"
            />
            <v-btn
              v-if="!item.is_ddi_locked"
              size="x-small"
              variant="text"
              color="error"
              icon="mdi-delete"
              :title="lbl.delete"
              :loading="deletingToken === item.base64"
              @click.prevent="confirmDeleteOne(item)"
            />
            <v-icon v-else size="small" :title="lbl.locked_delete">mdi-lock</v-icon>
          </div>
        </template>
      </v-data-table>

      <div
        class="csf-legend d-flex flex-wrap"
        role="group"
        :aria-label="lbl.legend_resource_types || 'Resource link types'"
      >
        <span class="csf-legend-item"><v-icon size="small" class="mr-1">mdi-link-off</v-icon> {{ lbl.not_linked }}</span>
        <span class="csf-legend-item"><v-icon size="small" color="deep-purple" class="mr-1">mdi-database</v-icon> {{ lbl.microdata }}</span>
        <span class="csf-legend-item"><v-icon size="small" color="success" class="mr-1">mdi-file-outline</v-icon> {{ lbl.other_resources }}</span>
      </div>
    </v-main>
  </v-app>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useStudyFilesApi } from './composables/useStudyFilesApi';

const { config } = useAppConfig();
const { fetchFiles, deleteFile, uploadFile, downloadHref } = useStudyFilesApi();

const lbl = computed(() => config.value?.labels || {});
const managefilesEditBase = computed(() => config.value?.managefilesEditBase || '');

const loading = ref(true);
const uploading = ref(false);
const uploadProgressLoaded = ref(0);
const uploadProgressTotal = ref(0);
const files = ref([]);
const total = ref(0);
const selected = ref([]);
const deletingToken = ref(null);
const deletingBatch = ref(false);

/** @type {import('vue').Ref<Array<{ key: string, file: File, name: string, sizeBytes: number }>>} */
const pendingFiles = ref([]);
const dragDepth = ref(0);

const snackbar = ref({ show: false, text: '', color: 'surface' });

const fileInput = ref(null);

const pendingHeaders = computed(() => [
  { title: lbl.value.name || 'Name', key: 'name', sortable: false },
  { title: lbl.value.size || 'Size', key: 'sizeBytes', width: '100px' },
  { title: lbl.value.actions || '', key: 'actions', sortable: false, align: 'end', width: '56px' },
]);

const headers = computed(() => [
  { title: lbl.value.name || 'Name', key: 'name', sortable: true },
  { title: lbl.value.size || 'Size', key: 'size', width: '90px' },
  { title: lbl.value.permissions || 'Perm', key: 'fileperms', width: '72px' },
  { title: lbl.value.modified || 'Modified', key: 'date', width: '160px' },
  { title: lbl.value.resource_col || 'Resource', key: 'resource', width: '120px' },
  { title: lbl.value.actions || 'Actions', key: 'actions', sortable: false, align: 'end', width: '140px' },
]);

function formatBytes(n) {
  if (n == null || Number.isNaN(n)) return '—';
  const u = ['B', 'KB', 'MB', 'GB'];
  let v = n;
  let i = 0;
  while (v >= 1024 && i < u.length - 1) {
    v /= 1024;
    i++;
  }
  return `${v < 10 && i > 0 ? v.toFixed(1) : Math.round(v)} ${u[i]}`;
}

function showSnack(text, color = 'surface') {
  snackbar.value = { show: true, text, color };
}

function rowNameClass(item) {
  const parts = ['font-weight-medium'];
  if (item.resource?.ismicro) parts.push('text-deep-purple');
  else if (item.resource) parts.push('text-success');
  else parts.push('text-medium-emphasis');
  return parts.join(' ');
}

function editUrl(item) {
  const b = item?.base64;
  const base = managefilesEditBase.value;
  if (!b || !base) {
    return base || '#';
  }
  // Query param avoids path splitting when base64 contains "/" or proxies decode %2F.
  const join = base.includes('?') ? '&' : '?';
  return `${base}${join}t=${encodeURIComponent(b)}`;
}

function addFilesFromFileList(list) {
  if (!list || !list.length) return;
  const sigs = new Set(
    pendingFiles.value.map((p) => `${p.file.name}\0${p.file.size}\0${p.file.lastModified}`)
  );
  const next = [...pendingFiles.value];
  for (let i = 0; i < list.length; i++) {
    const file = list[i];
    if (!file || file.size === 0) continue;
    const sig = `${file.name}\0${file.size}\0${file.lastModified}`;
    if (sigs.has(sig)) continue;
    sigs.add(sig);
    next.push({
      key: `${Date.now()}-${i}-${Math.random().toString(36).slice(2, 11)}`,
      file,
      name: file.name,
      sizeBytes: file.size,
    });
  }
  pendingFiles.value = next;
}

function removePending(key) {
  pendingFiles.value = pendingFiles.value.filter((p) => p.key !== key);
}

function clearPendingQueue() {
  pendingFiles.value = [];
}

function onDragEnter() {
  dragDepth.value += 1;
}

function onDragLeave() {
  dragDepth.value = Math.max(0, dragDepth.value - 1);
}

function onDragOver() {
  /* allow drop */
}

function onDrop(ev) {
  dragDepth.value = 0;
  const dt = ev.dataTransfer;
  if (dt?.files?.length) {
    addFilesFromFileList(dt.files);
  }
}

async function load() {
  loading.value = true;
  try {
    const data = await fetchFiles();
    files.value = Array.isArray(data.files) ? data.files : [];
    total.value = typeof data.total === 'number' ? data.total : files.value.length;
  } catch (e) {
    showSnack(String(e?.message || e), 'error');
    files.value = [];
    total.value = 0;
  } finally {
    loading.value = false;
  }
}

function openFilePicker() {
  fileInput.value?.click();
}

function onFileInputChange(ev) {
  const input = ev.target;
  const list = input.files;
  if (list?.length) {
    addFilesFromFileList(list);
  }
  input.value = '';
}

async function runUploadQueue() {
  const queue = [...pendingFiles.value];
  if (!queue.length) return;

  uploading.value = true;
  let sum = 0;
  for (let i = 0; i < queue.length; i++) {
    sum += queue[i].file.size || 0;
  }
  uploadProgressTotal.value = sum || 1;
  uploadProgressLoaded.value = 0;

  let failedAt = -1;
  let errMsg = '';
  let doneBytes = 0;

  try {
    for (let i = 0; i < queue.length; i++) {
      const entry = queue[i];
      const f = entry.file;
      const fileStart = doneBytes;
      try {
        await uploadFile(f, ({ loaded, total }) => {
          const t = total || f.size || 1;
          uploadProgressLoaded.value = fileStart + Math.min(loaded, t);
        });
        doneBytes += f.size || 0;
        uploadProgressLoaded.value = doneBytes;
      } catch (e) {
        failedAt = i;
        errMsg = String(e?.message || e);
        break;
      }
    }

    if (failedAt === -1) {
      pendingFiles.value = [];
      showSnack(lbl.value.saved || 'OK', 'success');
      await load();
    } else {
      pendingFiles.value = queue.slice(failedAt);
      showSnack(errMsg || lbl.value.upload_failed || 'Upload failed', 'error');
      await load();
    }
  } catch (e) {
    showSnack(String(e?.message || e), 'error');
    await load();
  } finally {
    uploading.value = false;
    uploadProgressTotal.value = 0;
    uploadProgressLoaded.value = 0;
  }
}

function confirmDeleteOne(item) {
  if (item.is_ddi_locked) return;
  if (!window.confirm(lbl.value.confirm_delete || 'Delete?')) return;
  deleteOne(item.base64);
}

async function deleteOne(token) {
  deletingToken.value = token;
  try {
    await deleteFile(token);
    showSnack(lbl.value.saved || 'Deleted', 'success');
    selected.value = selected.value.filter((t) => t !== token);
    await load();
  } catch (e) {
    const msg = String(e?.message || e);
    showSnack(msg, 'error');
  } finally {
    deletingToken.value = null;
  }
}

async function batchDelete() {
  if (selected.value.length === 0) {
    showSnack(lbl.value.no_selection || 'Nothing selected', 'warning');
    return;
  }
  if (!window.confirm(lbl.value.confirm_batch_delete || 'Delete selected?')) return;
  deletingBatch.value = true;
  try {
    const tokens = [...selected.value];
    for (const t of tokens) {
      const row = files.value.find((f) => f.base64 === t);
      if (row?.is_ddi_locked) continue;
      await deleteFile(t);
    }
    selected.value = [];
    await load();
    showSnack(lbl.value.saved || 'Done', 'success');
  } catch (e) {
    showSnack(String(e?.message || e), 'error');
    await load();
  } finally {
    deletingBatch.value = false;
  }
}

onMounted(() => {
  load();
});
</script>

<style scoped>
.catalog-study-files-vue {
  padding: 2rem 2rem 2.75rem;
}

/* Hidden file input: programmatic .click() from drop zone; not visible or tabbable */
.csf-file-input {
  position: absolute;
  width: 0;
  height: 0;
  opacity: 0;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  clip-path: inset(50%);
  margin: 0;
  padding: 0;
  border: 0;
  white-space: nowrap;
}

.csf-drop-zone {
  cursor: pointer;
  border-style: dashed !important;
  border-width: 1px !important;
  border-color: rgba(var(--v-theme-on-surface), 0.12) !important;
  background-color: rgba(var(--v-theme-on-surface), 0.045);
  transition: background-color 0.15s ease, border-color 0.15s ease;
  padding: 2.5rem 2rem;
  margin-bottom: 2rem;
}

.csf-drop-icon {
  display: block;
  margin: 0 auto 1rem;
}

.csf-drop-title {
  line-height: 1.5;
}

.csf-drop-sub {
  margin-top: 0.75rem;
  line-height: 1.45;
}

.csf-drop-zone:hover {
  background-color: rgba(var(--v-theme-on-surface), 0.07);
  border-color: rgba(var(--v-theme-on-surface), 0.2) !important;
}

.csf-drop-zone--active {
  background-color: rgba(var(--v-theme-primary), 0.07);
  border-color: rgba(var(--v-theme-primary), 0.4) !important;
}

.csf-queue-block {
  margin-bottom: 2.25rem;
}

.csf-queue-heading {
  margin-bottom: 0.65rem;
  letter-spacing: 0.01em;
}

.csf-queue-table-shell {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 8px;
  overflow: hidden;
  background-color: rgb(var(--v-theme-surface));
}

.csf-queue-table-footer {
  padding: 0.5rem 0.65rem;
  border-top: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  background-color: rgba(var(--v-theme-on-surface), 0.03);
}

.csf-progress {
  margin-bottom: 2rem;
  margin-top: 0.75rem;
}

.csf-server-header-block {
  margin-top: 2.5rem;
  margin-bottom: 1rem;
}

.csf-server-header-row {
  margin-bottom: 0.35rem;
}

.csf-server-delete-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem;
}

.csf-server-heading {
  font-weight: 700;
  letter-spacing: 0.01em;
}

.csf-server-total {
  flex-shrink: 0;
  padding-top: 0.15rem;
}

.csf-legend {
  font-size: 0.6875rem;
  line-height: 1.35;
  gap: 0.875rem 1.5rem;
  row-gap: 0.5rem;
  margin-top: 0.75rem;
  margin-bottom: 0;
  padding-top: 0.65rem;
  color: rgba(var(--v-theme-on-surface), 0.75);
}

.csf-legend-item {
  display: inline-flex;
  align-items: center;
  white-space: nowrap;
}

.csf-legend-item :deep(.v-icon) {
  font-size: 0.875rem !important;
}

.csf-server-table {
  margin-top: 0;
}

/* Study folder files table — tighter rows */
.csf-table :deep(th),
.csf-table :deep(td) {
  font-size: 13px;
  padding-top: 4px !important;
  padding-bottom: 4px !important;
  padding-left: 8px !important;
  padding-right: 8px !important;
}

.csf-table :deep(th) {
  padding-top: 6px !important;
  padding-bottom: 6px !important;
}

.csf-queue-table :deep(th),
.csf-queue-table :deep(td) {
  font-size: 13px;
  padding-top: 4px !important;
  padding-bottom: 4px !important;
  padding-left: 8px !important;
  padding-right: 8px !important;
}

.csf-queue-table :deep(th) {
  padding-top: 6px !important;
  padding-bottom: 6px !important;
}

.gap-1 {
  gap: 4px;
}

.text-deep-purple {
  color: #5e35b1;
}

.text-success {
  color: #2e7d32;
}
</style>
