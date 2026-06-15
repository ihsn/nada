<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="facets-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <v-row align="center" class="mb-4">
      <v-col cols="12" md="8">
        <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-0">Facets</h1>
      </v-col>
      <v-col cols="12" md="4" class="d-flex justify-end ga-2">
        <v-btn variant="text" color="primary" prepend-icon="mdi-sort" @click="router.push('/reorder')">Configure</v-btn>
        <v-btn variant="text" color="primary" prepend-icon="mdi-database-refresh" @click="router.push('/indexer')">Indexer</v-btn>
        <v-btn color="primary" prepend-icon="mdi-plus" @click="router.push('/new')">Create Facet</v-btn>
      </v-col>
    </v-row>

    <v-snackbar v-model="toast.open" :color="toast.color" location="bottom right" :timeout="4000">
      {{ toast.message }}
      <template #actions>
        <v-btn variant="text" size="small" @click="toast.open = false">Dismiss</v-btn>
      </template>
    </v-snackbar>

    <v-card v-if="loading && !facets.length" elevation="1">
      <v-card-text class="text-center py-12">
        <v-progress-circular indeterminate color="primary" size="64" class="mb-4" />
        <p class="text-medium-emphasis">Loading facets…</p>
      </v-card-text>
    </v-card>

    <v-card v-else-if="!facets.length" elevation="1">
      <v-card-text class="text-center py-12">
        <v-icon size="64" color="grey" class="mb-4">mdi-filter-variant-remove</v-icon>
        <h2 class="text-h6 mb-2">No facets found</h2>
        <p class="text-medium-emphasis mb-4">Create your first facet to enable catalog filtering.</p>
        <v-btn color="primary" prepend-icon="mdi-plus" @click="router.push('/new')">Create Facet</v-btn>
      </v-card-text>
    </v-card>

    <v-card v-else elevation="1">
      <v-data-table
        :headers="headers"
        :items="facets"
        :loading="loading"
        item-value="id"
        class="elevation-0"
        hide-default-footer
        :items-per-page="-1"
      >
        <template #item.index="{ index }">
          {{ index + 1 }}
        </template>
        <template #item.title="{ item }">
          <a class="text-primary text-decoration-none cursor-pointer" @click="router.push(`/terms/${item.id}`)">
            {{ item.title }}
          </a>
        </template>
        <template #item.enabled="{ item }">
          <v-chip :color="item.enabled == 1 ? 'success' : 'default'" size="small" variant="tonal">
            {{ item.enabled == 1 ? 'Enabled' : 'Disabled' }}
          </v-chip>
        </template>
        <template #item.actions="{ item }">
          <v-menu location="bottom end">
            <template #activator="{ props: menuProps }">
              <v-btn icon variant="text" v-bind="menuProps">
                <v-icon>mdi-dots-vertical</v-icon>
              </v-btn>
            </template>
            <v-list density="compact" min-width="150">
              <v-list-item prepend-icon="mdi-format-list-bulleted" title="Terms" @click="router.push(`/terms/${item.id}`)" />
              <v-list-item prepend-icon="mdi-pencil" title="Edit" @click="router.push(`/edit/${item.name}`)" />
              <v-divider />
              <v-list-item prepend-icon="mdi-delete" title="Delete" base-color="error" @click="openDeleteDialog(item)" />
            </v-list>
          </v-menu>
        </template>
      </v-data-table>
    </v-card>

    <!-- Delete dialog -->
    <v-dialog v-model="deleteDialog.open" max-width="400">
      <v-card>
        <v-card-title class="text-h6 pa-4">Delete Facet</v-card-title>
        <v-divider />
        <v-card-text class="pa-4">
          <v-alert v-if="deleteDialog.error" type="error" class="mb-4" density="compact" closable @click:close="deleteDialog.error = null">
            {{ deleteDialog.error }}
          </v-alert>
          <p>Delete <strong>{{ deleteDialog.facet?.title }}</strong>?</p>
          <p class="text-medium-emphasis text-body-2 mt-2">This action cannot be undone.</p>
        </v-card-text>
        <v-divider />
        <v-card-actions class="pa-3">
          <v-spacer />
          <v-btn variant="text" @click="deleteDialog.open = false">Cancel</v-btn>
          <v-btn color="error" variant="flat" :loading="deleteDialog.loading" @click="doDelete">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useFacetsApi } from '../composables/useFacetsApi';

const router = useRouter();
const { siteUrl } = useAppConfig();
const { loading, listFacets, deleteFacet } = useFacetsApi();

const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const breadcrumbItems = computed(() => [
  { title: 'Admin', href: `${siteBaseUrl.value}/admin` },
  { title: 'Facets', disabled: true },
]);

const headers = [
  { title: '#',      key: 'index',      sortable: false, width: 60 },
  { title: 'Title',  key: 'title',      sortable: false },
  { title: 'Type',   key: 'facet_type', sortable: false },
  { title: 'Status', key: 'enabled',    sortable: false },
  { title: 'Terms',  key: 'total',      sortable: false },
  { title: '',       key: 'actions',    sortable: false, align: 'end', width: 60 },
];

const facets = ref([]);
const toast = reactive({ open: false, message: '', color: 'success' });
const deleteDialog = reactive({ open: false, facet: null, error: null, loading: false });

function showToast(message, color = 'success') {
  toast.message = message;
  toast.color   = color;
  toast.open    = true;
}

onMounted(async () => {
  try {
    facets.value = await listFacets();
  } catch (e) {
    showToast(e?.response?.data?.message || e.message, 'error');
  }
});

function openDeleteDialog(facet) {
  deleteDialog.facet  = facet;
  deleteDialog.error  = null;
  deleteDialog.open   = true;
}

async function doDelete() {
  deleteDialog.loading = true;
  deleteDialog.error   = null;
  try {
    await deleteFacet(deleteDialog.facet.id);
    facets.value        = facets.value.filter(f => f.id !== deleteDialog.facet.id);
    deleteDialog.open   = false;
    showToast('Facet deleted.');
  } catch (e) {
    deleteDialog.error = e?.response?.data?.message || e.message;
  } finally {
    deleteDialog.loading = false;
  }
}
</script>

<style scoped>
.facets-breadcrumbs {
  font-size: 0.8125rem;
  margin-bottom: 0.5rem;
}
.facets-breadcrumbs :deep(.v-breadcrumbs-item),
.facets-breadcrumbs :deep(.v-breadcrumbs-divider) {
  font-size: 0.8125rem;
}
</style>
