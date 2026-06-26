<template>
  <v-app>
    <v-main class="catalog-study-citations-vue">
      <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="4500">
        {{ snackbar.text }}
      </v-snackbar>

      <div class="csc-top-row">
        <div class="csc-top-left d-flex flex-wrap align-center gap-2">
          <div class="text-subtitle-1 font-weight-bold">{{ lbl.title || 'Citations' }}</div>
        </div>
        <div class="csc-top-right d-flex flex-wrap align-center justify-end gap-1">
          <v-btn color="primary" size="small" variant="flat" class="text-none" @click="openAttachDialog">
            {{ lbl.attach_citations_cta || 'Attach citations' }}
          </v-btn>
        </div>
      </div>

      <v-card variant="flat" elevation="0" rounded="lg" class="csc-section">
        <div class="text-subtitle-2 font-weight-semibold mb-2">
          {{ lbl.attach_citation || 'Attached citations' }}
          <span class="text-caption text-medium-emphasis font-weight-regular">({{ attachedRows.length }})</span>
        </div>
        <v-data-table
          :headers="attachedHeaders"
          :items="attachedRows"
          item-value="id"
          density="compact"
          class="csc-table elevation-0 border rounded"
          :items-per-page="-1"
          hide-default-footer
        >
          <template #item.title="{ item }">
            <div class="font-weight-medium" v-html="item.formatted_citation || item.title" />
          </template>
          <template #item.actions="{ item }">
            <div class="d-flex align-center gap-1">
              <v-btn
                v-if="editCitationUrl(item)"
                size="x-small"
                variant="text"
                icon="mdi-pencil"
                :href="editCitationUrl(item)"
                :title="lbl.edit || 'Edit'"
              />
              <v-btn
                size="x-small"
                variant="text"
                color="error"
                icon="mdi-link-off"
                :loading="busyId === item.id"
                :title="lbl.remove || 'Remove'"
                @click.prevent="detachOne(item.id)"
              />
            </div>
          </template>
        </v-data-table>
      </v-card>

      <v-dialog v-model="attachDialog" fullscreen transition="dialog-bottom-transition">
        <v-card variant="flat" elevation="0" class="csc-dialog-card d-flex flex-column h-100 rounded-0">
          <v-card-title class="d-flex flex-wrap align-center gap-2 py-4 px-4 flex-shrink-0">
            <span class="text-h6 font-weight-semibold">{{ lbl.attach_citations_cta || 'Attach citations' }}</span>
            <v-spacer />
            <v-btn icon variant="text" density="comfortable" :title="lbl.close || 'Close'" @click="attachDialog = false">
              <v-icon>mdi-close</v-icon>
            </v-btn>
          </v-card-title>
          <v-divider />
          <v-card-text class="pa-4 flex-grow-1 d-flex flex-column overflow-hidden" style="min-height: 0">
            <div class="csc-dialog-search-row d-flex flex-wrap align-center gap-2 flex-shrink-0">
              <v-text-field
                v-model="keywords"
                hide-details
                density="compact"
                variant="outlined"
                :placeholder="lbl.search || 'Search'"
                class="csc-keywords csc-dialog-keywords flex-grow-1"
                @keyup.enter="runSearch(true)"
              />
              <v-btn color="primary" size="small" class="text-none" @click="runSearch(true)">
                {{ lbl.search || 'Search' }}
              </v-btn>
              <v-btn size="small" variant="text" class="text-none" @click="resetSearch">{{ lbl.reset || 'Reset' }}</v-btn>
            </div>

            <div class="text-caption text-medium-emphasis mb-2 flex-shrink-0">
              {{ searchTotal }} result(s)
            </div>

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
                    :loading="busyId === item.id"
                    :title="item.is_attached ? (lbl.remove || 'Remove') : (lbl.add || 'Attach')"
                    @click="toggleAttach(item)"
                  />
                </template>
                <template #item.title="{ item }">
                  <div v-html="item.formatted_citation || item.title" />
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
import { computed, onMounted, ref } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useStudyCitationsApi } from './composables/useStudyCitationsApi';

const { config } = useAppConfig();
const { fetchAttached, searchCitations, attachCitation, detachCitation } = useStudyCitationsApi();

const lbl = computed(() => config.value?.labels || {});
const legacy = computed(() => config.value?.legacyUrls || {});
const attachedRows = ref([]);
const searchRows = ref([]);
const searchTotal = ref(0);
const searchOffset = ref(0);
const searchLimit = ref(30);
const keywords = ref('');
const busyId = ref(null);
const snackbar = ref({ show: false, text: '', color: 'surface' });
const attachDialog = ref(false);

const attachedHeaders = computed(() => [
  { title: lbl.value.col_title || 'Title', key: 'title', sortable: false },
  { title: lbl.value.col_actions || 'Actions', key: 'actions', sortable: false, align: 'end', width: '92px' },
]);

const searchHeaders = computed(() => [
  { title: lbl.value.col_link || lbl.value.col_attached || 'Link', key: 'is_attached', sortable: false, width: '72px' },
  { title: lbl.value.col_title || 'Title', key: 'title', sortable: false },
  { title: lbl.value.col_year || 'Year', key: 'pub_year', sortable: false, width: '80px' },
  { title: lbl.value.col_doi || 'DOI', key: 'doi', sortable: false, width: '120px' },
]);

function showSnack(text, color = 'surface') {
  snackbar.value = { show: true, text, color };
}

function editCitationUrl(item) {
  const base = String(legacy.value?.editCitationBase || '').replace(/\/+$/, '');
  if (!base || !item?.id) return '';
  return `${base}/${encodeURIComponent(String(item.id))}`;
}

async function loadAttached() {
  const data = await fetchAttached('title', 'asc');
  attachedRows.value = Array.isArray(data.citations) ? data.citations : [];
}

async function runSearch(resetOffset = false) {
  if (resetOffset) searchOffset.value = 0;
  const data = await searchCitations({
    keywords: keywords.value,
    offset: searchOffset.value,
    limit: searchLimit.value,
    sortBy: 'changed',
    sortOrder: 'desc',
  });
  searchRows.value = Array.isArray(data.citations) ? data.citations : [];
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

async function toggleAttach(item) {
  if (!item?.id) return;
  busyId.value = item.id;
  try {
    if (item.is_attached) {
      await detachCitation(item.id);
    } else {
      await attachCitation(item.id);
    }
    await refreshAfterToggle();
  } catch (e) {
    showSnack(String(e?.message || e), 'error');
  } finally {
    busyId.value = null;
  }
}

async function detachOne(citationId) {
  if (!citationId) return;
  if (!window.confirm(lbl.value.confirm_remove || 'Remove citation?')) return;
  busyId.value = citationId;
  try {
    await detachCitation(citationId);
    await refreshAfterToggle();
  } catch (e) {
    showSnack(String(e?.message || e), 'error');
  } finally {
    busyId.value = null;
  }
}

function resetSearch() {
  keywords.value = '';
  runSearch(true).catch((e) => showSnack(String(e?.message || e), 'error'));
}

function nextPage() {
  if (searchOffset.value + searchLimit.value >= searchTotal.value) return;
  searchOffset.value += searchLimit.value;
  runSearch(false).catch((e) => showSnack(String(e?.message || e), 'error'));
}

function prevPage() {
  if (searchOffset.value <= 0) return;
  searchOffset.value = Math.max(0, searchOffset.value - searchLimit.value);
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
.catalog-study-citations-vue {
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

.csc-dialog-search-row .csc-dialog-keywords {
  max-width: none;
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

.csc-dialog-search-row {
  margin-bottom: 1.25rem;
}

.gap-1 {
  gap: 4px;
}

.gap-2 {
  gap: 8px;
}
</style>
