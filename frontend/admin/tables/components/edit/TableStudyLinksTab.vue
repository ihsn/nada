<template>
  <v-card flat rounded="0">
    <v-card-title class="d-flex align-center">
      <span>Study links</span>
      <v-spacer />
      <v-btn color="primary" prepend-icon="mdi-link-plus" @click="showAttachDialog = true">Attach study</v-btn>
    </v-card-title>
    <v-card-text>
      <v-alert v-if="errorMsg" type="error" closable class="mb-4" @click:close="errorMsg = ''">{{ errorMsg }}</v-alert>
      <v-alert v-if="successMsg" type="success" closable class="mb-4" @click:close="successMsg = ''">
        {{ successMsg }}
      </v-alert>
      <div v-if="loading" class="text-center py-8">
        <v-progress-circular indeterminate color="primary" />
        <div class="mt-2 text-medium-emphasis">Loading study links…</div>
      </div>
      <v-data-table
        v-else
        :headers="headers"
        :items="studies"
        :items-per-page="10"
        item-value="sid"
        density="comfortable"
      >
        <template #item.title="{ item }">
          <div>
            <div class="font-weight-medium">{{ item.title || 'N/A' }}</div>
            <div class="text-caption text-medium-emphasis">
              IDNO: {{ item.idno }} | {{ item.nation || 'N/A'
              }}{{ item.year_start ? ', ' + item.year_start : '' }}
            </div>
          </div>
        </template>
        <template #item.actions="{ item }">
          <v-btn
            icon
            size="small"
            color="primary"
            variant="text"
            :href="studyDataUrl(item.sid)"
            target="_blank"
            title="View study"
          >
            <v-icon size="small">mdi-open-in-new</v-icon>
          </v-btn>
          <v-btn icon size="small" color="error" variant="text" title="Detach" @click="confirmDetach(item)">
            <v-icon size="small">mdi-link-variant-off</v-icon>
          </v-btn>
        </template>
        <template #no-data>
          <div class="text-center py-8 text-medium-emphasis">
            <v-icon size="48" color="grey" class="mb-2">mdi-link-off</v-icon>
            <p>No studies attached to this table</p>
          </div>
        </template>
      </v-data-table>
    </v-card-text>

    <v-dialog v-model="showAttachDialog" max-width="640">
      <v-card>
        <v-card-title>Attach study</v-card-title>
        <v-card-text>
          <v-text-field
            v-model="searchQuery"
            label="Search studies"
            prepend-inner-icon="mdi-magnify"
            variant="outlined"
            density="compact"
            clearable
            hint="Enter at least 2 characters"
            @update:model-value="onSearch"
          />
          <v-alert v-if="searchError" type="error" density="compact" class="mt-2">{{ searchError }}</v-alert>
          <v-list v-if="searchResults.length" density="compact" class="mt-2" max-height="320" style="overflow-y: auto">
            <v-list-item
              v-for="study in searchResults"
              :key="study.idno"
              :title="study.title"
              :subtitle="`${study.idno} · ${study.nation || 'N/A'}`"
              @click="attach(study)"
            >
              <template #append>
                <v-btn size="small" color="primary" :loading="attaching" @click.stop="attach(study)">Attach</v-btn>
              </template>
            </v-list-item>
          </v-list>
          <div
            v-else-if="searchQuery && searchQuery.length >= 2 && !searching"
            class="text-center py-6 text-medium-emphasis"
          >
            No studies found
          </div>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="closeAttach">Close</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="showDetachDialog" max-width="480">
      <v-card>
        <v-card-title>Detach study</v-card-title>
        <v-card-text>
          <p>Detach <strong>{{ studyToDetach?.title }}</strong> from this table?</p>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="showDetachDialog = false">Cancel</v-btn>
          <v-btn color="error" :loading="detaching" @click="detach">Detach</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-card>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useTablesApi } from '../../composables/useTablesApi';

const props = defineProps({
  dbId: { type: String, required: true },
  tableId: { type: String, required: true },
});

const { studyEditBaseUrl } = useAppConfig();
const api = useTablesApi();

const studies = ref([]);
const loading = ref(false);
const errorMsg = ref('');
const successMsg = ref('');
const showAttachDialog = ref(false);
const showDetachDialog = ref(false);
const searchQuery = ref('');
const searchResults = ref([]);
const searching = ref(false);
const searchError = ref('');
const attaching = ref(false);
const detaching = ref(false);
const studyToDetach = ref(null);

let searchTimer = null;

const headers = [
  { title: 'Title', key: 'title', sortable: false },
  { title: 'IDNO', key: 'idno' },
  { title: 'Nation', key: 'nation' },
  { title: 'Year', key: 'year_start' },
  { title: 'Actions', key: 'actions', align: 'center', width: 120, sortable: false },
];

function studyDataUrl(sid) {
  const base = (studyEditBaseUrl.value || '').replace(/\/?$/, '/');
  return `${base}${sid}/data-api`;
}

async function loadStudies() {
  loading.value = true;
  errorMsg.value = '';
  try {
    studies.value = await api.fetchStudyLinks(props.dbId, props.tableId);
  } catch (e) {
    errorMsg.value = e.message;
    studies.value = [];
  } finally {
    loading.value = false;
  }
}

function onSearch() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(runSearch, 400);
}

async function runSearch() {
  const q = (searchQuery.value || '').trim();
  if (q.length < 2) {
    searchResults.value = [];
    return;
  }
  searching.value = true;
  searchError.value = '';
  try {
    const rows = await api.searchCatalogStudies(q);
    const attachedSids = studies.value.map((s) => s.sid);
    searchResults.value = rows
      .filter((study) => !attachedSids.includes(study.id))
      .map((study) => ({
        id: study.id,
        sid: study.id,
        idno: study.idno,
        title: study.title,
        nation: study.nation,
        year_start: study.year_start,
      }));
  } catch (e) {
    searchError.value = e.message;
    searchResults.value = [];
  } finally {
    searching.value = false;
  }
}

async function attach(study) {
  attaching.value = true;
  errorMsg.value = '';
  try {
    await api.attachStudy(props.dbId, props.tableId, study.idno);
    successMsg.value = `Study "${study.title}" attached successfully`;
    closeAttach();
    await loadStudies();
  } catch (e) {
    errorMsg.value = e.message;
  } finally {
    attaching.value = false;
  }
}

function closeAttach() {
  showAttachDialog.value = false;
  searchQuery.value = '';
  searchResults.value = [];
  searchError.value = '';
}

function confirmDetach(study) {
  studyToDetach.value = study;
  showDetachDialog.value = true;
}

async function detach() {
  if (!studyToDetach.value) return;
  detaching.value = true;
  try {
    await api.detachStudy(props.dbId, props.tableId, studyToDetach.value.sid);
    successMsg.value = `Study "${studyToDetach.value.title}" detached successfully`;
    showDetachDialog.value = false;
    studyToDetach.value = null;
    await loadStudies();
  } catch (e) {
    errorMsg.value = e.message;
  } finally {
    detaching.value = false;
  }
}

onMounted(() => loadStudies());

defineExpose({ loadStudies });
</script>
