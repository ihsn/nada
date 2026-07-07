<template>
  <v-app>
    <v-main class="catalog-study-overview-vue catalog-study-overview-compact pa-2 text-body-2">
      <v-overlay :model-value="loading && !dataset" class="align-center justify-center" persistent>
        <v-progress-circular indeterminate size="48" />
      </v-overlay>

      <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="4500">
        {{ snackbar.text }}
      </v-snackbar>

      <v-table v-if="dataset" density="compact" class="sov-overview-table">
        <tbody>
          <tr>
            <td class="sov-label-cell text-caption text-medium-emphasis">{{ lbl.ref_no }}</td>
            <td>{{ dataset.idno }}</td>
          </tr>

          <tr>
            <td class="sov-label-cell text-caption text-medium-emphasis">{{ lbl.created }}</td>
            <td>{{ formatTs(dataset.created) }}</td>
          </tr>

          <tr>
            <td class="sov-label-cell text-caption text-medium-emphasis">{{ lbl.last_changed }}</td>
            <td>{{ formatTs(dataset.changed) }}</td>
          </tr>

          <tr>
            <td class="sov-label-cell text-caption text-medium-emphasis">{{ lbl.year }}</td>
            <td>{{ yearsText }}</td>
          </tr>

          <tr>
            <td class="sov-label-cell text-caption text-medium-emphasis">{{ lbl.country }}</td>
            <td>
              <div class="sov-chip-row">
                <v-chip
                  v-for="c in dataset.countries || []"
                  :key="'ct-' + (c.cid ?? c.iso ?? c.name)"
                  size="x-small"
                  class="mr-1 mb-1"
                  :color="countryNeedsFix(c) ? 'error' : undefined"
                  :variant="countryNeedsFix(c) ? 'flat' : 'tonal'"
                  :title="countryNeedsFix(c) ? lbl.fix_country : undefined"
                >
                  <a
                    v-if="countryNeedsFix(c)"
                    :href="countriesMappingUrl"
                    class="sov-country-fix-link"
                  >{{ c.name }}</a>
                  <template v-else>{{ c.name }}</template>
                </v-chip>
              </div>
            </td>
          </tr>

          <tr>
            <td class="sov-label-cell text-caption text-medium-emphasis">{{ lbl.folder }}</td>
            <td>
              <div class="sov-folder-cell">
                <div class="sov-folder-row-main">
                  <span class="sov-folder-path">{{ folderPathDisplay }}</span>
                  <template v-if="folderStatus === undefined">
                    <v-progress-circular indeterminate size="16" width="2" class="align-middle" />
                  </template>
                  <template v-else-if="folderStatus && folderStatus.exists">
                    <v-icon color="success" size="22" class="align-middle" :title="lbl.study_folder_exists_disk">mdi-check-circle</v-icon>
                  </template>
                </div>
                <template v-if="folderStatus && !folderStatus.exists">
                  <div class="sov-folder-missing mt-2 text-body-2">
                    <span class="text-warning">{{ lbl.study_folder_missing_disk }}</span>
                    <v-btn
                      variant="plain"
                      density="compact"
                      color="primary"
                      class="sov-folder-create-link d-inline-flex px-1 ms-1"
                      min-width="0"
                      height="auto"
                      :ripple="false"
                      :loading="creatingFolder"
                      :disabled="creatingFolder || loading"
                      @click="createStudyFolder"
                    >{{ lbl.study_folder_create }}</v-btn>
                  </div>
                </template>
              </div>
            </td>
          </tr>

          <tr>
            <td class="sov-label-cell text-caption text-medium-emphasis">{{ lbl.repository }}</td>
            <td>
              <v-chip v-if="dataset.repositoryid" size="x-small" color="primary" variant="flat" class="mr-1 mb-1">
                {{ String(dataset.repositoryid).toUpperCase() }}
              </v-chip>
              <v-chip
                v-for="r in linkedRepoRows"
                :key="'sr-' + r.repositoryid"
                size="x-small"
                color="info"
                variant="tonal"
                class="mr-1 mb-1"
              >
                {{ String(r.repositoryid).toUpperCase() }}
              </v-chip>
              <span v-if="!dataset.repositoryid && linkedRepoRows.length === 0" class="text-medium-emphasis">N/A</span>
            </td>
          </tr>

          <tr>
            <td class="sov-label-cell text-caption text-medium-emphasis">{{ lbl.metadata_in_pdf }}</td>
            <td>
              <div class="sov-flex">
                <v-btn
                  v-if="pdfStatus === 'na' || pdfStatus === 'outdated'"
                  color="primary"
                  size="small"
                  variant="flat"
                  :href="pdfSetupUrl"
                >{{ lbl.generate_pdf }}</v-btn>
                <v-btn
                  v-if="pdfStatus !== 'na'"
                  size="small"
                  variant="tonal"
                  :href="pdfDeleteUrl"
                >{{ lbl.delete }}</v-btn>
                <v-chip v-if="pdfStatus === 'na'" size="small" color="warning" variant="flat">
                  <v-icon size="16" start>mdi-alert</v-icon>{{ lbl.pdf_not_generated }}
                </v-chip>
                <v-chip v-else-if="pdfStatus === 'uptodate'" size="small" color="success" variant="flat">
                  <v-icon size="16" start>mdi-check</v-icon>{{ lbl.pdf_uptodate }}
                </v-chip>
                <v-chip v-else size="small" color="warning" variant="flat">
                  <v-icon size="16" start>mdi-alert</v-icon>{{ lbl.pdf_outdated }}
                </v-chip>
              </div>
            </td>
          </tr>

          <tr>
            <td class="sov-label-cell text-caption text-medium-emphasis">{{ lbl.data_access }}</td>
            <td>
              <div v-if="dcEnabled" class="mb-2">
                <div class="text-caption text-medium-emphasis mb-1">{{ lbl.data_classification }}</div>
                <v-select
                  v-model="selectedClassCode"
                  :items="dcSelectItems"
                  item-title="title"
                  item-value="code"
                  density="compact"
                  variant="outlined"
                  hide-details="auto"
                  clearable
                  class="sov-field-full"
                  @update:model-value="onClassificationChange"
                />
              </div>

              <div class="text-caption text-medium-emphasis mb-1">{{ lbl.data_access }}</div>
              <v-select
                v-model="selectedAccessModel"
                :items="accessSelectItems"
                item-title="title"
                item-value="value"
                density="compact"
                variant="outlined"
                hide-details="auto"
                clearable
                class="sov-field-full mb-2"
                :placeholder="accessSelectItems.length === 0 ? lbl.loading : undefined"
              />

              <div v-if="isRemoteDataAccess" class="sov-remote-da-block">
                <div class="text-caption text-medium-emphasis mb-1">{{ lbl.remote_data_access_url }}</div>
                <v-text-field
                  id="remote-da"
                  v-model="remoteDa"
                  density="compact"
                  variant="outlined"
                  hide-details="auto"
                  clearable
                  class="sov-field-full"
                />
              </div>

              <v-btn class="sov-da-update-btn" size="small" variant="tonal" color="primary" :loading="savingDa" @click="saveDataAccess">{{ lbl.update }}</v-btn>

              <div
                :class="'study-microdata model-' + (dataset.data_access_type || '')"
                class="sov-microdata-applies"
              >
                <template v-if="microRows.length">
                  <div class="sov-applies-caption text-caption text-medium-emphasis">
                    {{ lbl.data_selection_apply_to_files }}
                  </div>
                  <ul class="sov-microdata-files-list text-body-2">
                    <li v-for="(mf, idx) in microRows" :key="'mf-' + idx">
                      {{ microLabel(mf) }}
                    </li>
                  </ul>
                </template>
                <template v-else>
                  <div class="text-error text-caption">{{ lbl.study_no_data_files_assigned }}</div>
                </template>
              </div>
            </td>
          </tr>

          <tr>
            <td class="sov-label-cell text-caption text-medium-emphasis">{{ lbl.indicator_database }}</td>
            <td>
              <div class="sov-flex sov-link-row">
                <v-text-field
                  v-model="linkIndicator"
                  density="compact"
                  variant="outlined"
                  hide-details="auto"
                  clearable
                  placeholder="URL"
                  class="sov-link-field"
                />
                <v-btn size="small" variant="tonal" color="primary" class="sov-link-btn" :loading="savingLinks" @click="saveLinks">{{ lbl.update }}</v-btn>
              </div>
            </td>
          </tr>

          <tr>
            <td class="sov-label-cell text-caption text-medium-emphasis">{{ lbl.study_website }}</td>
            <td>
              <div class="sov-flex sov-link-row">
                <v-text-field
                  v-model="linkStudy"
                  density="compact"
                  variant="outlined"
                  hide-details="auto"
                  clearable
                  placeholder="URL"
                  class="sov-link-field"
                />
                <v-btn size="small" variant="tonal" color="primary" class="sov-link-btn" :loading="savingLinks" @click="saveLinks">{{ lbl.update }}</v-btn>
              </div>
            </td>
          </tr>

          <tr>
            <td class="sov-label-cell text-caption text-medium-emphasis">{{ lbl.featured_study }}</td>
            <td>
              <v-checkbox
                v-model="featured"
                density="compact"
                hide-details
                color="primary"
                :label="lbl.mark_as_featured"
                @update:model-value="onFeaturedChange"
              />
            </td>
          </tr>

          <tr class="sov-row-tags">
            <td class="sov-label-cell text-caption text-medium-emphasis">{{ lbl.tags }}</td>
            <td>
              <v-combobox
                :model-value="tagModel"
                :items="[]"
                :placeholder="lbl.add_tags_placeholder"
                :chip-props="{ size: 'small' }"
                multiple
                chips
                closable-chips
                clearable
                variant="outlined"
                density="compact"
                hide-details="auto"
                :loading="tagSaving"
                :disabled="tagSaving || loading"
                hide-no-data
                class="sov-tags-combo sov-field-full"
                @update:model-value="onTagsModelUpdate"
              />
            </td>
          </tr>

          <tr class="sov-row-collections">
            <td class="sov-label-cell text-caption text-medium-emphasis">{{ lbl.study_collections }}</td>
            <td>
              <v-autocomplete
                v-model="selectedLinkedIds"
                :items="collectionItems"
                item-title="name"
                item-value="id"
                multiple
                chips
                closable-chips
                clearable
                variant="outlined"
                density="compact"
                hide-details="auto"
                :loading="collectionsSaving"
                :disabled="collectionsSaving || loading"
                class="sov-collections-select sov-field-full"
                @update:model-value="onLinkedCollectionsUpdate"
              />
            </td>
          </tr>

          <tr>
            <td class="sov-label-cell text-caption text-medium-emphasis">{{ lbl.study_aliases }}</td>
            <td>
              <div class="sov-alias-block">
                <div class="sov-flex sov-link-row">
                  <v-text-field
                    v-model="aliasInput"
                    class="sov-alias-field"
                    density="compact"
                    hide-details
                    variant="outlined"
                    clearable
                    @keyup.enter="addAlias"
                  />
                  <v-btn size="small" variant="tonal" color="primary" class="sov-link-btn" :loading="aliasSaving" @click="addAlias">{{ lbl.update }}</v-btn>
                </div>
                <div v-if="aliasRows.length" class="sov-alias-chips">
                  <v-chip
                    v-for="a in aliasRows"
                    :key="'alias-' + a.id"
                    closable
                    size="small"
                    variant="tonal"
                    color="primary"
                    :disabled="aliasSaving"
                    @click:close="removeAlias(a)"
                  >
                    {{ a.alternate_id }}
                  </v-chip>
                </div>
              </div>
            </td>
          </tr>

          <tr>
            <td class="sov-label-cell text-caption text-medium-emphasis">{{ lbl.doi_label }}</td>
            <td>
              <div class="sov-flex sov-link-row">
                <v-text-field
                  v-model="doiField"
                  density="compact"
                  variant="outlined"
                  hide-details="auto"
                  clearable
                  placeholder="DOI"
                  class="sov-link-field"
                />
                <v-btn size="small" variant="tonal" color="primary" class="sov-link-btn" :loading="doiSaving" @click="saveDoi">{{ lbl.update }}</v-btn>
              </div>
            </td>
          </tr>
        </tbody>
      </v-table>
    </v-main>
  </v-app>
</template>

<script setup>
import { computed, nextTick, onMounted, ref } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useStudyOverviewApi } from './composables/useStudyOverviewApi';

defineOptions({ name: 'CatalogStudyOverviewApp' });

const { config } = useAppConfig();
const api = useStudyOverviewApi();

const loading = ref(true);
const dataset = ref(null);
/** `undefined` = loading; `null` = status request failed; object = API payload */
const folderStatus = ref(undefined);
const creatingFolder = ref(false);
const tagRows = ref([]);
const collectionItems = ref([]);
const selectedLinkedIds = ref([]);
const syncingLinkedCollections = ref(false);
const dcEnabled = ref(false);
const dcRows = ref([]);
const accessOptions = ref([]);

const dcSelectItems = computed(() =>
  (dcRows.value || []).map((row) => ({
    title: row.title,
    code: row.code != null ? String(row.code) : '',
  }))
);

const accessSelectItems = computed(() =>
  (accessOptions.value || []).map((o) => ({
    title: o.fname,
    value: o.model,
  }))
);

/** Data access model `remote` — show external repository URL field */
const isRemoteDataAccess = computed(() => String(selectedAccessModel.value || '').toLowerCase() === 'remote');

const selectedClassCode = ref('');
const selectedAccessModel = ref('');
const remoteDa = ref('');
const linkIndicator = ref('');
const linkStudy = ref('');
const doiField = ref('');
const featured = ref(false);

const tagModel = ref([]);
const syncingTags = ref(false);
const aliasInput = ref('');

const savingDa = ref(false);
const savingLinks = ref(false);
const doiSaving = ref(false);
const tagSaving = ref(false);
const aliasSaving = ref(false);
const collectionsSaving = ref(false);

const snackbar = ref({ show: false, text: '', color: 'success' });

const lbl = computed(() => ({
  ref_no: 'Reference',
  created: 'Created',
  last_changed: 'Last changed',
  study_aliases: 'Aliases',
  year: 'Year',
  country: 'Country',
  folder: 'Folder',
  study_folder_exists_disk: 'Folder exists on the server.',
  study_folder_missing_disk: 'The study folder does not exist.',
  study_folder_create: 'Create folder',
  repository: 'Repository',
  metadata_in_pdf: 'PDF',
  generate_pdf: 'Generate PDF',
  delete: 'Delete',
  pdf_not_generated: 'PDF not generated',
  pdf_uptodate: 'Up to date',
  pdf_outdated: 'Outdated',
  data_access: 'Data access',
  data_classification: 'Classification',
  remote_data_access_url: 'Remote data URL',
  indicator_database: 'Indicator',
  study_website: 'Website',
  featured_study: 'Featured',
  mark_as_featured: 'Mark featured',
  tags: 'Tags',
  add_tags_placeholder: 'Add tags (type and press Enter)',
  study_collections: 'Collections',
  doi_label: 'DOI',
  update: 'Update',
  saved: 'Saved',
  loading: '…',
  fix_country: 'Fix country',
  data_selection_apply_to_files: 'Applies to files',
  study_no_data_files_assigned: 'No microdata files',
  ...((config.value && config.value.labels) || {}),
}));

const pdfSetupUrl = computed(() => config.value?.pdfSetupUrl || '#');
const pdfDeleteUrl = computed(() => config.value?.pdfDeleteUrl || '#');
const countriesMappingUrl = computed(() => config.value?.countriesMappingUrl || '#');

const folderPathDisplay = computed(() => {
  const d = dataset.value;
  const fs = folderStatus.value;
  const p = (d && d.folder_path) || (fs && fs.folder_path) || '';
  return p || '—';
});

const aliasRows = computed(() => dataset.value?.aliases || []);

const yearsText = computed(() => {
  const d = dataset.value;
  if (!d) return '';
  const ys = [d.year_start, d.year_end].filter((x) => x != null && String(x).trim() !== '');
  const u = [...new Set(ys)];
  return u.join(' — ');
});

const pdfStatus = computed(() => dataset.value?.pdf_documentation?.status || 'na');

const linkedRepoRows = computed(() => {
  const owner = dataset.value?.repositoryid ? String(dataset.value.repositoryid).toLowerCase() : '';
  const list = dataset.value?.survey_repos || [];
  return list.filter((r) => {
    const rid = r.repositoryid ? String(r.repositoryid).toLowerCase() : '';
    return rid && rid !== owner;
  });
});

const microRows = computed(() => dataset.value?.microdata_files || []);

function microLabel(mf) {
  const n = mf.filename || mf.title || mf.resource_id || '';
  if (!n) return JSON.stringify(mf);
  const parts = String(n).split('/');
  return parts[parts.length - 1];
}

/** ISO 8601 from API (`date("c")`) or legacy numeric unix */
function formatTs(value) {
  if (value == null || value === '') return '—';
  if (typeof value === 'number' && !Number.isNaN(value)) {
    const d = new Date(value * 1000);
    return Number.isNaN(d.getTime()) ? '—' : d.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
  }
  const d = new Date(value);
  return Number.isNaN(d.getTime()) ? String(value) : d.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
}

function countryNeedsFix(c) {
  const v = c.cid ?? c.countryid ?? c.id;
  return parseInt(v, 10) < 1;
}

function tagLabel(t) {
  return t.tag ?? t.name ?? String(t);
}

function normalizeTagStrings(arr) {
  const seen = new Set();
  const out = [];
  for (const x of arr || []) {
    const s = String(x ?? '').trim();
    if (!s) continue;
    const k = s.toLowerCase();
    if (seen.has(k)) continue;
    seen.add(k);
    out.push(s);
  }
  return out;
}

function applyTagsFromServer(tags) {
  syncingTags.value = true;
  tagRows.value = tags;
  tagModel.value = normalizeTagStrings(tags.map(tagLabel));
  nextTick(() => {
    syncingTags.value = false;
  });
}

function tagSetsEqual(next, prev) {
  const A = normalizeTagStrings(next);
  const B = normalizeTagStrings(prev);
  if (A.length !== B.length) return false;
  const setB = new Set(B.map((s) => s.toLowerCase()));
  return A.every((s) => setB.has(s.toLowerCase()));
}

function normRepo(s) {
  return String(s ?? '').trim().toLowerCase();
}

function linkedRepoIdsFromDataset(ds) {
  if (!ds) return [];
  const owner = normRepo(ds.repositoryid);
  const out = [];
  const seen = new Set();
  for (const sr of ds.survey_repos || []) {
    const rid = sr.repositoryid != null ? String(sr.repositoryid).trim() : '';
    if (!rid || normRepo(rid) === owner) continue;
    const k = normRepo(rid);
    if (seen.has(k)) continue;
    seen.add(k);
    out.push(sr.repositoryid);
  }
  return out;
}

function buildCollectionItems(apiRows, linkedIds, ownerRepo) {
  const owner = normRepo(ownerRepo);
  const map = new Map();
  for (const row of apiRows || []) {
    const id = row.repositoryid ?? row.id ?? row.code;
    if (!id || normRepo(id) === owner) continue;
    map.set(normRepo(id), {
      id,
      name: row.title ?? row.name ?? String(id),
      code: row.repositoryid ?? row.code ?? id,
      count: row.count ?? 0,
    });
  }
  for (const lid of linkedIds) {
    const k = normRepo(lid);
    if (!k || k === owner) continue;
    if (!map.has(k)) {
      map.set(k, { id: lid, name: String(lid), code: lid, count: 0 });
    }
  }
  return [...map.values()].sort((a, b) =>
    String(a.name).localeCompare(String(b.name), undefined, { sensitivity: 'base' })
  );
}

function resolveLinkedSelection(linkedIds, items) {
  return linkedIds.map((lid) => {
    const m = items.find((it) => normRepo(it.id) === normRepo(lid));
    return m ? m.id : lid;
  });
}

function repoIdSetsEqual(a, b) {
  const A = new Set((a || []).map(normRepo));
  const B = new Set((b || []).map(normRepo));
  if (A.size !== B.size) return false;
  for (const x of A) if (!B.has(x)) return false;
  return true;
}

function applyLinkedCollectionsFromServer(study, collectionRows) {
  syncingLinkedCollections.value = true;
  const linkedIds = linkedRepoIdsFromDataset(study);
  collectionItems.value = buildCollectionItems(
    collectionRows || [],
    linkedIds,
    study.repositoryid
  );
  selectedLinkedIds.value = resolveLinkedSelection(linkedIds, collectionItems.value);
  nextTick(() => {
    syncingLinkedCollections.value = false;
  });
}

async function onLinkedCollectionsUpdate(next) {
  if (syncingLinkedCollections.value || loading.value) return;
  const ids = Array.isArray(next) ? next : [];
  const prev = linkedRepoIdsFromDataset(dataset.value);
  if (repoIdSetsEqual(ids, prev)) return;

  collectionsSaving.value = true;
  try {
    await api.postLinkedCollectionsReplace(ids);
    snackbar.value = { show: true, text: lbl.value.saved, color: 'success' };
    await reloadAll();
  } catch (e) {
    snackbar.value = { show: true, text: e.message || String(e), color: 'error' };
    await reloadAll();
  } finally {
    collectionsSaving.value = false;
  }
}

async function createStudyFolder() {
  creatingFolder.value = true;
  try {
    await api.postCreateStudyFolder();
    snackbar.value = { show: true, text: lbl.value.saved, color: 'success' };
    await reloadAll();
  } catch (e) {
    snackbar.value = { show: true, text: e.message || String(e), color: 'error' };
  } finally {
    creatingFolder.value = false;
  }
}

async function reloadAll() {
  loading.value = true;
  folderStatus.value = undefined;
  try {
    const [study, fs] = await Promise.all([
      api.fetchStudy(),
      api.fetchFolderStatus().catch(() => null),
    ]);
    dataset.value = study;

    if (fs && fs.status === 'success') {
      folderStatus.value = {
        exists: !!fs.exists,
        folder_path: fs.folder_path ?? null,
        folder_path_full: fs.folder_path_full ?? null,
      };
    } else {
      folderStatus.value = null;
    }

    const siteDcOff = config.value?.dataClassificationsEnabled === false;
    const dcPromise = siteDcOff
      ? Promise.resolve({ data_classifications_enabled: false, codelist: [] })
      : api.fetchDataClassifications();

    const [tags, dc, listCollections] = await Promise.all([
      api.fetchTags(),
      dcPromise,
      api.fetchListCollections(),
    ]);

    applyTagsFromServer(tags);
    applyLinkedCollectionsFromServer(study, listCollections);

    dcEnabled.value = siteDcOff ? false : !!dc.data_classifications_enabled;
    dcRows.value = siteDcOff ? [] : dc.codelist || [];

    selectedClassCode.value = study.data_class_code || '';
    selectedAccessModel.value = study.data_access_type || '';
    remoteDa.value = study.remote_data_url || '';
    linkIndicator.value = study.link_indicator || '';
    linkStudy.value = study.link_study || '';
    doiField.value = study.doi || '';
    featured.value = !!study.is_featured;

    const daClass = String(study.data_class_code || '').trim() || 'public';
    await loadAccessOptionsForClass(daClass);

    const models = new Set((accessOptions.value || []).map((o) => o.model));
    if (!models.has(selectedAccessModel.value)) {
      selectedAccessModel.value = accessOptions.value[0]?.model || '';
    }
  } catch (e) {
    snackbar.value = { show: true, text: e.message || String(e), color: 'error' };
    folderStatus.value = null;
  } finally {
    loading.value = false;
  }
}

async function loadAccessOptionsForClass(code) {
  const c = String(code || '').trim();
  if (!c) {
    accessOptions.value = [];
    return;
  }
  try {
    accessOptions.value = await api.fetchDataAccessOptions(c);
  } catch (e) {
    snackbar.value = { show: true, text: e.message || String(e), color: 'error' };
    accessOptions.value = [];
  }
}

async function onClassificationChange() {
  await loadAccessOptionsForClass(selectedClassCode.value);
  const models = new Set((accessOptions.value || []).map((o) => o.model));
  if (selectedAccessModel.value && !models.has(selectedAccessModel.value)) {
    selectedAccessModel.value = accessOptions.value[0]?.model || '';
  }
}

async function saveDataAccess() {
  savingDa.value = true;
  try {
    const payload = {
      access_policy: selectedAccessModel.value ?? '',
      data_remote_url: remoteDa.value ?? '',
    };
    if (dcEnabled.value && selectedClassCode.value) {
      payload.data_classification = selectedClassCode.value;
    }
    await api.postOptions(payload);
    snackbar.value = { show: true, text: lbl.value.saved, color: 'success' };
    await reloadAll();
  } catch (e) {
    snackbar.value = { show: true, text: e.message || String(e), color: 'error' };
  } finally {
    savingDa.value = false;
  }
}

async function saveLinks() {
  savingLinks.value = true;
  try {
    await api.postOptions({
      link_indicator: linkIndicator.value ?? '',
      link_study: linkStudy.value ?? '',
    });
    snackbar.value = { show: true, text: lbl.value.saved, color: 'success' };
    await reloadAll();
  } catch (e) {
    snackbar.value = { show: true, text: e.message || String(e), color: 'error' };
  } finally {
    savingLinks.value = false;
  }
}

async function saveDoi() {
  doiSaving.value = true;
  try {
    await api.postDoi(doiField.value ?? '');
    snackbar.value = { show: true, text: lbl.value.saved, color: 'success' };
    await reloadAll();
  } catch (e) {
    snackbar.value = { show: true, text: e.message || String(e), color: 'error' };
  } finally {
    doiSaving.value = false;
  }
}

async function onTagsModelUpdate(nextRaw) {
  if (syncingTags.value || loading.value) return;
  const next = normalizeTagStrings(Array.isArray(nextRaw) ? nextRaw : []);
  const prev = normalizeTagStrings(tagRows.value.map(tagLabel));
  if (tagSetsEqual(next, prev)) return;
  const prevSet = new Set(prev.map((s) => s.toLowerCase()));
  const nextSet = new Set(next.map((s) => s.toLowerCase()));
  const toRemove = prev.filter((s) => !nextSet.has(s.toLowerCase()));
  const toAdd = next.filter((s) => !prevSet.has(s.toLowerCase()));
  if (toRemove.length === 0 && toAdd.length === 0) return;

  tagSaving.value = true;
  try {
    for (const s of toRemove) {
      const row = tagRows.value.find((r) => tagLabel(r).toLowerCase() === s.toLowerCase());
      if (row) await api.deleteTagRow(row);
    }
    if (toAdd.length) await api.postTags(toAdd);
    snackbar.value = { show: true, text: lbl.value.saved, color: 'success' };
    await reloadAll();
  } catch (e) {
    snackbar.value = { show: true, text: e.message || String(e), color: 'error' };
    await reloadAll();
  } finally {
    tagSaving.value = false;
  }
}

async function addAlias() {
  const a = String(aliasInput.value || '').trim();
  if (!a) return;
  aliasSaving.value = true;
  try {
    await api.postAliases([a]);
    aliasInput.value = '';
    snackbar.value = { show: true, text: lbl.value.saved, color: 'success' };
    await reloadAll();
  } catch (e) {
    snackbar.value = { show: true, text: e.message || String(e), color: 'error' };
  } finally {
    aliasSaving.value = false;
  }
}

async function removeAlias(row) {
  aliasSaving.value = true;
  try {
    await api.deleteAliasRow(row);
    snackbar.value = { show: true, text: lbl.value.saved, color: 'success' };
    await reloadAll();
  } catch (e) {
    snackbar.value = { show: true, text: e.message || String(e), color: 'error' };
  } finally {
    aliasSaving.value = false;
  }
}

async function onFeaturedChange(v) {
  try {
    await api.postOptions({ featured: !!v });
    featured.value = !!v;
    snackbar.value = { show: true, text: lbl.value.saved, color: 'success' };
    await reloadAll();
  } catch (e) {
    snackbar.value = { show: true, text: e.message || String(e), color: 'error' };
    await reloadAll();
  }
}

onMounted(() => {
  reloadAll();
});
</script>

<style scoped>
.sov-folder-cell {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  max-width: 100%;
}
.sov-folder-row-main {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  max-width: 100%;
}
.sov-folder-path {
  word-break: break-word;
}
.sov-folder-missing {
  width: 100%;
  max-width: 100%;
  line-height: 1.5;
}
.sov-folder-create-link {
  vertical-align: baseline;
  text-decoration: underline;
  text-underline-offset: 2px;
  font: inherit;
  letter-spacing: inherit;
  text-transform: none;
}
.sov-folder-create-link :deep(.v-btn__overlay) {
  opacity: 0;
}
.sov-folder-create-link :deep(.v-btn__content) {
  gap: 0;
}
.catalog-study-overview-vue {
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
}
.catalog-study-overview-compact {
  font-size: 0.8125rem;
  line-height: 1.45;
}
.catalog-study-overview-vue .sov-overview-table {
  width: 100%;
  table-layout: fixed;
}
.catalog-study-overview-vue .sov-overview-table :deep(table) {
  width: 100%;
  table-layout: fixed;
}
.catalog-study-overview-vue .sov-label-cell {
  width: 168px;
  max-width: 168px;
  white-space: normal;
  word-break: break-word;
  overflow-wrap: break-word;
  vertical-align: top;
}
.catalog-study-overview-vue .sov-overview-table :deep(td) {
  vertical-align: top;
  padding: 10px 12px;
}
.catalog-study-overview-vue .sov-overview-table :deep(td:not(.sov-label-cell)) {
  word-break: break-word;
}
.sov-chip-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 4px 6px;
}
.sov-field-full {
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
}
.sov-country-fix-link {
  color: inherit;
  text-decoration: underline;
}
.sov-microdata-applies {
  margin-top: 18px;
}
.sov-applies-caption {
  margin-top: 4px;
  margin-bottom: 10px;
}
.sov-microdata-files-list {
  margin: 0;
  padding-left: 1.25rem;
  list-style-type: disc;
}
.sov-microdata-files-list li {
  margin-bottom: 6px;
  padding-left: 2px;
}
.sov-microdata-files-list li:last-child {
  margin-bottom: 0;
}
.sov-da-update-btn {
  margin-top: 16px;
}
.sov-flex {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}
.sov-link-row {
  align-items: center;
  width: 100%;
  max-width: 100%;
}
.catalog-study-overview-vue .sov-link-field {
  flex: 1;
  min-width: 0;
}
.sov-link-btn {
  flex-shrink: 0;
}
.sov-alias-block {
  width: 100%;
}
.sov-alias-field {
  flex: 1;
  min-width: 0;
}
.sov-alias-field :deep(.v-input),
.sov-alias-field :deep(.v-field) {
  width: 100%;
  max-width: 100%;
}
.sov-alias-chips {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px 8px;
  margin-top: 12px;
}
.sov-tags-combo {
  width: 100%;
  max-width: 100%;
}
.catalog-study-overview-vue .sov-row-tags td {
  padding-top: 12px;
  padding-bottom: 12px;
  vertical-align: top;
}
.sov-tags-combo :deep(.v-input),
.sov-tags-combo :deep(.v-field) {
  width: 100%;
  max-width: 100%;
}
.sov-tags-combo :deep(.v-field__input) {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px 4px;
  padding-top: 4px;
  padding-bottom: 4px;
  min-height: 34px;
}
.sov-tags-combo :deep(.v-chip) {
  font-size: 0.75rem;
  line-height: 1.25;
  min-height: 22px;
  height: auto;
  padding-inline: 6px;
  padding-block: 1px;
  margin-block: 1px;
}
.sov-tags-combo :deep(.v-chip .v-chip__close) {
  font-size: 1rem;
}
.sov-collections-select :deep(.v-input),
.sov-collections-select :deep(.v-field) {
  width: 100%;
  max-width: 100%;
}
.mt-1 {
  margin-top: 6px;
}
.mb-2 {
  margin-bottom: 8px;
}
</style>
