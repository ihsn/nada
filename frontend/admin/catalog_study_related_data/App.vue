<template>
  <v-app>
    <v-main class="catalog-study-related-data-vue">
      <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="4500">
        {{ snackbar.text }}
      </v-snackbar>

      <div class="csc-top-row">
        <div class="csc-top-left d-flex flex-wrap align-center gap-2">
          <div class="text-subtitle-1 font-weight-bold">{{ lbl.title || 'Related studies' }}</div>
        </div>
        <div class="csc-top-right d-flex flex-wrap align-center justify-end gap-1">
          <v-btn
            v-if="legacy.fullPageAttachUrl"
            size="small"
            variant="text"
            :href="legacy.fullPageAttachUrl"
          >
            {{ lbl.manage_in_legacy || 'Manage in legacy' }}
          </v-btn>
          <v-btn color="primary" size="small" variant="flat" class="text-none" @click="openAttachDialog">
            {{ lbl.attach_related_cta || 'Attach related' }}
          </v-btn>
        </div>
      </div>

      <v-card variant="flat" elevation="0" rounded="lg" class="csc-section">
        <div class="text-subtitle-2 font-weight-semibold mb-2">
          {{ lbl.attached || 'Related studies' }}
          <span class="text-caption text-medium-emphasis font-weight-regular">({{ attachedRows.length }})</span>
        </div>
        <v-data-table
          :headers="attachedHeaders"
          :items="attachedRows"
          item-value="related_sid"
          density="compact"
          class="csc-table elevation-0 border rounded"
          :items-per-page="-1"
          hide-default-footer
        >
          <template #item.title="{ item }">
            <a :href="studyEditUrl(item.related_sid)" class="text-primary text-decoration-none font-weight-medium">{{
              item.title
            }}</a>
            <div class="text-caption text-medium-emphasis">
              {{ item.idno }}
            </div>
          </template>
          <template #item.meta="{ item }">
            <span class="text-caption">{{ item.nation }}, {{ item.year_start }}</span>
          </template>
          <template #item.relationship_id="{ item }">
            <v-select
              :model-value="item.relationship_id"
              :items="relationshipTypeItems"
              item-title="label"
              item-value="id"
              density="compact"
              variant="outlined"
              hide-details
              class="csr-rel-select"
              style="min-width: 220px"
              :loading="busyRelSid === item.related_sid"
              @update:model-value="(v) => onRelationshipChange(item, v)"
            />
          </template>
          <template #item.actions="{ item }">
            <v-btn
              size="x-small"
              variant="text"
              color="error"
              icon="mdi-delete"
              :loading="busyDetachSid === item.related_sid"
              :title="lbl.remove || 'Remove'"
              @click.prevent="removeAttached(item)"
            />
          </template>
        </v-data-table>
      </v-card>

      <v-dialog
        v-model="attachDialog"
        fullscreen
        transition="dialog-bottom-transition"
      >
        <v-card variant="flat" elevation="0" class="csc-dialog-card d-flex flex-column h-100 rounded-0">
          <v-card-title class="d-flex flex-wrap align-center gap-2 py-4 px-4 flex-shrink-0">
            <span class="text-h6 font-weight-semibold">{{ lbl.attach_related_cta || 'Attach related' }}</span>
            <v-spacer />
            <v-btn icon variant="text" density="comfortable" :title="lbl.close || 'Close'" @click="attachDialog = false">
              <v-icon>mdi-close</v-icon>
            </v-btn>
          </v-card-title>
          <v-divider />
          <v-card-text class="pa-4 flex-grow-1 d-flex flex-column overflow-hidden" style="min-height: 0">
            <div class="d-flex flex-wrap align-center gap-2 mb-3">
              <v-select
                v-model="searchField"
                :items="fieldItems"
                item-title="title"
                item-value="value"
                density="compact"
                variant="outlined"
                hide-details
                style="min-width: 140px"
              />
              <v-text-field
                v-model="keywords"
                hide-details
                density="compact"
                variant="outlined"
                :label="lbl.search || 'Search'"
                class="csc-keywords flex-grow-1"
                @keyup.enter="runSearch(true)"
              />
              <v-btn color="primary" size="small" class="text-none" @click="runSearch(true)">
                {{ lbl.search || 'Search' }}
              </v-btn>
              <v-btn size="small" variant="text" class="text-none" @click="resetSearch">{{ lbl.reset || 'Reset' }}</v-btn>
            </div>

            <div class="text-caption text-medium-emphasis mb-2 flex-shrink-0">{{ searchTotal }} result(s)</div>

            <div class="csc-dialog-table-wrap flex-grow-1 overflow-auto min-height-0">
            <v-data-table
              :headers="searchHeaders"
              :items="searchRows"
              item-value="id"
              density="compact"
              class="csc-table elevation-0 border rounded"
              :items-per-page="-1"
              hide-default-footer
            >
              <template #item.is_attached="{ item }">
                <v-btn
                  size="x-small"
                  variant="text"
                  :color="item.is_attached ? 'success' : 'primary'"
                  :icon="item.is_attached ? 'mdi-link-variant' : 'mdi-link-variant-plus'"
                  :loading="busySearchSid === item.id"
                  :title="item.is_attached ? (lbl.remove || 'Remove') : (lbl.add || 'Attach')"
                  @click="toggleSearchRow(item)"
                />
              </template>
              <template #item.title="{ item }">
                <div class="font-weight-medium">{{ item.title }}</div>
                <div class="text-caption text-medium-emphasis">{{ item.idno }}</div>
              </template>
              <template #item.meta="{ item }">
                <span class="text-caption">{{ item.nation }}, {{ item.year_start }}</span>
              </template>
              <template #item.relationship_pick="{ item }">
                <v-select
                  :model-value="relationshipSelectModel(item)"
                  :items="relationshipTypeItems"
                  item-title="label"
                  item-value="id"
                  density="compact"
                  variant="outlined"
                  hide-details
                  class="csr-rel-select"
                  style="min-width: 200px"
                  :loading="busyRelSearchSid === item.id"
                  @update:model-value="(v) => onSearchRowRelationshipChange(item, v)"
                />
              </template>
            </v-data-table>
            </div>

            <div class="d-flex align-center mt-3 gap-2 flex-wrap flex-shrink-0">
              <v-btn size="small" variant="text" :disabled="searchOffset <= 0" @click="prevPage">Prev</v-btn>
              <v-btn
                size="small"
                variant="text"
                :disabled="searchOffset + searchLimit >= searchTotal"
                @click="nextPage"
              >
                Next
              </v-btn>
              <span class="text-caption text-medium-emphasis">
                {{ searchOffset + 1 }}-{{ Math.min(searchOffset + searchRows.length, searchTotal) }}
              </span>
            </div>
          </v-card-text>
          <v-divider />
          <v-card-actions class="px-4 py-3 justify-end flex-shrink-0">
            <v-btn variant="text" class="text-none" @click="attachDialog = false">{{ lbl.close || 'Close' }}</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-main>
  </v-app>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useStudyRelatedDataApi } from './composables/useStudyRelatedDataApi';

const { config } = useAppConfig();
const { fetchAll, searchStudies, attachStudy, updateRelationship, detachStudy } = useStudyRelatedDataApi();

const lbl = computed(() => config.value?.labels || {});
const legacy = computed(() => config.value?.legacyUrls || {});

const attachedRows = ref([]);
const relationshipTypes = ref([]);
const relationshipTypeItems = computed(() =>
  relationshipTypes.value.map((t) => ({
    id: t.id,
    label: t.label,
  })),
);

const defaultRelationshipId = ref(0);

/** Per–search-row relationship choice for studies not yet attached (key = catalog study id). */
const relationshipPickBySid = ref({});

const searchRows = ref([]);
const searchTotal = ref(0);
const searchOffset = ref(0);
const searchLimit = ref(30);
const keywords = ref('');
const searchField = ref('title');

const busyRelSid = ref(null);
const busyDetachSid = ref(null);
const busySearchSid = ref(null);
const busyRelSearchSid = ref(null);

const snackbar = ref({ show: false, text: '', color: 'surface' });
const attachDialog = ref(false);

const fieldItems = computed(() => [
  { value: 'title', title: lbl.value.field_title || 'Title' },
  { value: 'nation', title: lbl.value.field_nation || 'Country' },
  { value: 'idno', title: lbl.value.field_idno || 'Survey ID' },
  { value: 'year_start', title: lbl.value.field_year_start || 'Year' },
  { value: 'authoring_entity', title: lbl.value.field_authoring_entity || 'Producer' },
]);

const attachedHeaders = computed(() => [
  { title: lbl.value.col_title || 'Title', key: 'title', sortable: false },
  { title: lbl.value.col_country_year || 'Country / year', key: 'meta', sortable: false, width: '140px' },
  { title: lbl.value.col_relationship || 'Relationship', key: 'relationship_id', sortable: false },
  { title: lbl.value.col_actions || 'Actions', key: 'actions', sortable: false, align: 'end', width: '72px' },
]);

const searchHeaders = computed(() => [
  { title: lbl.value.col_link || lbl.value.col_attached || 'Link', key: 'is_attached', sortable: false, width: '72px' },
  { title: lbl.value.col_title || 'Title', key: 'title', sortable: false },
  { title: lbl.value.col_country_year || 'Country / year', key: 'meta', sortable: false, width: '140px' },
  {
    title: lbl.value.col_relationship || 'Relationship',
    key: 'relationship_pick',
    sortable: false,
    width: '240px',
  },
]);

function pickDefaultRelationshipId() {
  const items = relationshipTypeItems.value;
  const d = defaultRelationshipId.value;
  if (d != null && items.some((t) => t.id === d)) return d;
  return items[0]?.id ?? 0;
}

function relationshipSelectModel(item) {
  if (item.is_attached) {
    const rid = Number(item.relationship_id);
    return rid || pickDefaultRelationshipId();
  }
  const picked = relationshipPickBySid.value[item.id];
  if (picked != null) return picked;
  return pickDefaultRelationshipId();
}

function ensureSearchRowPicks() {
  const def = pickDefaultRelationshipId();
  if (!def) return;
  const picks = { ...relationshipPickBySid.value };
  let changed = false;
  for (const r of searchRows.value) {
    if (!r.is_attached && picks[r.id] === undefined) {
      picks[r.id] = def;
      changed = true;
    }
  }
  if (changed) relationshipPickBySid.value = picks;
}

watch(
  () => [searchRows.value, relationshipTypeItems.value, defaultRelationshipId.value],
  () => {
    ensureSearchRowPicks();
  },
  { deep: true },
);

async function onSearchRowRelationshipChange(item, newId) {
  const relId = Number(newId);
  if (item.is_attached) {
    busyRelSearchSid.value = item.id;
    try {
      await updateRelationship(item.id, relId);
      showSnack(lbl.value.saved || 'Saved', 'success');
      await refreshAfterToggle();
    } catch (e) {
      showSnack(String(e?.message || e), 'error');
      await refreshAfterToggle();
    } finally {
      busyRelSearchSid.value = null;
    }
  } else {
    relationshipPickBySid.value = { ...relationshipPickBySid.value, [item.id]: relId };
  }
}

function showSnack(text, color = 'surface') {
  snackbar.value = { show: true, text, color };
}

function studyEditUrl(relatedSid) {
  const base = String(legacy.value?.editStudyBase || '').replace(/\/+$/, '');
  if (!base) return '#';
  return `${base}/${encodeURIComponent(String(relatedSid))}`;
}

async function loadAttached() {
  const data = await fetchAll();
  relationshipTypes.value = Array.isArray(data.relationship_types) ? data.relationship_types : [];
  if (
    relationshipTypes.value.length > 0 &&
    !relationshipTypes.value.some((t) => t.id === defaultRelationshipId.value)
  ) {
    defaultRelationshipId.value = relationshipTypes.value[0].id;
  }
  attachedRows.value = Array.isArray(data.related_studies) ? data.related_studies : [];
}

async function runSearch(resetOffset = false) {
  if (resetOffset) searchOffset.value = 0;
  const data = await searchStudies({
    field: searchField.value,
    keywords: keywords.value,
    offset: searchOffset.value,
    limit: searchLimit.value,
  });
  searchRows.value = Array.isArray(data.studies) ? data.studies : [];
  searchTotal.value = typeof data.total === 'number' ? data.total : searchRows.value.length;
}

async function refreshAfterToggle() {
  await loadAttached();
  if (attachDialog.value) {
    await runSearch(false);
  }
}

async function openAttachDialog() {
  attachDialog.value = true;
  try {
    await runSearch(true);
  } catch (e) {
    showSnack(String(e?.message || e), 'error');
  }
}

function resetSearch() {
  keywords.value = '';
  searchOffset.value = 0;
  runSearch(true).catch((e) => showSnack(String(e?.message || e), 'error'));
}

async function onRelationshipChange(item, newId) {
  busyRelSid.value = item.related_sid;
  try {
    await updateRelationship(item.related_sid, Number(newId));
    showSnack(lbl.value.saved || 'Saved', 'success');
    await refreshAfterToggle();
  } catch (e) {
    showSnack(String(e?.message || e), 'error');
    await loadAttached();
  } finally {
    busyRelSid.value = null;
  }
}

async function removeAttached(item) {
  const ok = window.confirm(lbl.value.confirm_remove || 'Delete?');
  if (!ok) return;
  busyDetachSid.value = item.related_sid;
  try {
    await detachStudy(item.related_sid);
    showSnack(lbl.value.saved || 'Saved', 'success');
    await refreshAfterToggle();
  } catch (e) {
    showSnack(String(e?.message || e), 'error');
  } finally {
    busyDetachSid.value = null;
  }
}

async function toggleSearchRow(item) {
  busySearchSid.value = item.id;
  try {
    if (item.is_attached) {
      await detachStudy(item.id);
      showSnack(lbl.value.saved || 'Saved', 'success');
    } else {
      const relId = relationshipPickBySid.value[item.id] ?? pickDefaultRelationshipId();
      await attachStudy(item.id, relId);
      showSnack(lbl.value.saved || 'Saved', 'success');
    }
    await refreshAfterToggle();
  } catch (e) {
    showSnack(String(e?.message || e), 'error');
  } finally {
    busySearchSid.value = null;
  }
}

function prevPage() {
  searchOffset.value = Math.max(0, searchOffset.value - searchLimit.value);
  runSearch(false).catch((e) => showSnack(String(e?.message || e), 'error'));
}

function nextPage() {
  if (searchOffset.value + searchLimit.value >= searchTotal.value) return;
  searchOffset.value += searchLimit.value;
  runSearch(false).catch((e) => showSnack(String(e?.message || e), 'error'));
}

onMounted(async () => {
  try {
    await loadAttached();
  } catch (e) {
    showSnack(String(e?.message || e), 'error');
  }
});
</script>

<style scoped>
.catalog-study-related-data-vue {
  padding: 1.5rem 1.25rem 2rem;
}

.csc-top-row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: center;
  gap: 0.75rem 1rem;
  width: 100%;
  margin-bottom: 1.5rem;
}

@media (max-width: 600px) {
  .csc-top-row {
    grid-template-columns: 1fr;
  }

  .csc-top-right {
    justify-self: end;
  }
}

.csc-section {
  background: transparent;
}

.csc-dialog-card {
  min-height: 100%;
}

.csc-dialog-table-wrap {
  min-height: 120px;
}

.csc-table {
  border-radius: 8px;
  overflow: hidden;
}

.csc-keywords {
  min-width: 0;
  max-width: 100%;
}

@media (min-width: 600px) {
  .csc-keywords {
    min-width: 280px;
    max-width: 520px;
  }
}

.gap-1 {
  gap: 4px;
}

.gap-2 {
  gap: 8px;
}
</style>
