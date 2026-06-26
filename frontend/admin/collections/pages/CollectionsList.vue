<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="coll-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <v-row align="center" class="mb-4">
      <v-col cols="12" md="8">
        <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-0">Collections</h1>
      </v-col>
      <v-col cols="12" md="4" class="d-flex justify-end ga-2">
        <v-btn
          variant="text"
          color="primary"
          prepend-icon="mdi-shape-outline"
          :href="`${siteBaseUrl}/admin/repository_sections`"
        >
          Manage Sections
        </v-btn>
        <v-btn
          color="primary"
          prepend-icon="mdi-plus"
          @click="router.push('/new')"
        >
          New Collection
        </v-btn>
      </v-col>
    </v-row>

    <v-alert v-if="accessDenied" type="error" class="mb-4" density="compact">
      You do not have permission to view or manage collections.
    </v-alert>

    <template v-else>
      <v-card class="pa-4 mb-4" elevation="1">
        <v-row dense align="center">
          <v-col cols="12" md="6">
            <AdminCollectionsSearchBar @search="onSearch" @submit="applySearch" />
          </v-col>
          <v-col cols="12" md="auto">
            <v-btn color="primary" @click="applySearch">Search</v-btn>
          </v-col>
        </v-row>
      </v-card>

      <AdminCollectionsFilters
        :published="publishedFilter"
        @filter-change="onFilterChange"
      />

      <v-snackbar v-model="toast.open" :color="toast.color" location="bottom right" :timeout="5000">
        {{ toast.message }}
        <template #actions>
          <v-btn variant="text" size="small" @click="toast.open = false">Dismiss</v-btn>
        </template>
      </v-snackbar>

      <v-card elevation="1">
        <AdminCollectionsResults
          :collections="filteredCollections"
          :loading="loading"
          @edit="c => router.push('/edit/' + c.repositoryid)"
          @history="c => router.push('/history/' + c.repositoryid)"
          @delete="openDeleteDialog"
          @publish-change="onPublishChange"
          @weight-change="onWeightChange"
        />
      </v-card>

      <!-- Delete Confirmation Dialog -->
      <v-dialog v-model="deleteDialog.open" max-width="400">
        <v-card>
          <v-card-title class="text-h6 pa-4">Delete Collection</v-card-title>
          <v-divider />
          <v-card-text class="pa-4">
            <v-alert v-if="deleteDialog.error" type="error" class="mb-4" density="compact" closable @click:close="deleteDialog.error = null">
              {{ deleteDialog.error }}
            </v-alert>
            <p>Are you sure you want to delete <strong>{{ deleteDialog.collection?.repositoryid }}</strong>?</p>
            <p class="text-medium-emphasis text-body-2 mt-2">This action cannot be undone.</p>
          </v-card-text>
          <v-divider />
          <v-card-actions class="pa-3">
            <v-spacer />
            <v-btn variant="text" @click="deleteDialog.open = false">Cancel</v-btn>
            <v-btn color="error" variant="flat" :loading="saving" @click="confirmDelete">Delete</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import AdminCollectionsSearchBar from '../components/AdminCollectionsSearchBar.vue';
import AdminCollectionsFilters from '../components/AdminCollectionsFilters.vue';
import AdminCollectionsResults from '../components/AdminCollectionsResults.vue';
import { useCollectionsApi, isCollectionsAccessDenied } from '../composables/useCollectionsApi';

const router = useRouter();
const { siteUrl } = useAppConfig();
const { loading, listCollections, updateCollection, deleteCollection } = useCollectionsApi();

const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));

const breadcrumbItems = computed(() => [
  { title: 'Admin', href: `${siteBaseUrl.value}/admin` },
  { title: 'Collections', disabled: true },
]);

const accessDenied = ref(false);
const collections = ref([]);
const searchQuery = ref('');
const publishedFilter = ref('');
const saving = ref(false);

const toast = reactive({ open: false, message: '', color: 'error' });

function showError(e) {
  toast.message = e?.response?.data?.message || e?.message || 'An error occurred';
  toast.color = 'error';
  toast.open = true;
}

const deleteDialog = reactive({
  open: false,
  collection: null,
  error: null,
});

const filteredCollections = computed(() => {
  let list = collections.value;
  if (publishedFilter.value === '1') list = list.filter(c => c.ispublished == 1);
  else if (publishedFilter.value === '0') list = list.filter(c => c.ispublished == 0);
  const q = searchQuery.value.trim().toLowerCase();
  if (q) {
    list = list.filter(c =>
      (c.repositoryid || '').toLowerCase().includes(q) ||
      (c.title || '').toLowerCase().includes(q)
    );
  }
  return list;
});

async function fetchCollections() {
  accessDenied.value = false;
  try {
    collections.value = await listCollections();
  } catch (e) {
    collections.value = [];
    if (isCollectionsAccessDenied(e)) {
      accessDenied.value = true;
    } else {
      showError(e);
    }
  }
}

function onSearch(q) { searchQuery.value = q; }
function applySearch() { /* filtering is reactive; hook for future server-side search */ }
function onFilterChange(val) { publishedFilter.value = val; }

function openDeleteDialog(collection) {
  deleteDialog.collection = collection;
  deleteDialog.error = null;
  deleteDialog.open = true;
}

async function onPublishChange({ collection, published }) {
  try {
    await updateCollection({ repositoryid: collection.repositoryid, ispublished: published });
    await fetchCollections();
  } catch (e) {
    showError(e);
    await fetchCollections();
  }
}

async function onWeightChange({ collection, weight }) {
  try {
    await updateCollection({ repositoryid: collection.repositoryid, weight });
    await fetchCollections();
  } catch (e) {
    showError(e);
    await fetchCollections();
  }
}

async function confirmDelete() {
  if (!deleteDialog.collection) return;
  saving.value = true;
  deleteDialog.error = null;
  try {
    await deleteCollection(deleteDialog.collection.repositoryid);
    deleteDialog.open = false;
    await fetchCollections();
  } catch (e) {
    deleteDialog.error = e?.response?.data?.message || e.message || 'An error occurred';
  } finally {
    saving.value = false;
  }
}

onMounted(fetchCollections);
</script>

<style scoped>
.coll-breadcrumbs {
  font-size: 0.8125rem;
  margin-bottom: 0.5rem;
}

.coll-breadcrumbs :deep(.v-breadcrumbs-item),
.coll-breadcrumbs :deep(.v-breadcrumbs-divider) {
  font-size: 0.8125rem;
}
</style>
