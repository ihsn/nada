<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="tables-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <v-row align="center" class="mb-4">
      <v-col cols="12" md="8">
        <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-0">Tables</h1>
      </v-col>
      <v-col cols="12" md="4" class="d-flex justify-end">
        <v-btn color="primary" prepend-icon="mdi-plus" :to="{ path: '/create' }">
          Create table
        </v-btn>
      </v-col>
    </v-row>

    <v-card v-if="loading && tables.length === 0" elevation="1">
      <v-card-text class="text-center py-12">
        <v-progress-circular indeterminate color="primary" size="64" class="mb-4" />
        <p class="text-medium-emphasis">Loading tables…</p>
      </v-card-text>
    </v-card>

    <v-card v-else-if="tables.length > 0" elevation="1">
      <v-data-table
        v-model:page="currentPage"
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="tables"
        :items-length="totalTables"
        :loading="loading"
        item-value="table_key"
        class="elevation-0"
      >
        <template #item.table_id="{ item }">
          {{ item._id || item.table_id }}
        </template>
        <template #item.db_id="{ item }">
          {{ item.db_id || 'N/A' }}
        </template>
        <template #item.title="{ item }">
          <a
            class="text-primary cursor-pointer text-decoration-none"
            @click.prevent="editTable(item)"
          >
            {{ item.title || item.metadata?.title || 'N/A' }}
          </a>
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
              <v-btn icon variant="text" v-bind="menuProps">
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
              <v-list-item prepend-icon="mdi-delete" title="Delete" base-color="error" @click="deleteTable(item)" />
            </v-list>
          </v-menu>
        </template>
        <template #bottom>
          <v-divider />
          <div class="pa-3 d-flex align-center justify-space-between flex-wrap gap-2">
            <div class="text-caption text-medium-emphasis">{{ totalTables }} total</div>
            <div class="d-flex align-center gap-3">
              <v-select
                v-model="itemsPerPage"
                hide-details
                density="compact"
                variant="outlined"
                style="max-width: 88px"
                :items="[15, 30, 50]"
              />
              <v-pagination
                v-model="currentPage"
                :length="Math.max(1, Math.ceil(totalTables / itemsPerPage))"
                :total-visible="7"
                size="small"
              />
            </div>
          </div>
        </template>
      </v-data-table>
    </v-card>

    <v-card v-else elevation="1">
      <v-card-text class="text-center py-12">
        <v-icon size="64" color="grey" class="mb-4">mdi-database-off</v-icon>
        <h2 class="text-h6 mb-2">No tables found</h2>
        <p class="text-medium-emphasis mb-4">No tables are currently available in the database.</p>
        <v-btn color="primary" prepend-icon="mdi-plus" :to="{ path: '/create' }">Create your first table</v-btn>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup>
import { ref, computed, inject, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useTablesApi } from '../composables/useTablesApi';
import { formatNumber, formatDate } from '../utils/fieldUtils';

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
const loading = ref(false);
const currentPage = ref(1);
const itemsPerPage = ref(15);
const totalTables = ref(0);

const headers = [
  { title: 'Title', key: 'title', sortable: false },
  { title: 'Table ID', key: 'table_id', sortable: false },
  { title: 'Database ID', key: 'db_id', sortable: false },
  { title: 'Rows', key: 'rows_count', sortable: false, align: 'end' },
  { title: 'Size', key: 'storage_size', sortable: false },
  { title: 'Indexes', key: 'nindexes', sortable: false, align: 'center' },
  { title: 'Created', key: 'created_at', sortable: false },
  { title: 'Updated', key: 'updated_at', sortable: false },
  { title: 'Actions', key: 'actions', sortable: false, align: 'center', width: 80 },
];

async function loadTables() {
  loading.value = true;
  try {
    const offset = (currentPage.value - 1) * itemsPerPage.value;
    const result = await api.fetchTables({ limit: itemsPerPage.value, offset });
    tables.value = result.tables;
    totalTables.value = result.total;
  } catch (e) {
    setMessage('Error loading tables: ' + (e.response?.data?.message || e.message), 'error');
  } finally {
    loading.value = false;
  }
}

watch([currentPage, itemsPerPage], () => loadTables(), { immediate: true });

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

async function deleteTable(table) {
  const tableName = table._id || table.table_id;
  if (
    !confirm(
      `Delete table "${tableName}"?\n\nThis will permanently delete:\n- Table data\n- Table fields (data dictionary)\n- Table definition\n\nThis action cannot be undone.`
    )
  ) {
    return;
  }
  try {
    await api.deleteTable(table.db_id, table.table_id, true);
    setMessage('Table, fields, and data deleted successfully', 'success');
    await loadTables();
  } catch (e) {
    setMessage('Error deleting table: ' + (e.response?.data?.message || e.message), 'error');
  }
}
</script>

<style scoped>
.tables-breadcrumbs {
  font-size: 0.8125rem;
  margin-bottom: 0.5rem;
}

.tables-breadcrumbs :deep(.v-breadcrumbs-item),
.tables-breadcrumbs :deep(.v-breadcrumbs-divider) {
  font-size: 0.8125rem;
}
</style>
