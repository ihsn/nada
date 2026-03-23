<template>
  <div>
    <div class="d-flex align-center mb-4">
      <v-btn icon="mdi-arrow-left" variant="text" size="small" @click="router.push('/')" />
      <h1 class="text-h5 font-weight-medium ml-2">
        Collection History
        <span v-if="collection.title" class="text-medium-emphasis"> — {{ collection.title }}</span>
      </h1>
    </div>

    <v-alert v-if="error" type="error" class="mb-4" density="compact">{{ error }}</v-alert>

    <v-data-table-server
      :headers="headers"
      :items="rows"
      :items-length="total"
      :loading="loading"
      :items-per-page="pagination.itemsPerPage"
      :page="pagination.page"
      density="compact"
      class="elevation-1"
      @update:options="onOptions"
    >
      <template #item.title="{ item }">
        <a :href="siteUrl + 'index.php/admin/catalog/edit/' + item.id" class="study-link">
          {{ item.title }}
        </a>
      </template>

      <template #item.year="{ item }">
        {{ formatYear(item) }}
      </template>

      <template #item.created="{ item }">
        {{ formatDate(item.created) }}
      </template>

      <template #item.changed="{ item }">
        {{ formatDate(item.changed) }}
      </template>

      <template #top>
        <div v-if="!loading && total > 0" class="pa-3 text-body-2 text-medium-emphasis">
          {{ total }} {{ total === 1 ? 'study' : 'studies' }} in this collection
        </div>
      </template>

      <template #no-data>
        <div class="text-center pa-4 text-medium-emphasis">No studies in this collection.</div>
      </template>
    </v-data-table-server>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useCollectionsApi } from '../composables/useCollectionsApi';
import { useAppConfig } from '@/shared/composables/useAppConfig';

const router = useRouter();
const route = useRoute();
const { loading, getHistory } = useCollectionsApi();
const { siteUrl } = useAppConfig();

const rows = ref([]);
const total = ref(0);
const collection = reactive({ repositoryid: '', title: '' });
const error = ref(null);

const pagination = reactive({ page: 1, itemsPerPage: 25 });

const headers = [
  { title: 'Title', key: 'title', sortable: false },
  { title: 'Country', key: 'nation', sortable: false },
  { title: 'Year', key: 'year', sortable: false },
  { title: 'Data access', key: 'da_model', sortable: false },
  { title: 'Created', key: 'created', sortable: false },
  { title: 'Changed', key: 'changed', sortable: false },
];

function formatYear(item) {
  const years = [item.year_start, item.year_end].filter(Boolean);
  return [...new Set(years)].join(' - ');
}

function formatDate(ts) {
  if (!ts) return '—';
  const d = new Date(ts * 1000);
  return d.toLocaleDateString('en-US', { year: 'numeric', month: '2-digit', day: '2-digit' });
}

async function fetchPage() {
  error.value = null;
  try {
    const data = await getHistory(route.params.repositoryid, {
      page: pagination.page,
      ps: pagination.itemsPerPage,
    });
    rows.value = data.rows || [];
    total.value = data.total || 0;
    Object.assign(collection, data.collection || {});
  } catch (e) {
    error.value = e?.response?.data?.message || e.message || 'Failed to load history';
  }
}

function onOptions({ page, itemsPerPage }) {
  pagination.page = page;
  pagination.itemsPerPage = itemsPerPage;
  fetchPage();
}

onMounted(fetchPage);
</script>

<style scoped>
.study-link {
  color: inherit;
  text-decoration: none;
}
.study-link:hover {
  text-decoration: underline;
}
</style>
