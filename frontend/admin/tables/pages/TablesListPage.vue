<template>
  <div>
    <TablesBreadcrumbs :items="breadcrumbItems" />

    <TablesPageHeader
      title="Tables"
      subtitle="Manage MongoDB table definitions, data, and dictionaries"
      icon="mdi-table-large"
    >
      <template #meta>
        <v-chip v-if="!loading" variant="tonal" color="primary" size="small">
          {{ displayTotal }} {{ displayTotal === 1 ? 'table' : 'tables' }}
        </v-chip>
      </template>
      <template #actions>
        <v-btn color="primary" prepend-icon="mdi-plus" :to="{ path: '/create' }">Create table</v-btn>
      </template>
    </TablesPageHeader>

    <v-card class="admin-tables-results-card admin-tables-surface" rounded="lg" elevation="1">
      <div class="admin-tables-search-inner admin-tables-search-inner--bordered">
        <TablesFormField label="Search" hint="Filter by title, database ID, or table ID">
          <v-text-field
            v-model="searchQuery"
            variant="outlined"
            density="comfortable"
            hide-details
            clearable
            prepend-inner-icon="mdi-magnify"
            placeholder="Title, db_id, table_id…"
            @click:clear="onSearchClear"
          />
        </TablesFormField>
      </div>

      <div v-if="loading && displayTotal === 0 && !isSearchMode" class="text-center py-12">
        <v-progress-circular indeterminate color="primary" />
        <p class="text-medium-emphasis mt-4 mb-0">Loading tables…</p>
      </div>

      <v-card-text v-else-if="!loading && displayTotal === 0 && !searchQuery" class="text-center py-12">
        <v-icon size="64" color="grey-lighten-1" class="mb-4">mdi-table</v-icon>
        <p class="text-h6 text-medium-emphasis mb-2">No tables found</p>
        <p class="text-body-2 text-medium-emphasis mb-4">Create a table to get started</p>
        <v-btn color="primary" prepend-icon="mdi-plus" :to="{ path: '/create' }">Create table</v-btn>
      </v-card-text>

      <div v-else-if="isSearchMode && searchLoading && displayTotal === 0" class="text-center py-12">
        <v-progress-circular indeterminate color="primary" />
        <p class="text-medium-emphasis mt-4 mb-0">Searching…</p>
      </div>

      <v-card-text v-else-if="!loading && isSearchMode && displayTotal === 0" class="text-center py-12">
        <v-icon size="64" color="grey-lighten-1" class="mb-4">mdi-magnify-close</v-icon>
        <p class="text-h6 text-medium-emphasis mb-2">No matching tables</p>
        <p class="text-body-2 text-medium-emphasis mb-0">Try a different search term or clear the filter.</p>
      </v-card-text>

      <template v-else>
      <v-data-table-server
        v-if="!isSearchMode"
        :headers="headers"
        :items="tables"
        :items-length="totalTables"
        :loading="loading"
        :page="currentPage"
        :items-per-page="itemsPerPage"
        :items-per-page-options="[15, 30, 50]"
        item-value="table_key"
        class="admin-tables-table elevation-0"
        hover
        density="comfortable"
      >
        <template #item.thumb>
          <div class="tables-row-thumb">
            <v-avatar color="primary" variant="tonal" size="40" rounded="lg">
              <v-icon icon="mdi-table" size="22" />
            </v-avatar>
          </div>
        </template>
        <template #item.title="{ item }">
          <a class="text-primary cursor-pointer text-decoration-none font-weight-medium" @click.prevent="editTable(item)">
            {{ item.title || item.metadata?.title || 'N/A' }}
          </a>
          <div class="text-caption text-medium-emphasis">
            <code class="text-caption">{{ item.db_id || '—' }}</code>
            <span class="mx-1">/</span>
            <code class="text-caption">{{ item._id || item.table_id }}</code>
          </div>
        </template>
        <template #item.rows_count="{ item }">
          {{ formatNumber(item.rows_count || 0) }}
        </template>
        <template #item.storage_size="{ item }">
          {{ item.storage_size || 'N/A' }}
        </template>
        <template #item.nindexes="{ item }">
          <v-chip v-if="item.nindexes" size="small" color="info">{{ item.nindexes }}</v-chip>
          <span v-else class="text-medium-emphasis">0</span>
        </template>
        <template #item.created_at="{ item }">
          <span class="text-caption text-medium-emphasis">
            {{ item.created_at ? formatDate(item.created_at) : 'N/A' }}
          </span>
        </template>
        <template #item.updated_at="{ item }">
          <span class="text-caption text-medium-emphasis">
            {{ item.updated_at ? formatDate(item.updated_at) : 'N/A' }}
          </span>
        </template>
        <template #item.actions="{ item }">
          <v-menu location="bottom end">
            <template #activator="{ props: menuProps }">
              <v-btn icon variant="text" v-bind="menuProps" aria-label="Table actions">
                <v-icon>mdi-dots-vertical</v-icon>
              </v-btn>
            </template>
            <v-list density="compact" min-width="180">
              <v-list-item prepend-icon="mdi-pencil" title="Edit" @click="editTable(item)" />
              <v-divider />
              <v-list-item prepend-icon="mdi-information" title="Info" :href="api.apiUrl(item, 'info')" target="_blank" />
              <v-list-item prepend-icon="mdi-format-list-bulleted" title="Fields" :href="api.apiUrl(item, 'fields')" target="_blank" />
              <v-list-item prepend-icon="mdi-database" title="Data" :href="api.apiUrl(item, 'data')" target="_blank" />
              <v-list-item prepend-icon="mdi-download" title="Export definition" @click="exportDefinition(item)" />
              <v-divider />
              <v-list-item prepend-icon="mdi-delete" title="Delete" base-color="error" @click="openDeleteDialog(item)" />
            </v-list>
          </v-menu>
        </template>
        <template #bottom>
          <TablesTableFooter
            :start="startIndex"
            :end="endIndex"
            :total="totalTables"
            :page="currentPage"
            :total-pages="totalPages"
            :items-per-page="itemsPerPage"
            @update:page="onPageChange"
            @update:items-per-page="onItemsPerPageChange"
          />
        </template>
      </v-data-table-server>

      <v-data-table
        v-else
        :headers="headers"
        :items="pagedSearchResults"
        :loading="loading || searchLoading"
        item-value="table_key"
        class="admin-tables-table elevation-0"
        hover
        density="comfortable"
        hide-default-footer
      >
        <template #item.thumb>
          <div class="tables-row-thumb">
            <v-avatar color="primary" variant="tonal" size="40" rounded="lg">
              <v-icon icon="mdi-table" size="22" />
            </v-avatar>
          </div>
        </template>
        <template #item.title="{ item }">
          <a class="text-primary cursor-pointer text-decoration-none font-weight-medium" @click.prevent="editTable(item)">
            {{ item.title || item.metadata?.title || 'N/A' }}
          </a>
          <div class="text-caption text-medium-emphasis">
            <code class="text-caption">{{ item.db_id || '—' }}</code>
            <span class="mx-1">/</span>
            <code class="text-caption">{{ item._id || item.table_id }}</code>
          </div>
        </template>
        <template #item.rows_count="{ item }">
          {{ formatNumber(item.rows_count || 0) }}
        </template>
        <template #item.storage_size="{ item }">
          {{ item.storage_size || 'N/A' }}
        </template>
        <template #item.nindexes="{ item }">
          <v-chip v-if="item.nindexes" size="small" color="info">{{ item.nindexes }}</v-chip>
          <span v-else class="text-medium-emphasis">0</span>
        </template>
        <template #item.created_at="{ item }">
          <span class="text-caption text-medium-emphasis">
            {{ item.created_at ? formatDate(item.created_at) : 'N/A' }}
          </span>
        </template>
        <template #item.updated_at="{ item }">
          <span class="text-caption text-medium-emphasis">
            {{ item.updated_at ? formatDate(item.updated_at) : 'N/A' }}
          </span>
        </template>
        <template #item.actions="{ item }">
          <v-menu location="bottom end">
            <template #activator="{ props: menuProps }">
              <v-btn icon variant="text" v-bind="menuProps" aria-label="Table actions">
                <v-icon>mdi-dots-vertical</v-icon>
              </v-btn>
            </template>
            <v-list density="compact" min-width="180">
              <v-list-item prepend-icon="mdi-pencil" title="Edit" @click="editTable(item)" />
              <v-divider />
              <v-list-item prepend-icon="mdi-delete" title="Delete" base-color="error" @click="openDeleteDialog(item)" />
            </v-list>
          </v-menu>
        </template>
        <template #bottom>
          <TablesTableFooter
            :start="searchStartIndex"
            :end="searchEndIndex"
            :total="filteredTables.length"
            :page="searchPage"
            :total-pages="searchTotalPages"
            :items-per-page="searchItemsPerPage"
            @update:page="searchPage = $event"
            @update:items-per-page="onSearchItemsPerPageChange"
          />
        </template>
      </v-data-table>
      </template>
    </v-card>

    <v-dialog v-model="deleteDialog.open" max-width="520" persistent>
      <v-card>
        <v-card-title class="bg-error text-white">Delete table</v-card-title>
        <v-card-text class="pt-4">
          <p class="mb-3">
            Delete table <strong>{{ deleteDialog.label }}</strong>? This permanently removes table data, the data
            dictionary, and the table definition.
          </p>
          <v-alert type="warning" variant="tonal" density="compact">This action cannot be undone.</v-alert>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" :disabled="deleteDialog.loading" @click="deleteDialog.open = false">Cancel</v-btn>
          <v-btn color="error" :loading="deleteDialog.loading" prepend-icon="mdi-delete" @click="confirmDeleteTable">
            Delete
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, computed, inject, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useTablesApi } from '../composables/useTablesApi';
import { formatNumber, formatDate } from '../utils/fieldUtils';
import TablesBreadcrumbs from '../components/TablesBreadcrumbs.vue';
import TablesPageHeader from '../components/TablesPageHeader.vue';
import TablesFormField from '../components/TablesFormField.vue';
import TablesTableFooter from '../components/TablesTableFooter.vue';

defineOptions({ name: 'TablesListPage' });

const router = useRouter();
const { siteUrl } = useAppConfig();
const setMessage = inject('setMessage', () => {});
const api = useTablesApi();

const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const breadcrumbItems = computed(() => [
  { title: 'Admin', href: `${siteBaseUrl.value}/admin` },
  { title: 'Tables', disabled: true },
]);

const tables = ref([]);
const allTablesCache = ref(null);
const loading = ref(true);
const searchLoading = ref(false);
const searchQuery = ref('');
const currentPage = ref(1);
const itemsPerPage = ref(15);
const totalTables = ref(0);
const searchPage = ref(1);
const searchItemsPerPage = ref(15);
let loadSeq = 0;

const deleteDialog = ref({
  open: false,
  loading: false,
  table: null,
  label: '',
});

const isSearchMode = computed(() => searchQuery.value.trim().length > 0);

const headers = [
  { title: '', key: 'thumb', sortable: false, width: 56 },
  { title: 'Title', key: 'title', sortable: false },
  { title: 'Rows', key: 'rows_count', sortable: false, align: 'end' },
  { title: 'Size', key: 'storage_size', sortable: false },
  { title: 'Indexes', key: 'nindexes', sortable: false, align: 'center' },
  { title: 'Created', key: 'created_at', sortable: false },
  { title: 'Updated', key: 'updated_at', sortable: false },
  { title: 'Actions', key: 'actions', sortable: false, align: 'center', width: 80 },
];

function tableMatchesSearch(table, q) {
  const title = (table.title || table.metadata?.title || '').toLowerCase();
  const db = (table.db_id || '').toLowerCase();
  const tid = (table.table_id || table._id || '').toLowerCase();
  return title.includes(q) || db.includes(q) || tid.includes(q);
}

const filteredTables = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  if (!q) return [];
  const source = allTablesCache.value || [];
  return source.filter((t) => tableMatchesSearch(t, q));
});

const pagedSearchResults = computed(() => {
  const start = (searchPage.value - 1) * searchItemsPerPage.value;
  return filteredTables.value.slice(start, start + searchItemsPerPage.value);
});

const searchTotalPages = computed(() =>
  Math.max(1, Math.ceil(filteredTables.value.length / searchItemsPerPage.value) || 1)
);
const searchStartIndex = computed(() =>
  filteredTables.value.length === 0 ? 0 : (searchPage.value - 1) * searchItemsPerPage.value + 1
);
const searchEndIndex = computed(() =>
  Math.min(searchPage.value * searchItemsPerPage.value, filteredTables.value.length)
);

const displayTotal = computed(() => (isSearchMode.value ? filteredTables.value.length : totalTables.value));

const totalPages = computed(() => Math.max(1, Math.ceil(totalTables.value / itemsPerPage.value) || 1));
const startIndex = computed(() => (totalTables.value === 0 ? 0 : (currentPage.value - 1) * itemsPerPage.value + 1));
const endIndex = computed(() => Math.min(currentPage.value * itemsPerPage.value, totalTables.value));

async function loadAllTablesForSearch() {
  searchLoading.value = true;
  try {
    const acc = [];
    let offset = 0;
    const limit = 100;
    while (true) {
      const result = await api.fetchTables({ limit, offset });
      acc.push(...result.tables);
      if (acc.length >= result.total || result.tables.length === 0) break;
      offset += limit;
      if (offset > 5000) break;
    }
    allTablesCache.value = acc;
  } catch (e) {
    setMessage('Error loading tables for search: ' + (e.response?.data?.message || e.message), 'error');
    allTablesCache.value = [];
  } finally {
    searchLoading.value = false;
  }
}

watch(searchQuery, async (q) => {
  searchPage.value = 1;
  if (q.trim() && !allTablesCache.value) {
    await loadAllTablesForSearch();
  }
});

function onSearchClear() {
  searchQuery.value = '';
  searchPage.value = 1;
}

function onSearchItemsPerPageChange(perPage) {
  searchItemsPerPage.value = perPage;
  searchPage.value = 1;
}

async function loadTables() {
  const seq = ++loadSeq;
  loading.value = true;
  try {
    const offset = (currentPage.value - 1) * itemsPerPage.value;
    const result = await api.fetchTables({ limit: itemsPerPage.value, offset });
    if (seq !== loadSeq) return;
    tables.value = result.tables;
    totalTables.value = result.total;
    if (currentPage.value > totalPages.value) {
      currentPage.value = totalPages.value;
      await loadTables();
    }
  } catch (e) {
    if (seq !== loadSeq) return;
    setMessage('Error loading tables: ' + (e.response?.data?.message || e.message), 'error');
  } finally {
    if (seq === loadSeq) loading.value = false;
  }
}

function onPageChange(page) {
  if (page === currentPage.value) return;
  currentPage.value = page;
  loadTables();
}

function onItemsPerPageChange(perPage) {
  if (perPage === itemsPerPage.value) return;
  itemsPerPage.value = perPage;
  currentPage.value = 1;
  loadTables();
}

onMounted(loadTables);

function editTable(table) {
  const tableId = table.table_id || table._id;
  const dbId = table.db_id;
  router.push({ name: 'edit', params: { db_id: dbId, table_id: tableId } });
}

function exportDefinition(table) {
  const dbId = table.db_id || table.metadata?.db_id;
  const tableId =
    table.table_id ||
    table.metadata?.table_id ||
    (table._id ? String(table._id).replace(`table_${dbId}_`, '') : '');
  window.open(api.exportDefinitionUrl(dbId, tableId), '_blank');
}

function openDeleteDialog(table) {
  deleteDialog.value = {
    open: true,
    loading: false,
    table,
    label: table.title || table.metadata?.title || table._id || table.table_id,
  };
}

async function confirmDeleteTable() {
  const table = deleteDialog.value.table;
  if (!table) return;
  deleteDialog.value.loading = true;
  try {
    await api.deleteTable(table.db_id, table.table_id, true);
    setMessage('Table, fields, and data deleted successfully', 'success');
    deleteDialog.value.open = false;
    allTablesCache.value = null;
    if (searchQuery.value.trim()) {
      await loadAllTablesForSearch();
    }
    await loadTables();
  } catch (e) {
    setMessage('Error deleting table: ' + (e.response?.data?.message || e.message), 'error');
  } finally {
    deleteDialog.value.loading = false;
  }
}
</script>
