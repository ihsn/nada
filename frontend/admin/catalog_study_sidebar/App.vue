<template>
  <v-app class="catalog-study-sidebar-vapp">
    <v-main class="catalog-study-sidebar-root pa-0">
      <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="4500">
        {{ snackbar.text }}
      </v-snackbar>

      <!-- Status -->
      <v-card variant="outlined" class="csb-sidebar-card">
        <v-card-title class="csb-card-title py-2">{{ lbl.status || 'Status' }}</v-card-title>
        <v-card-text class="pt-0">
          <div class="csb-btn-stack">
            <v-btn
              block
              variant="flat"
              class="csb-btn-publish"
              :class="publishedLocal ? 'csb-btn-publish--published' : 'csb-btn-publish--draft'"
              :color="publishedLocal ? 'success' : 'warning'"
              :loading="publishBusy"
              :title="lbl.click_publish || ''"
              size="small"
              @click="togglePublish"
            >
              {{ publishedLocal ? (lbl.published || 'Published') : (lbl.draft || 'Draft') }}
            </v-btn>
            <v-btn
              block
              class="csb-btn-delete"
              color="error"
              variant="tonal"
              size="small"
              :loading="deleteBusy"
              @click="onDeleteStudy"
            >
              {{ lbl.delete_study || 'Delete study' }}
            </v-btn>
          </div>
        </v-card-text>
      </v-card>

      <!-- Warnings (collapsible) -->
      <v-expansion-panels
        v-model="warningsPanelOpen"
        variant="accordion"
        class="csb-sidebar-card csb-expansion-panels"
        rounded="lg"
      >
        <v-expansion-panel value="warnings" elevation="0">
          <v-expansion-panel-title class="csb-card-title py-2">
            <span class="d-flex align-center flex-wrap gap-2">
              {{ lbl.study_warnings || 'Warnings' }}
              <v-chip v-if="warnings.length > 0" size="x-small" color="error" variant="flat">{{ warnings.length }}</v-chip>
            </span>
          </v-expansion-panel-title>
          <v-expansion-panel-text class="pt-0 pb-3">
            <div v-if="warningsLoading" class="text-caption text-medium-emphasis">{{ lbl.loading || 'Loading…' }}</div>
            <div v-else-if="warnings.length === 0" class="text-caption text-medium-emphasis">—</div>
            <ul v-else class="csb-warnings ps-4 mb-0">
              <li v-for="(w, idx) in warnings" :key="idx">{{ w.message }}</li>
            </ul>
          </v-expansion-panel-text>
        </v-expansion-panel>
      </v-expansion-panels>

      <!-- Thumbnail -->
      <v-card variant="outlined" class="csb-sidebar-card">
        <v-card-title class="csb-card-title py-2">{{ lbl.thumbnail || 'Thumbnail' }}</v-card-title>
        <v-card-text class="pt-0">
          <div class="csb-thumb-block">
          <div class="csb-thumb-shell rounded-lg">
            <div class="csb-thumb-frame">
              <v-img
                v-if="thumbnailDisplayUrl"
                :src="thumbnailDisplayUrl"
                :width="150"
                :height="150"
                cover
                class="csb-thumb-img rounded-lg"
              />
              <div
                v-else
                class="csb-thumb-placeholder d-flex align-center justify-center text-medium-emphasis text-caption px-4 text-center"
              >
                {{ lbl.thumbnail_empty_hint || 'No thumbnail' }}
              </div>
              <v-btn
                type="button"
                icon
                size="x-small"
                variant="elevated"
                color="surface"
                class="csb-thumb-edit-btn"
                :title="lbl.upload_thumbnail_title || lbl.upload || 'Edit thumbnail'"
                :aria-label="lbl.upload_thumbnail_title || lbl.upload || 'Edit thumbnail'"
                @click.stop="thumbDialog = true"
              >
                <v-icon icon="mdi-pencil" size="18" />
              </v-btn>
            </div>
          </div>
          </div>
          <div v-if="thumbnailFilename" class="csb-thumb-actions d-flex flex-wrap gap-2 justify-center">
            <v-btn
              size="small"
              variant="outlined"
              color="error"
              :loading="thumbBusy"
              @click="onRemoveThumbnail"
            >
              {{ lbl.remove || 'Remove' }}
            </v-btn>
          </div>
        </v-card-text>
      </v-card>

      <!-- Options -->
      <v-card variant="outlined" class="csb-sidebar-card">
        <v-card-title class="csb-card-title py-2">{{ lbl.options || 'Options' }}</v-card-title>
        <v-card-text class="pt-0">
          <v-list density="compact" class="bg-transparent pa-0 csb-options-list">
            <v-list-item
              class="csb-option-item rounded-lg mb-1"
              :href="legacy.browsePublic"
              target="_blank"
              rel="noopener"
              prepend-icon="mdi-book-search-outline"
              :title="lbl.browse_metadata || ''"
            />
            <v-list-item
              class="csb-option-item rounded-lg mb-1"
              :href="legacy.importRdf"
              prepend-icon="mdi-database-import-outline"
              :title="lbl.upload_rdf || ''"
            />
            <v-list-item
              class="csb-option-item rounded-lg mb-1"
              :href="legacy.fixLinks"
              prepend-icon="mdi-link-variant"
              :title="lbl.link_resources || ''"
            />
            <template v-if="studyType === 'survey'">
              <v-list-item
                class="csb-option-item rounded-lg mb-1"
                :href="legacy.pdfSetup"
                prepend-icon="mdi-file-pdf-box"
                :title="lbl.generate_pdf || ''"
              />
              <v-list-item
                class="csb-option-item rounded-lg mb-1"
                :href="legacy.replaceDdi"
                prepend-icon="mdi-file-replace-outline"
                :title="lbl.replace_ddi || ''"
              />
              <v-list-item
                class="csb-option-item rounded-lg mb-1"
                :href="legacy.exportDdi"
                prepend-icon="mdi-download-outline"
                :title="lbl.export_ddi || ''"
              />
              <v-list-item
                class="csb-option-item rounded-lg mb-1"
                :href="legacy.refreshDdi"
                prepend-icon="mdi-refresh"
                :title="lbl.refresh_ddi || ''"
              />
              <v-list-item
                class="csb-option-item rounded-lg mb-1"
                prepend-icon="mdi-file-document-edit-outline"
                :title="lbl.generate_ddi || 'Generate DDI'"
                :disabled="ddiGenerateBusy"
                @click="onGenerateDdi"
              />
            </template>
            <v-list-item
              class="csb-option-item rounded-lg mb-1"
              :href="legacy.transfer"
              prepend-icon="mdi-account-switch-outline"
              :title="lbl.transfer_study || ''"
            />
            <v-list-item
              class="csb-option-item rounded-lg mb-1"
              :href="legacy.exportRdf"
              prepend-icon="mdi-xml"
              :title="lbl.export_rdf || ''"
            />
            <v-list-item
              class="csb-option-item rounded-lg"
              :href="legacy.deleteStudy"
              prepend-icon="mdi-delete-outline"
              :title="lbl.delete_study || ''"
            />
          </v-list>
        </v-card-text>
      </v-card>

      <v-dialog v-model="thumbDialog" max-width="420">
        <v-card>
          <v-card-title class="text-subtitle-1">{{ lbl.upload_thumbnail_title || 'Upload thumbnail' }}</v-card-title>
          <v-card-text>
            <v-file-input
              v-model="thumbFileModel"
              accept="image/jpeg,image/png,image/gif"
              :multiple="false"
              density="compact"
              variant="outlined"
              hide-details
              clearable
            />
          </v-card-text>
          <v-card-actions>
            <v-spacer />
            <v-btn variant="text" @click="thumbDialog = false">{{ lbl.cancel || 'Cancel' }}</v-btn>
            <v-btn color="primary" :loading="thumbBusy" :disabled="!hasThumbFileSelected" @click="onUploadThumbnail">
              {{ lbl.upload || 'Upload' }}
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-main>
  </v-app>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useCatalogStudySidebarApi } from './composables/useCatalogStudySidebarApi';

const { config } = useAppConfig();
const { fetchWarnings, setPublished, deleteStudy, uploadThumbnail, removeThumbnail, generateDdi } = useCatalogStudySidebarApi();

const lbl = computed(() => config.value?.labels || {});
const legacy = computed(() => config.value?.legacyUrls || {});
const studyType = computed(() => String(config.value?.studyType || 'survey'));
const catalogListUrl = computed(() => String(config.value?.catalogListUrl || '/'));

const publishedLocal = ref(!!config.value?.published);
const thumbnailFilename = ref(String(config.value?.thumbnailFilename || ''));
const thumbVer = ref(0);

const warnings = ref([]);
const warningsLoading = ref(true);
const warningsPanelOpen = ref([]);
const publishBusy = ref(false);
const deleteBusy = ref(false);
const thumbBusy = ref(false);
const ddiGenerateBusy = ref(false);
const thumbDialog = ref(false);
const thumbFileModel = ref(null);

const snackbar = ref({ show: false, text: '', color: 'surface' });

const thumbnailsBase = computed(() => String(config.value?.thumbnailsPublicBase || '').replace(/\/+$/, '') + '/');

const thumbnailDisplayUrl = computed(() => {
  const fn = thumbnailFilename.value.trim();
  if (!fn) return '';
  return `${thumbnailsBase.value}${encodeURIComponent(fn)}?v=${thumbVer.value}`;
});

/** v-file-input may bind File or File[] depending on Vuetify version */
const hasThumbFileSelected = computed(() => {
  const v = thumbFileModel.value;
  if (v == null) return false;
  if (Array.isArray(v)) return v.length > 0 && v[0] instanceof File;
  return v instanceof File;
});

watch(thumbDialog, (open) => {
  if (!open) {
    thumbFileModel.value = null;
  }
});

function showSnack(text, color = 'surface') {
  snackbar.value = { show: true, text, color };
}

async function loadWarnings() {
  warningsLoading.value = true;
  try {
    const data = await fetchWarnings();
    warnings.value = Array.isArray(data.warnings) ? data.warnings : [];
  } catch (e) {
    showSnack(e?.message || 'Error', 'error');
    warnings.value = [];
  } finally {
    warningsLoading.value = false;
  }
}

async function togglePublish() {
  const next = !publishedLocal.value;
  publishBusy.value = true;
  try {
    await setPublished(next);
    publishedLocal.value = next;
    window.dispatchEvent(
      new CustomEvent('catalogStudyPublishedChanged', {
        detail: { published: next },
      })
    );
    showSnack(lbl.value.saved || 'Saved', 'success');
    await loadWarnings();
  } catch (e) {
    showSnack(e?.message || 'Error', 'error');
  } finally {
    publishBusy.value = false;
  }
}

async function onDeleteStudy() {
  if (!window.confirm(lbl.value.confirm_delete || 'Delete?')) return;
  deleteBusy.value = true;
  try {
    await deleteStudy();
    window.location.href = catalogListUrl.value;
  } catch (e) {
    showSnack(e?.message || 'Error', 'error');
    deleteBusy.value = false;
  }
}

async function onUploadThumbnail() {
  const raw = thumbFileModel.value;
  let file = null;
  if (Array.isArray(raw)) file = raw[0];
  else if (raw instanceof File) file = raw;
  if (!file) return;
  thumbBusy.value = true;
  try {
    const data = await uploadThumbnail(file);
    const name = data.uploaded_file_name ? String(data.uploaded_file_name) : '';
    if (name) {
      thumbnailFilename.value = name.split(/[/\\]/).pop() || name;
    }
    thumbVer.value += 1;
    thumbDialog.value = false;
    thumbFileModel.value = null;
    showSnack(lbl.value.saved || 'Saved', 'success');
    await loadWarnings();
  } catch (e) {
    showSnack(e?.message || 'Error', 'error');
  } finally {
    thumbBusy.value = false;
  }
}

async function onRemoveThumbnail() {
  thumbBusy.value = true;
  try {
    await removeThumbnail();
    thumbnailFilename.value = '';
    thumbVer.value += 1;
    showSnack(lbl.value.saved || 'Saved', 'success');
    await loadWarnings();
  } catch (e) {
    showSnack(e?.message || 'Error', 'error');
  } finally {
    thumbBusy.value = false;
  }
}

async function onGenerateDdi(ev) {
  ev.preventDefault();
  if (!window.confirm(lbl.value.confirm_generate_ddi || 'Continue?')) return;
  if (ddiGenerateBusy.value) return;
  ddiGenerateBusy.value = true;
  try {
    await generateDdi();
    showSnack(lbl.value.generate_ddi_success || 'DDI generated', 'success');
  } catch (e) {
    showSnack(e?.message || 'Error', 'error');
  } finally {
    ddiGenerateBusy.value = false;
  }
}

onMounted(() => {
  loadWarnings();
});
</script>

<style scoped>
/* Nested VApp defaults to 100dvh min-height on .v-application__wrap — keep this shell content-sized */
.catalog-study-sidebar-vapp {
  background: transparent !important;
}

.catalog-study-sidebar-vapp :deep(.v-application__wrap) {
  min-height: auto !important;
  background: transparent !important;
}

.catalog-study-sidebar-vapp :deep(.v-main) {
  flex: 0 0 auto !important;
  background: transparent !important;
}

.catalog-study-sidebar-root {
  max-width: 100%;
  font-size: 0.8125rem;
  line-height: 1.45;
  box-sizing: border-box;
  background: transparent !important;
}

.csb-card-title {
  font-size: 0.8125rem !important;
  font-weight: 600;
  letter-spacing: 0.01em;
}

/* Outlined cards: white panel, light border, space below each stacked block */
.csb-sidebar-card {
  border-color: rgba(0, 0, 0, 0.08) !important;
  margin-bottom: 16px;
  background-color: #fff !important;
}

.csb-expansion-panels {
  border: 1px solid rgba(0, 0, 0, 0.08);
  background-color: #fff !important;
}

.csb-expansion-panels :deep(.v-expansion-panel) {
  background-color: #fff !important;
}

.csb-expansion-panels :deep(.v-expansion-panel-title),
.csb-expansion-panels :deep(.v-expansion-panel-text) {
  font-size: 0.8125rem;
}

.csb-expansion-panels :deep(.v-expansion-panel-title__overlay) {
  border-radius: inherit;
}

/* Publish / Delete spacing */
.csb-btn-stack {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.csb-warnings li {
  margin-bottom: 6px;
  font-size: 0.8125rem;
}

.csb-warnings li:last-child {
  margin-bottom: 0;
}

/* Thumbnail (max 150×150), centered in column */
.csb-thumb-block {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
}

.csb-thumb-shell {
  width: 150px;
  max-width: 150px;
  height: 150px;
  max-height: 150px;
  margin-bottom: 12px;
  background: rgba(var(--v-theme-on-surface), 0.04);
  border: 1px dashed rgba(0, 0, 0, 0.12);
  overflow: hidden;
  flex-shrink: 0;
}

.csb-thumb-frame {
  position: relative;
  width: 100%;
  height: 100%;
}

.csb-thumb-edit-btn {
  position: absolute;
  right: 6px;
  bottom: 6px;
  z-index: 1;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.22);
}

.csb-thumb-edit-btn :deep(.v-btn__overlay) {
  opacity: 0.08;
}

.csb-thumb-img {
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
}

.csb-thumb-img :deep(img) {
  max-width: 150px;
  max-height: 150px;
}

.csb-thumb-placeholder {
  width: 100%;
  height: 100%;
  min-height: 0;
}

.csb-thumb-actions {
  margin-top: 2px;
}

/* Options list */
.csb-options-list :deep(.v-list-item) {
  font-size: 0.8125rem;
  min-height: 38px;
}

.csb-options-list :deep(.v-list-item-title) {
  font-size: 0.8125rem !important;
  line-height: 1.35;
  white-space: normal;
  word-break: break-word;
}

.csb-option-item :deep(.v-list-item__prepend > .v-icon) {
  opacity: 0.85;
}

.csb-option-item:hover {
  background-color: rgba(var(--v-theme-on-surface), 0.04);
}
</style>
