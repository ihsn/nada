<template>
  <div>
    <h1 class="text-h5 font-weight-medium mb-4">Collections</h1>

    <AdminCollectionsSearchBar
      :loading="loading"
      @search="onSearch"
      @new-collection="router.push('/new')"
    />

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

    <AdminCollectionsResults
      :collections="filteredCollections"
      :loading="loading"
      @edit="c => router.push('/edit/' + c.repositoryid)"
      @history="c => router.push('/history/' + c.repositoryid)"
      @delete="openDeleteDialog"
      @publish-change="onPublishChange"
      @weight-change="onWeightChange"
    />

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
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import AdminCollectionsSearchBar from '../components/AdminCollectionsSearchBar.vue';
import AdminCollectionsFilters from '../components/AdminCollectionsFilters.vue';
import AdminCollectionsResults from '../components/AdminCollectionsResults.vue';
import { useCollectionsApi } from '../composables/useCollectionsApi';

const router = useRouter();
const { loading, listCollections, updateCollection, deleteCollection } = useCollectionsApi();

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
  collections.value = await listCollections();
}

function onSearch(q) { searchQuery.value = q; }
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
    await fetchCollections(); // revert optimistic UI
  }
}

async function onWeightChange({ collection, weight }) {
  try {
    await updateCollection({ repositoryid: collection.repositoryid, weight });
    await fetchCollections();
  } catch (e) {
    showError(e);
    await fetchCollections(); // revert optimistic UI
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
