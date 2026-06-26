<template>
  <div class="d-flex flex-column gap-4">
    <div class="d-flex flex-wrap align-center gap-2">
      <v-btn variant="text" prepend-icon="mdi-arrow-left" @click="goBack">Back to list</v-btn>
      <div class="text-subtitle-1 font-weight-medium">Projects using DSD #{{ structureId }}</div>
      <v-spacer />
      <v-chip variant="tonal" color="indigo" size="small">{{ total }} {{ total === 1 ? 'project' : 'projects' }}</v-chip>
    </div>

    <v-card rounded="xl" border>
      <v-card-text class="pa-0">
        <v-data-table-server
          :headers="headers"
          :items="projects"
          :items-length="total"
          :page="page"
          :items-per-page="itemsPerPage"
          :items-per-page-options="[10, 25, 50, 100]"
          :loading="loading"
          item-value="id"
          class="elevation-0"
          @update:page="page = $event"
          @update:items-per-page="itemsPerPage = $event"
        >
          <template #no-data>
            <div class="py-10 text-center text-medium-emphasis">No projects are linked to this data structure.</div>
          </template>
          <template v-slot:[`item.id`]="{ item }">
            <code>{{ item.id }}</code>
          </template>
          <template v-slot:[`item.idno`]="{ item }">
            <code>{{ item.idno || '—' }}</code>
          </template>
          <template v-slot:[`item.title`]="{ item }">
            <a :href="studyUrl(item.id)" class="text-decoration-none text-primary" target="_blank" rel="noopener">
              {{ item.title || 'Untitled' }}
            </a>
          </template>
          <template v-slot:[`item.published`]="{ item }">
            <v-chip :color="Number(item.published) === 1 ? 'success' : 'grey'" size="x-small" variant="tonal">
              {{ Number(item.published) === 1 ? 'Published' : 'Draft' }}
            </v-chip>
          </template>
          <template v-slot:[`item.changed`]="{ item }">
            <span class="text-medium-emphasis">{{ formatDate(item.changed) }}</span>
          </template>
        </v-data-table-server>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup>
import { ref, computed, watch, inject } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useDataStructuresApi } from '../composables/useDataStructuresApi';
import { useAppConfig } from '@/shared/composables/useAppConfig';

defineOptions({ name: 'DataStructureProjectsPage' });

const router = useRouter();
const route = useRoute();
const setMessage = inject('setMessage', () => {});
const { fetchDataStructureProjects } = useDataStructuresApi();
const { siteUrl } = useAppConfig();

const projects = ref([]);
const total = ref(0);
const page = ref(1);
const itemsPerPage = ref(25);
const loading = ref(false);

const structureId = computed(() => Number(route.params.id));

const headers = [
  { title: 'ID', key: 'id', width: 80 },
  { title: 'IDNO', key: 'idno', width: 220 },
  { title: 'Title', key: 'title', minWidth: 280 },
  { title: 'Type', key: 'type', width: 130 },
  { title: 'Collection', key: 'repositoryid', width: 140 },
  { title: 'Status', key: 'published', width: 110 },
  { title: 'Updated', key: 'changed', width: 130 },
];

function goBack() {
  router.push({ name: 'data-structures' });
}

function studyUrl(id) {
  const root = String(siteUrl.value || '').replace(/\/$/, '');
  if (!root) return `/index.php/catalog/${id}`;
  return `${root}/catalog/${id}`;
}

function formatDate(val) {
  const n = Number(val);
  if (!Number.isFinite(n) || n <= 0) return '—';
  const d = new Date((n > 1e12 ? n : n * 1000));
  return Number.isNaN(d.getTime()) ? '—' : d.toLocaleDateString(undefined, { dateStyle: 'short' });
}

async function loadProjects() {
  if (!Number.isFinite(structureId.value) || structureId.value < 1) return;
  loading.value = true;
  try {
    const res = await fetchDataStructureProjects(structureId.value, {
      page: page.value,
      per_page: itemsPerPage.value,
    });
    projects.value = res.projects ?? [];
    total.value = res.total ?? 0;
  } catch (e) {
    setMessage(e?.response?.data?.message || e?.message || 'Failed to load projects', 'error');
  } finally {
    loading.value = false;
  }
}

watch([structureId, page, itemsPerPage], loadProjects, { immediate: true });
</script>
