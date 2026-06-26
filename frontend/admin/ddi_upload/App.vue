<template>
  <v-app>
    <v-main class="bg-grey-lighten-5 admin-upload-page">
      <v-progress-linear
        v-show="submitting"
        indeterminate
        color="primary"
        height="3"
        absolute
        class="admin-upload-top-progress"
      />

      <v-container fluid class="pa-4 pa-md-6 admin-upload-page__container">
        <header class="admin-upload-page__header">
          <div class="admin-upload-page__title-block">
            <h1 class="text-h4 text-md-h5 font-weight-semibold text-high-emphasis mb-0 admin-upload-page__title">
              {{ pageHeading }}
            </h1>
          </div>
          <div class="admin-upload-page__header-actions d-flex flex-wrap gap-2">
            <v-btn
              variant="text"
              color="medium-emphasis"
              :href="catalogAdminUrl"
              class="text-none"
              prepend-icon="mdi-arrow-left"
            >
              {{ tr('back_to_catalog') }}
            </v-btn>
            <v-btn
              variant="flat"
              color="primary"
              :href="catalogAdminUrl"
              class="text-none"
              prepend-icon="mdi-view-dashboard-outline"
            >
              {{ tr('catalog_manage') }}
            </v-btn>
          </div>
        </header>

        <div class="admin-upload-alerts mb-6">
          <v-alert
            v-if="flashErrorHtml"
            type="error"
            variant="tonal"
            rounded="xl"
            prominent
            closable
            class="mb-3"
            @click:close="flashErrorHtml = null"
          >
            <template #prepend>
              <v-icon icon="mdi-alert-circle-outline" size="large" />
            </template>
            <div class="text-subtitle-2 font-weight-medium mb-1">{{ tr('alert_error_title') }}</div>
            <div class="error-pre-wrap text-body-2" v-html="flashErrorHtml"></div>
          </v-alert>

          <v-alert
            v-if="loadError"
            type="error"
            variant="tonal"
            rounded="xl"
            prominent
            class="mb-3"
          >
            <template #prepend>
              <v-icon icon="mdi-cloud-off-outline" size="large" />
            </template>
            {{ loadError }}
          </v-alert>

          <v-alert
            v-if="importProgress"
            type="info"
            variant="tonal"
            rounded="xl"
            class="mb-3"
          >
            {{ importProgress }}
          </v-alert>

          <v-alert
            v-if="apiError"
            type="error"
            variant="tonal"
            rounded="xl"
            prominent
            class="mb-0"
          >
            <template #prepend>
              <v-icon icon="mdi-upload-off-outline" size="large" />
            </template>
            <pre class="api-error-json text-body-2 mb-0">{{ apiError }}</pre>
          </v-alert>
        </div>

        <v-row class="admin-upload-row">
          <v-col cols="12" lg="7" xl="8">
            <v-card
              variant="flat"
              rounded="xl"
              class="admin-upload-card admin-upload-card--primary fill-height"
            >
              <v-card-item class="pb-0 pt-6 px-6">
                <template #prepend>
                  <div class="admin-upload-section-icon admin-upload-section-icon--primary" aria-hidden="true">
                    <v-icon icon="mdi-file-upload-outline" size="28" />
                  </div>
                </template>
                <v-card-title class="text-h5 font-weight-semibold ps-1 text-high-emphasis">
                  {{ tr('import_section_title') }}
                </v-card-title>
              </v-card-item>

              <v-card-text class="px-6 pb-2 pt-4">
                <template v-if="loadingCollections">
                  <div class="admin-upload-skeleton-stack">
                    <v-skeleton-loader type="heading" class="mb-4" />
                    <v-skeleton-loader type="image" height="88" class="rounded-xl mb-3" />
                    <v-skeleton-loader type="image" height="88" class="rounded-xl mb-3" />
                    <v-skeleton-loader type="button" width="200" />
                  </div>
                </template>

                <div
                  v-else-if="collectionItems.length === 0"
                  class="admin-upload-empty pa-8 text-center"
                >
                  <v-avatar color="warning" variant="tonal" size="64" rounded="xl" class="mb-4">
                    <v-icon icon="mdi-database-off-outline" size="36" />
                  </v-avatar>
                  <h3 class="text-h6 font-weight-semibold mb-2">{{ tr('empty_collections_title') }}</h3>
                  <p class="text-body-2 text-medium-emphasis mx-auto mb-4" style="max-width: 22rem">
                    {{ tr('empty_collections_hint') }}
                  </p>
                  <v-btn
                    color="primary"
                    variant="flat"
                    class="text-none"
                    :href="collectionsAdminUrl"
                    prepend-icon="mdi-folder-multiple-outline"
                  >
                    {{ tr('browse_collections') }}
                  </v-btn>
                </div>

                <form v-else class="admin-upload-form-stack" @submit.prevent="onSubmit">
                  <div class="admin-upload-labeled-field">
                    <label class="admin-upload-field-label" :for="fieldIds.collection">
                      {{ tr('collection') }}
                    </label>
                    <v-select
                      :id="fieldIds.collection"
                      v-model="selectedRepo"
                      :items="collectionItems"
                      item-title="displayLabel"
                      item-value="repositoryid"
                      variant="outlined"
                      density="comfortable"
                      prepend-inner-icon="mdi-database-outline"
                      hide-details="auto"
                      :no-data-text="tr('no_collections')"
                      class="admin-upload-field"
                    />
                  </div>

                  <div class="admin-upload-labeled-field">
                    <label class="admin-upload-field-label" :for="fieldIds.mainFile">
                      {{ tr('msg_metadata_file') }}
                    </label>
                    <v-file-input
                      :id="fieldIds.mainFile"
                      v-model="mainFileModel"
                      :hint="mainFileHint"
                      persistent-hint
                      accept=".xml,.json,.jsonl,.zip,text/xml,application/xml,application/json,application/zip,application/x-zip-compressed"
                      variant="outlined"
                      density="comfortable"
                      :prepend-icon="false"
                      prepend-inner-icon="mdi-file-document-outline"
                      show-size
                      clearable
                      hide-details="auto"
                      class="admin-upload-file-field"
                    />
                  </div>

                  <div v-if="showRdfField" class="admin-upload-labeled-field">
                    <label class="admin-upload-field-label" :for="fieldIds.rdfFile">
                      {{ tr('msg_select_rdf') }}
                    </label>
                    <v-file-input
                      :id="fieldIds.rdfFile"
                      v-model="rdfFileModel"
                      accept=".rdf,.ttl,text/turtle,application/rdf+xml"
                      variant="outlined"
                      density="comfortable"
                      :prepend-icon="false"
                      prepend-inner-icon="mdi-link-variant"
                      show-size
                      clearable
                      hide-details="auto"
                      class="admin-upload-file-field"
                    />
                  </div>

                  <v-checkbox
                    v-model="overwrite"
                    :label="tr('ddi_overwrite_exist')"
                    density="comfortable"
                    color="primary"
                    hide-details
                    class="mt-0"
                  />

                  <div class="d-flex flex-column flex-sm-row flex-wrap gap-3 pt-2 align-sm-center">
                    <v-btn
                      color="primary"
                      size="default"
                      type="submit"
                      class="text-none px-6"
                      rounded="lg"
                      :loading="submitting"
                      :disabled="!canSubmit"
                      prepend-icon="mdi-cloud-upload-outline"
                    >
                      {{ tr('submit') }}
                    </v-btn>
                    <v-btn
                      variant="text"
                      size="large"
                      class="text-none"
                      :href="catalogAdminUrl"
                    >
                      {{ tr('cancel') }}
                    </v-btn>
                  </div>
                </form>
              </v-card-text>
            </v-card>
          </v-col>

          <v-col cols="12" lg="5" xl="4">
            <div class="admin-upload-aside">
              <v-card
                variant="flat"
                rounded="xl"
                class="admin-upload-card admin-upload-card--secondary fill-height"
              >
                <v-card-item class="pb-0 pt-6 px-6">
                  <template #prepend>
                    <div class="admin-upload-section-icon admin-upload-section-icon--secondary" aria-hidden="true">
                      <v-icon icon="mdi-plus-circle-outline" size="28" />
                    </div>
                  </template>
                  <v-card-title class="text-h6 font-weight-semibold ps-1 text-high-emphasis">
                    {{ tr('create_new_study') }}
                  </v-card-title>
                </v-card-item>

                <v-card-text class="px-6 pt-4 pb-6">
                  <template v-if="loadingCollections">
                    <div class="admin-upload-skeleton-stack">
                      <v-skeleton-loader type="heading" class="mb-3" />
                      <v-skeleton-loader type="image" height="56" class="rounded-xl mb-3" />
                      <v-skeleton-loader type="image" height="56" class="rounded-xl mb-3" />
                      <v-skeleton-loader type="button" width="100%" max-width="280" />
                    </div>
                  </template>

                  <div
                    v-else-if="collectionItems.length === 0"
                    class="admin-upload-aside-empty text-body-2 text-medium-emphasis"
                  >
                    <p class="mb-3">{{ tr('empty_collections_hint') }}</p>
                    <v-btn
                      color="secondary"
                      variant="tonal"
                      class="text-none"
                      size="small"
                      :href="collectionsAdminUrl"
                      prepend-icon="mdi-folder-multiple-outline"
                      block
                    >
                      {{ tr('browse_collections') }}
                    </v-btn>
                  </div>

                  <form
                    v-else
                    :action="createStudyUrl"
                    method="get"
                    class="admin-upload-form-stack"
                  >
                    <div class="admin-upload-labeled-field">
                      <label class="admin-upload-field-label" :for="fieldIds.createCollection">
                        {{ tr('collection') }}
                      </label>
                      <v-select
                        :id="fieldIds.createCollection"
                        v-model="selectedRepo"
                        :items="collectionItems"
                        item-title="displayLabel"
                        item-value="repositoryid"
                        variant="outlined"
                        density="comfortable"
                        prepend-inner-icon="mdi-database-outline"
                        hide-details="auto"
                        :no-data-text="tr('no_collections')"
                        class="admin-upload-field"
                      />
                    </div>
                    <div class="admin-upload-labeled-field">
                      <label class="admin-upload-field-label" :for="fieldIds.createType">
                        {{ tr('select_data_type') }}
                      </label>
                      <v-select
                        :id="fieldIds.createType"
                        v-model="createType"
                        :items="createTypeItems"
                        item-title="label"
                        item-value="value"
                        variant="outlined"
                        density="comfortable"
                        prepend-inner-icon="mdi-shape-outline"
                        hide-details="auto"
                        class="admin-upload-field"
                      />
                    </div>
                    <input type="hidden" name="type" :value="createType" />
                    <input type="hidden" name="repositoryid" :value="selectedRepo" />
                    <v-btn
                      color="secondary"
                      type="submit"
                      block
                      size="default"
                      class="text-none"
                      rounded="lg"
                      prepend-icon="mdi-arrow-right"
                      :disabled="!canCreateStudy"
                    >
                      {{ tr('create') }}
                    </v-btn>
                  </form>
                </v-card-text>
              </v-card>
            </div>
          </v-col>
        </v-row>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useDdiUploadApi } from './composables/useDdiUploadApi';

defineOptions({ name: 'AdminDdiUploadApp' });

/** Stable ids for label[for] → control (single page mount). */
const fieldIds = {
  collection: 'ddi-upload-field-collection',
  mainFile: 'ddi-upload-field-main-file',
  rdfFile: 'ddi-upload-field-rdf',
  createCollection: 'ddi-upload-field-create-collection',
  createType: 'ddi-upload-field-create-type',
};

const { config } = useAppConfig();
const { fetchCollectionsForUpload, postImport, uploadPackageZip, runPackageImport } = useDdiUploadApi();

const importProgress = ref('');

const mainFileModel = ref(null);
const rdfFileModel = ref(null);
const loadingCollections = ref(true);
const loadError = ref('');
const apiError = ref('');
const submitting = ref(false);
const collections = ref([]);
const selectedRepo = ref('');
const overwrite = ref(false);
const flashErrorHtml = ref(
  typeof config.value?.flashError === 'string' && config.value.flashError ? config.value.flashError : null
);

const catalogAdminUrl = computed(() => config.value?.catalogAdminUrl ?? '');
const catalogEditBase = computed(() => config.value?.catalogEditBase ?? '');
const collectionsAdminUrl = computed(() => config.value?.collectionsAdminUrl ?? '');
const createStudyUrl = computed(() => config.value?.createStudyUrl ?? '');
const maxUploadMb = computed(() => config.value?.maxUploadMb ?? 0);
const defaultRepositoryid = computed(() => config.value?.defaultRepositoryid ?? '');

const pageHeading = computed(() => config.value?.pageHeading ?? 'Add study');

const collectionItems = computed(() => collections.value);

const createType = ref('survey');
const createTypeItems = computed(() => [
  { value: 'survey', label: 'Microdata' },
  { value: 'document', label: 'Document' },
  { value: 'table', label: 'Table' },
  { value: 'timeseries', label: 'Timeseries' },
  { value: 'script', label: 'Script' },
  { value: 'image', label: 'Image' },
]);

/** Human-readable collection title (API may return an untranslated lang key for central). */
function resolveCollectionTitle(title, repositoryid) {
  const lang = config.value?.translations || {};
  if (repositoryid === 'central' && lang.central_data_catalog) {
    return lang.central_data_catalog;
  }
  const raw = title || repositoryid || '';
  if (raw && lang[raw]) {
    return lang[raw];
  }
  return raw;
}

function collectionDisplayLabel(c) {
  const rid = c.repositoryid;
  const title = resolveCollectionTitle(c.title, rid);
  if (rid === 'central') {
    return title;
  }
  if (title && String(title).toLowerCase() !== String(rid).toLowerCase()) {
    return `${title} (${rid})`;
  }
  return title || rid;
}

function formatImportApiError(err) {
  const payload = err?.importResponse ?? err?.response?.data;
  if (payload && typeof payload === 'object') {
    try {
      return JSON.stringify(payload, null, 2);
    } catch {
      // fall through
    }
  }
  return err?.message || 'Import failed';
}

function tr(key) {
  const lang = config.value?.translations || {};
  if (lang[key]) return lang[key];
  const map = {
    add_study_to_collection: 'Add study',
    collection: 'Collection',
    create_new_study: 'Create new study',
    select_data_type: 'Select data type',
    msg_metadata_file: 'Metadata file (DDI/XML, Geospatial/XML, JSON, JSONL, or package ZIP)',
    max_upload_limit: 'Maximum upload size:',
    msg_select_rdf: 'RDF / resources (optional)',
    back_to_catalog: 'Back',
    catalog_manage: 'Manage studies',
    import_section_title: 'Import from file',
    no_collections: 'No collections available',
    empty_collections_title: 'No collections to import into',
    empty_collections_hint:
      'You need access to at least one collection with create permission, or collections must exist in the system.',
    browse_collections: 'View collections',
    alert_error_title: 'Something went wrong',
    central_data_catalog: 'Central Data Catalog',
    package_zip_hint: 'Package ZIP should match export from Manage studies (info.json, JSONL, optional XML and documentation).',
    jsonl_hint: 'JSONL: first line is the study document; further lines are variables.',
  };
  return map[key] || key;
}

function normalizeFile(val) {
  if (!val) {
    return null;
  }
  if (Array.isArray(val)) {
    return val[0] instanceof File ? val[0] : null;
  }
  if (val instanceof File) {
    return val;
  }
  return null;
}

const mainFileForSubmit = computed(() => normalizeFile(mainFileModel.value));

function mainFileExtension(file) {
  if (!file?.name) return '';
  const dot = file.name.lastIndexOf('.');
  return dot >= 0 ? file.name.slice(dot + 1).toLowerCase() : '';
}

const showRdfField = computed(() => {
  const ext = mainFileExtension(mainFileForSubmit.value);
  return ext !== 'zip' && ext !== 'jsonl' && ext !== 'json';
});

const mainFileHint = computed(() => {
  const base = `${tr('max_upload_limit')} ${maxUploadMb.value} MB`;
  const ext = mainFileExtension(mainFileForSubmit.value);
  if (ext === 'zip') {
    return `${base}. ${tr('package_zip_hint')}`;
  }
  if (ext === 'jsonl') {
    return `${base}. ${tr('jsonl_hint')}`;
  }
  return base;
});

const canSubmit = computed(
  () => selectedRepo.value && collectionItems.value.length > 0 && !!mainFileForSubmit.value
);

const canCreateStudy = computed(
  () => !!selectedRepo.value && collectionItems.value.length > 0 && !loadingCollections.value
);

function formatPackageProgress(ev) {
  if (!ev) return '';
  if (ev.phase === 'upload' && ev.total) {
    const pct = Math.round((ev.loaded / ev.total) * 100);
    return `Uploading package… ${pct}%`;
  }
  if (ev.next_task === 'datafile' || ev.phase === 'importing_datafiles') {
    const done = ev.data_files_done ?? 0;
    const total = ev.data_files_total ?? 0;
    return total > 0 ? `Importing data files… ${done} / ${total}` : 'Importing study package…';
  }
  if (ev.next_task === 'unzip' || ev.phase === 'unzipped') return 'Extracting package…';
  if (ev.next_task === 'create' || ev.phase === 'study_created') return 'Creating study metadata…';
  if (ev.next_task === 'finalize' || ev.phase === 'completed') return 'Finalizing package…';
  return 'Importing study package…';
}

async function onSubmit() {
  const main = mainFileForSubmit.value;
  if (!canSubmit.value || !main) return;
  submitting.value = true;
  apiError.value = '';
  importProgress.value = '';
  try {
    const ext = mainFileExtension(main);

    if (ext === 'zip') {
      importProgress.value = 'Uploading package…';
      const uploadId = await uploadPackageZip(main, (ev) => {
        importProgress.value = formatPackageProgress(ev);
      });
      try {
        const u = new URL(window.location.href);
        u.searchParams.set('upload_id', uploadId);
        window.history.replaceState({}, '', u.toString());
      } catch {
        /* ignore */
      }
      const res = await runPackageImport(
        uploadId,
        { repositoryid: selectedRepo.value, overwrite: overwrite.value },
        (ev) => {
          importProgress.value = formatPackageProgress(ev);
        }
      );
      const sid = res.sid;
      if (sid) {
        window.location.href = catalogEditBase.value + encodeURIComponent(sid);
      }
      return;
    }

    const fd = new FormData();
    fd.append('repositoryid', selectedRepo.value);
    if (overwrite.value) fd.append('overwrite', 'yes');
    fd.append('file', main);
    const rdf = normalizeFile(rdfFileModel.value);
    if (rdf) fd.append('rdf', rdf);
    const res = await postImport(fd);
    const sid = res.sid;
    if (sid) {
      window.location.href = catalogEditBase.value + encodeURIComponent(sid);
    }
  } catch (e) {
    apiError.value = formatImportApiError(e);
  } finally {
    submitting.value = false;
    importProgress.value = '';
  }
}

async function loadCollections() {
  loadingCollections.value = true;
  loadError.value = '';
  try {
    const rows = await fetchCollectionsForUpload();
    collections.value = rows.map(c => ({
      repositoryid: c.repositoryid,
      displayLabel: collectionDisplayLabel(c),
    }));
    const def = defaultRepositoryid.value;
    const ids = new Set(collections.value.map(c => c.repositoryid));
    if (def && ids.has(def)) {
      selectedRepo.value = def;
    } else if (collections.value.length) {
      selectedRepo.value = collections.value[0].repositoryid;
    }
  } catch (e) {
    loadError.value = e?.response?.data?.message || e?.message || 'Failed to load collections';
    collections.value = [];
  } finally {
    loadingCollections.value = false;
  }
}

onMounted(() => {
  loadCollections();
});
</script>

<style scoped>
.error-pre-wrap :deep(pre) {
  white-space: pre-wrap;
  margin: 0;
  font-family: inherit;
  font-size: 0.875rem;
}

.api-error-json {
  white-space: pre-wrap;
  word-break: break-word;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

@media (min-width: 1280px) {
  .admin-upload-row {
    align-items: stretch;
  }
}
</style>
