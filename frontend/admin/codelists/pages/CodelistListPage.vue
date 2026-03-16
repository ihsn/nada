<template>
  <div>
    <div class="d-flex justify-end mb-3">
      <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreateDialog">
        Add codelist
      </v-btn>
    </div>
    <CodelistList
      ref="listRef"
      :codelists="codelists"
      :loading="loading"
      @manage="goToDetail"
      @delete="confirmDeleteCodelist"
      @saved="onCodelistSaved"
    />

    <v-dialog v-model="deleteDialog.show" max-width="400" persistent>
      <v-card>
        <v-card-title>Delete codelist?</v-card-title>
        <v-card-text>
          Delete &quot;{{ deleteDialog.codelist?.name }}&quot;? All items, groups, and translations will be removed.
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="deleteDialog.show = false">Cancel</v-btn>
          <v-btn color="error" :loading="deleteDialog.saving" @click="doDeleteCodelist">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, inject } from 'vue';
import { useRouter } from 'vue-router';
import CodelistList from '../components/CodelistList.vue';
import { useCodelistsApi } from '../composables/useCodelistsApi';

defineOptions({ name: 'CodelistListPage' });

const router = useRouter();
const setMessage = inject('setMessage', () => {});

const {
  loading,
  fetchCodelists,
  createCodelist,
  updateCodelist,
  deleteCodelist,
} = useCodelistsApi();

const listRef = ref(null);
const codelists = ref([]);
const deleteDialog = reactive({ show: false, saving: false, codelist: null });

function openCreateDialog() {
  listRef.value?.openCreate?.();
}

async function loadList() {
  try {
    codelists.value = await fetchCodelists(true);
  } catch (e) {
    setMessage(e?.response?.data?.message || e?.message || 'Failed to load codelists', 'error');
  }
}

function goToDetail(codelist) {
  router.push({ name: 'codelist-detail', params: { id: String(codelist.id) } });
}

async function onCodelistSaved(payload) {
  try {
    if (payload.isEdit && payload.codelistId != null) {
      await updateCodelist(payload.codelistId, {
        name: payload.name,
        description: payload.description,
      });
      setMessage('Codelist updated.', 'success');
    } else if (!payload.isEdit) {
      await createCodelist({ name: payload.name, description: payload.description });
      setMessage('Codelist created.', 'success');
    }
    await loadList();
  } catch (e) {
    setMessage(e?.response?.data?.message || e?.message || 'Save failed', 'error');
  }
}

function confirmDeleteCodelist(codelist) {
  deleteDialog.codelist = codelist;
  deleteDialog.show = true;
}

async function doDeleteCodelist() {
  if (!deleteDialog.codelist) return;
  deleteDialog.saving = true;
  try {
    await deleteCodelist(deleteDialog.codelist.id);
    setMessage('Codelist deleted.', 'success');
    deleteDialog.show = false;
    await loadList();
  } catch (e) {
    setMessage(e?.response?.data?.message || e?.message || 'Delete failed', 'error');
  } finally {
    deleteDialog.saving = false;
  }
}

onMounted(() => {
  loadList();
});
</script>
