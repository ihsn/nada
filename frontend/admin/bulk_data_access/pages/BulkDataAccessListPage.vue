<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="bda-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <div class="d-flex flex-wrap align-center justify-space-between gap-3 mb-4">
      <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-0">
        {{ t('bulk_da_collections', 'Bulk data access collections') }}
      </h1>
      <v-btn v-if="canEdit" color="primary" prepend-icon="mdi-plus" @click="goAdd">
        {{ t('da_collection_create', 'Create new collection') }}
      </v-btn>
    </div>

    <v-alert v-if="loadError" type="error" class="mb-4" density="compact" closable @click:close="loadError = ''">
      {{ loadError }}
    </v-alert>

    <v-card elevation="1">
      <div v-if="rows.length && canDelete" class="pa-3 d-flex flex-wrap align-center gap-2 border-b">
        <v-select
          v-model="batchAction"
          :items="batchItems"
          item-title="title"
          item-value="value"
          density="compact"
          variant="outlined"
          hide-details
          style="max-width: 220px"
        />
        <v-btn size="small" variant="tonal" :disabled="!selected.length || batchAction === '-1'" @click="onBatchApply">
          {{ t('apply', 'Apply') }}
        </v-btn>
      </div>

      <v-data-table
        v-model="selected"
        :headers="headers"
        :items="rows"
        :loading="loading"
        item-value="id"
        :show-select="canDelete"
        class="elevation-0"
      >
        <template #item.title="{ item }">
          <a
            v-if="canEdit"
            href="#"
            class="text-primary text-decoration-none"
            @click.prevent="goEdit(item.id)"
          >{{ item.title }}</a>
          <span v-else>{{ item.title }}</span>
        </template>
        <template #item.description="{ item }">
          <span class="text-body-2">{{ truncate(item.description, 120) }}</span>
        </template>
        <template #item.actions="{ item }">
          <template v-if="canEdit">
            <v-btn size="small" variant="text" color="primary" @click="goEdit(item.id)">{{ t('edit', 'Edit') }}</v-btn>
            <span class="text-medium-emphasis">|</span>
            <v-btn size="small" variant="text" color="primary" @click="goAttach(item.id)">{{ t('attach_studies', 'Attach studies') }}</v-btn>
          </template>
          <template v-if="canDelete">
            <span v-if="canEdit" class="text-medium-emphasis">|</span>
            <v-btn size="small" variant="text" color="error" @click="openDeleteSingle(item)">{{ t('delete', 'Delete') }}</v-btn>
          </template>
        </template>
      </v-data-table>

      <v-card-text v-if="!loading && !rows.length" class="text-medium-emphasis">
        {{ t('no_records_found', 'No records found') }}
      </v-card-text>
    </v-card>

    <v-dialog v-model="deleteDialog.open" max-width="420">
      <v-card>
        <v-card-title class="text-h6">{{ t('delete', 'Delete') }}</v-card-title>
        <v-card-text>
          {{ deleteDialog.message }}
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="deleteDialog.open = false">{{ t('cancel', 'Cancel') }}</v-btn>
          <v-btn color="error" variant="flat" :loading="deleteDialog.saving" @click="confirmDelete"> {{ t('delete', 'Delete') }} </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="toast.open" :color="toast.color" location="bottom right" :timeout="4000">
      {{ toast.message }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useBulkDataAccessApi } from '../composables/useBulkDataAccessApi';
import { useI18n } from '@/shared/composables/useI18n';
import { useAppConfig } from '@/shared/composables/useAppConfig';

defineOptions({ name: 'BulkDataAccessListPage' });

const { t } = useI18n();
const router = useRouter();
const { siteUrl, canEdit, canDelete } = useAppConfig();
const { loading, fetchCollections, deleteCollections } = useBulkDataAccessApi();

const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));

const breadcrumbItems = computed(() => [
  { title: t('home', 'Home'), href: `${siteBaseUrl.value}/admin` },
  { title: t('bulk_da_collections', 'Bulk data access collections'), disabled: true },
]);

const rows = ref([]);
const selected = ref([]);
const loadError = ref('');
const batchAction = ref('-1');

const batchItems = computed(() => [
  { title: '—', value: '-1' },
  { title: t('delete', 'Delete'), value: 'delete' },
]);

const headers = computed(() => [
  { title: t('title', 'Title'), key: 'title', sortable: true },
  { title: t('description', 'Description'), key: 'description', sortable: false },
  { title: t('actions', 'Actions'), key: 'actions', sortable: false, width: '360px' },
]);

const deleteDialog = ref({
  open: false,
  message: '',
  ids: [],
  saving: false,
});

const toast = ref({ open: false, message: '', color: 'success' });

function truncate(s, n) {
  if (!s) return '';
  const str = String(s);
  return str.length <= n ? str : `${str.slice(0, n)}…`;
}

function goAdd() {
  router.push({ name: 'bda-add' });
}

function goEdit(id) {
  router.push({ name: 'bda-edit', params: { id: String(id) } });
}

function goAttach(id) {
  router.push({ name: 'bda-attach', params: { id: String(id) } });
}

async function load() {
  loadError.value = '';
  try {
    rows.value = await fetchCollections();
  } catch (e) {
    loadError.value = e?.message || 'Error';
  }
}

function openDeleteSingle(item) {
  deleteDialog.value = {
    open: true,
    message: t('confirm_delete', 'Delete this collection?'),
    ids: [item.id],
    saving: false,
  };
}

function onBatchApply() {
  if (batchAction.value !== 'delete') return;
  if (!selected.value.length) return;
  deleteDialog.value = {
    open: true,
    message: t('confirm_delete_selected', 'Delete selected collections?'),
    ids: [...selected.value],
    saving: false,
  };
}

async function confirmDelete() {
  deleteDialog.value.saving = true;
  try {
    await deleteCollections(deleteDialog.value.ids);
    deleteDialog.value.open = false;
    selected.value = [];
    batchAction.value = '-1';
    toast.value = { open: true, message: t('msg_deleted', 'Deleted'), color: 'success' };
    await load();
  } catch (e) {
    toast.value = { open: true, message: e?.message || 'Error', color: 'error' };
  } finally {
    deleteDialog.value.saving = false;
  }
}

onMounted(load);
</script>

<style scoped>
.bda-breadcrumbs :deep(.v-breadcrumbs-item),
.bda-breadcrumbs :deep(.v-breadcrumbs-divider) {
  font-size: 0.875rem;
}
.border-b {
  border-bottom: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
}
</style>
