<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="fs-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <v-row align="center" class="mb-4">
      <v-col cols="12" md="8">
        <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-0">{{ t('file_manager', 'File manager') }}</h1>
        <div class="text-body-2 text-medium-emphasis">
          Public files for use in pages, menus and site content.
        </div>
      </v-col>
      <v-col cols="12" md="4" class="text-md-end">
        <v-btn v-if="canUpload" color="primary" prepend-icon="mdi-upload" @click="uploadOpen = true">
          Upload files
        </v-btn>
      </v-col>
    </v-row>

    <v-alert v-if="error" type="error" density="compact" class="mb-4" closable @click:close="error = ''">
      {{ error }}
    </v-alert>

    <v-card class="pa-4 mb-4" elevation="1">
      <v-row dense align="end">
        <v-col cols="12" md="6">
          <div class="text-body-2 font-weight-bold text-high-emphasis mb-1">{{ t('search', 'Search') }}</div>
          <v-text-field
            v-model="keywords"
            placeholder="File name"
            density="compact"
            variant="outlined"
            hide-details
            clearable
            prepend-inner-icon="mdi-magnify"
            @keyup.enter="applyFilters"
            @click:clear="onClearKeywords"
          />
        </v-col>
        <v-col cols="6" md="2">
          <div class="text-body-2 font-weight-bold text-high-emphasis mb-1">Type</div>
          <v-combobox
            v-model="fileType"
            :items="typeItems"
            placeholder="Any"
            density="compact"
            variant="outlined"
            hide-details
            clearable
            @update:model-value="applyFilters"
          />
        </v-col>
        <v-col cols="6" md="2">
          <v-switch
            v-model="imagesOnly"
            label="Images only"
            color="primary"
            density="compact"
            hide-details
            inset
            @update:model-value="applyFilters"
          />
        </v-col>
        <v-col cols="12" md="2">
          <v-btn color="primary" block @click="applyFilters">{{ t('search', 'Search') }}</v-btn>
        </v-col>
      </v-row>
    </v-card>

    <v-card elevation="1">
      <div v-if="canDelete && selected.length" class="pa-3 d-flex align-center ga-3 border-b">
        <span class="text-body-2">{{ selected.length }} selected</span>
        <v-btn size="small" color="error" variant="tonal" prepend-icon="mdi-delete" @click="askDelete(selected)">
          Delete selected
        </v-btn>
      </div>

      <v-data-table-server
        v-model="selected"
        v-model:page="page"
        v-model:items-per-page="pageSize"
        v-model:sort-by="sortBy"
        :headers="headers"
        :items="files"
        :items-length="total"
        :loading="loading"
        :items-per-page-options="[15, 30, 50, 100]"
        :show-select="canDelete"
        item-value="file_name"
        @update:options="load"
      >
        <template #item.preview="{ item }">
          <a :href="fileUrl(item)" target="_blank" rel="noopener" class="fs-thumb">
            <img v-if="isImage(item) && !brokenThumbs[item.file_name]" :src="fileUrl(item)" alt="" loading="lazy" @error="brokenThumbs[item.file_name] = true" />
            <v-icon v-else :icon="fileIcon(item.file_ext)" :color="fileColor(item.file_ext)" size="28" />
          </a>
        </template>
        <template #item.file_name="{ item }">
          <a href="#" class="text-primary text-decoration-none font-weight-medium" @click.prevent="openDetails(item)">
            {{ item.file_name }}
          </a>
          <div class="text-caption text-medium-emphasis">{{ item.file_path }}</div>
          <div v-if="item.file_exists === false" class="text-caption text-error">File missing on disk</div>
        </template>
        <template #item.file_ext="{ item }">
          <v-chip size="x-small" label :color="fileColor(item.file_ext)" variant="tonal">
            {{ item.file_ext ? String(item.file_ext).toUpperCase() : 'N/A' }}
          </v-chip>
        </template>
        <template #item.file_size="{ item }">{{ formatFileSize(item.file_size) }}</template>
        <template #item.changed="{ item }">{{ formatTimestamp(item.changed) }}</template>
        <template #item.actions="{ item }">
          <div class="d-flex justify-end">
            <v-btn icon="mdi-link-variant" size="small" variant="text" title="Copy link" @click="copyLink(item)" />
            <v-btn icon="mdi-open-in-new" size="small" variant="text" title="Open" :href="fileUrl(item)" target="_blank" rel="noopener" />
            <v-btn v-if="canDelete" icon="mdi-delete" size="small" variant="text" color="error" title="Delete" @click="askDelete([item.file_name])" />
          </div>
        </template>
        <template #no-data>
          <div class="py-8 text-medium-emphasis">
            <v-icon icon="mdi-folder-open-outline" size="40" class="mb-2" />
            <div>No files found.</div>
          </div>
        </template>
      </v-data-table-server>
    </v-card>

    <FileUploadDialog v-model="uploadOpen" @uploaded="onUploaded" />

    <v-dialog v-model="details.open" max-width="640">
      <v-card v-if="details.file">
        <v-card-title class="text-h6 text-truncate">{{ details.file.file_name }}</v-card-title>
        <v-card-text>
          <div v-if="isImage(details.file)" class="fs-preview mb-4">
            <img :src="fileUrl(details.file)" alt="" />
          </div>
          <v-table density="compact">
            <tbody>
              <tr>
                <th>Type</th>
                <td>{{ details.file.file_ext ? String(details.file.file_ext).toUpperCase() : 'N/A' }}</td>
              </tr>
              <tr>
                <th>Size</th>
                <td>{{ formatFileSize(details.file.file_size) }}</td>
              </tr>
              <tr>
                <th>Uploaded</th>
                <td>{{ formatTimestamp(details.file.changed) }}</td>
              </tr>
              <tr>
                <th>Folder</th>
                <td>{{ details.file.file_path }}</td>
              </tr>
            </tbody>
          </v-table>
          <v-text-field
            :model-value="fileUrl(details.file)"
            label="Public URL"
            readonly
            density="compact"
            variant="outlined"
            hide-details
            class="mt-4"
            append-inner-icon="mdi-content-copy"
            @click:append-inner="copyLink(details.file)"
          />
        </v-card-text>
        <v-card-actions>
          <v-btn v-if="canDelete" color="error" variant="text" prepend-icon="mdi-delete" @click="askDelete([details.file.file_name])">
            {{ t('delete', 'Delete') }}
          </v-btn>
          <v-spacer />
          <v-btn variant="text" @click="details.open = false">{{ t('close', 'Close') }}</v-btn>
          <v-btn color="primary" variant="flat" prepend-icon="mdi-open-in-new" :href="fileUrl(details.file)" target="_blank" rel="noopener">
            Open
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="deleteDialog.open" max-width="440" :persistent="deleteDialog.saving">
      <v-card>
        <v-card-title class="text-h6">{{ t('confirm_delete', 'Confirm delete') }}</v-card-title>
        <v-card-text>
          <template v-if="deleteDialog.names.length === 1">
            Delete <strong>{{ deleteDialog.names[0] }}</strong>?
          </template>
          <template v-else>Delete {{ deleteDialog.names.length }} files?</template>
          Pages that link to deleted files will show broken links. This cannot be undone.
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" :disabled="deleteDialog.saving" @click="deleteDialog.open = false">
            {{ t('cancel', 'Cancel') }}
          </v-btn>
          <v-btn color="error" variant="flat" :loading="deleteDialog.saving" @click="confirmDelete">
            {{ t('delete', 'Delete') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="toast.open" :color="toast.color" location="bottom right" :timeout="4000">
      {{ toast.message }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useI18n } from '@/shared/composables/useI18n';
import { useFilestoreApi } from '../composables/useFilestoreApi';
import { fileColor, fileIcon, formatFileSize, formatTimestamp } from '../utils/files';
import FileUploadDialog from '../components/FileUploadDialog.vue';

defineOptions({ name: 'FilestorePage' });

const { t } = useI18n();
const { config, siteUrl } = useAppConfig();
const { fileUrl, search, getFile, remove } = useFilestoreApi();

const canUpload = computed(() => !!config.value?.canUpload);
const canDelete = computed(() => !!config.value?.canDelete);

const breadcrumbItems = computed(() => [
  { title: t('home', 'Home'), href: `${String(siteUrl.value || '').replace(/\/$/, '')}/admin` },
  { title: t('file_manager', 'File manager'), disabled: true },
]);

const files = ref([]);
const total = ref(0);
const loading = ref(false);
const error = ref('');
const keywords = ref('');
const fileType = ref(null);
const imagesOnly = ref(false);
const page = ref(1);
const pageSize = ref(15);
const sortBy = ref([{ key: 'changed', order: 'desc' }]);
const selected = ref([]);
const uploadOpen = ref(false);
const brokenThumbs = reactive({});
const details = reactive({ open: false, file: null });
const deleteDialog = reactive({ open: false, names: [], saving: false });
const toast = reactive({ open: false, message: '', color: 'success' });

const typeItems = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'ppt', 'pptx', 'zip', 'txt', 'jpg', 'png', 'gif'];

const headers = computed(() => [
  { title: '', key: 'preview', sortable: false, width: '72px' },
  { title: 'File name', key: 'file_name', sortable: true },
  { title: 'Type', key: 'file_ext', sortable: true, width: '100px' },
  { title: 'Size', key: 'file_size', sortable: false, width: '110px' },
  { title: 'Uploaded', key: 'changed', sortable: true, width: '190px' },
  { title: '', key: 'actions', sortable: false, width: '150px', align: 'end' },
]);

function isImage(file) {
  return Number(file?.is_image) === 1;
}

function notify(message, color = 'success') {
  Object.assign(toast, { open: true, message, color });
}

async function load() {
  loading.value = true;
  error.value = '';
  const sb = sortBy.value[0];
  const q = (keywords.value || '').trim();
  const ext = String(fileType.value || '').trim().replace(/^\./, '').toLowerCase();
  try {
    const result = await search({
      limit: pageSize.value,
      offset: (page.value - 1) * pageSize.value,
      sort_by: sb?.key || 'changed',
      sort_order: sb?.order === 'asc' ? 'asc' : 'desc',
      search: q || undefined,
      field: q ? 'file_name' : undefined,
      filter_type: ext || undefined,
      filter_images: imagesOnly.value ? '1' : undefined,
    });
    files.value = result.files;
    total.value = result.total;
    selected.value = selected.value.filter((name) => result.files.some((f) => f.file_name === name));
  } catch (e) {
    files.value = [];
    total.value = 0;
    error.value = e.message;
  } finally {
    loading.value = false;
  }
}

function applyFilters() {
  if (page.value !== 1) {
    page.value = 1;
  } else {
    load();
  }
}

function onClearKeywords() {
  keywords.value = '';
  applyFilters();
}

async function openDetails(item) {
  details.file = item;
  details.open = true;
  try {
    details.file = await getFile(item.file_name);
  } catch (e) {
    notify(e.message, 'error');
  }
}

async function copyLink(file) {
  try {
    await navigator.clipboard.writeText(fileUrl(file));
    notify('Link copied');
  } catch {
    notify('Could not copy the link', 'error');
  }
}

function askDelete(names) {
  Object.assign(deleteDialog, { open: true, names: [...names], saving: false });
}

async function confirmDelete() {
  deleteDialog.saving = true;
  const failed = [];
  for (const name of deleteDialog.names) {
    try {
      await remove(name);
    } catch (e) {
      failed.push(`${name}: ${e.message}`);
    }
  }
  const deleted = deleteDialog.names.length - failed.length;
  deleteDialog.saving = false;
  deleteDialog.open = false;
  if (details.file && deleteDialog.names.includes(details.file.file_name)) details.open = false;
  selected.value = [];
  if (failed.length) {
    error.value = `Could not delete ${failed.length} file(s): ${failed.join('; ')}`;
  }
  if (deleted) notify(`${deleted} file(s) deleted`);
  await load();
}

function onUploaded(count) {
  notify(`${count} file(s) uploaded`);
  applyFilters();
}
</script>

<style scoped>
.fs-breadcrumbs {
  font-size: 0.8125rem;
  margin-bottom: 0.5rem;
}

.fs-thumb {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  margin: 6px 0;
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 4px;
  background: #f5f5f5;
  overflow: hidden;
}

.fs-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.fs-preview {
  display: flex;
  justify-content: center;
  background: #f5f5f5;
  border-radius: 4px;
}

.fs-preview img {
  max-width: 100%;
  max-height: 360px;
  object-fit: contain;
}

.border-b {
  border-bottom: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
}
</style>
