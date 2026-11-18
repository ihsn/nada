<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="rsec-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <v-row align="center" class="mb-4">
      <v-col cols="12" md="8">
        <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-1">
          {{ t('repository_sections_management', 'Collection groups') }}
        </h1>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Group collections under section headings. Used when listing collections on the site.
        </p>
      </v-col>
      <v-col cols="12" md="4" class="d-flex justify-end ga-2">
        <v-btn variant="text" color="primary" :href="`${siteBase}/admin/collections`">
          Collections
        </v-btn>
        <v-btn
          v-if="canEdit"
          color="primary"
          prepend-icon="mdi-plus"
          :to="{ name: 'section-new' }"
        >
          {{ t('repository_section_add', 'Add section') }}
        </v-btn>
      </v-col>
    </v-row>

    <v-alert v-if="error" type="error" class="mb-4" density="compact" closable @click:close="error = ''">
      {{ error }}
    </v-alert>
    <v-alert v-if="success" type="success" class="mb-4" density="compact" closable @click:close="success = ''">
      {{ success }}
    </v-alert>

    <v-card elevation="1">
      <div v-if="canDelete && selected.length" class="pa-3 d-flex align-center ga-3 border-b">
        <span class="text-body-2">{{ selected.length }} selected</span>
        <v-btn size="small" color="error" variant="tonal" prepend-icon="mdi-delete" @click="askBatchDelete">
          {{ t('delete', 'Delete') }}
        </v-btn>
      </div>

      <v-data-table
        v-model="selected"
        :headers="headers"
        :items="sections"
        :loading="loading"
        :items-per-page="25"
        :items-per-page-options="[25, 50, 100, -1]"
        :show-select="canDelete"
        density="comfortable"
        class="text-body-2"
        item-value="id"
      >
        <template #item.title="{ item }">
          <router-link
            v-if="canEdit"
            :to="{ name: 'section-edit', params: { id: item.id } }"
            class="rsec-link font-weight-medium"
          >
            {{ item.title }}
          </router-link>
          <span v-else class="font-weight-medium">{{ item.title }}</span>
        </template>
        <template #item.actions="{ item }">
          <div class="d-flex justify-end">
            <v-btn
              v-if="canEdit"
              icon="mdi-pencil-outline"
              size="small"
              variant="text"
              :to="{ name: 'section-edit', params: { id: item.id } }"
            />
            <v-btn
              v-if="canDelete"
              icon="mdi-delete-outline"
              size="small"
              variant="text"
              color="error"
              @click="openDelete(item)"
            />
          </div>
        </template>
        <template #no-data>
          <div class="py-8 text-medium-emphasis">{{ t('no_records_found', 'No records found') }}</div>
        </template>
      </v-data-table>
    </v-card>

    <v-dialog v-model="deleteDialog.open" max-width="460">
      <v-card>
        <v-card-title class="text-h6">{{ t('delete', 'Delete') }}</v-card-title>
        <v-card-text>
          <v-alert v-if="deleteDialog.error" type="error" class="mb-3" density="compact">
            {{ deleteDialog.error }}
          </v-alert>
          <p class="mb-0">
            {{ t('js_confirm_delete', 'Are you sure you want to delete the selected item(s)?') }}
            <strong v-if="deleteDialog.item">“{{ deleteDialog.item.title }}”</strong>
            <span v-else-if="deleteDialog.ids?.length"> ({{ deleteDialog.ids.length }})</span>
          </p>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="deleteDialog.open = false">{{ t('cancel', 'Cancel') }}</v-btn>
          <v-btn color="error" :loading="deleteDialog.busy" @click="confirmDelete">
            {{ t('delete', 'Delete') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useSectionsApi } from '../composables/useSectionsApi';

defineOptions({ name: 'SectionsListPage' });

const { siteUrl, config } = useAppConfig();
const api = useSectionsApi();

const loading = ref(false);
const error = ref('');
const success = ref('');
const sections = ref([]);
const selected = ref([]);
const deleteDialog = reactive({ open: false, item: null, ids: null, busy: false, error: '' });

const t = (key, fallback) => config.value?.translations?.[key] || fallback || key;
const canEdit = computed(() => !!config.value?.canEdit);
const canDelete = computed(() => !!config.value?.canDelete);
const siteBase = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));

const breadcrumbItems = computed(() => [
  { title: t('Home', 'Home'), href: `${siteBase.value}/admin` },
  { title: t('Collections', 'Collections'), href: `${siteBase.value}/admin/collections` },
  { title: t('repository_sections', 'Collection groups'), disabled: true },
]);

const headers = computed(() => {
  const cols = [
    { title: t('title', 'Title'), key: 'title', sortable: true },
    { title: t('weight', 'Weight'), key: 'weight', sortable: true, width: 120 },
  ];
  if (canEdit.value || canDelete.value) {
    cols.push({ title: t('actions', 'Actions'), key: 'actions', sortable: false, align: 'end' });
  }
  return cols;
});

async function refresh() {
  loading.value = true;
  error.value = '';
  try {
    sections.value = await api.list({ sort_by: 'weight', sort_order: 'asc' });
    selected.value = [];
  } catch (e) {
    error.value = e.message;
  } finally {
    loading.value = false;
  }
}

function openDelete(item) {
  deleteDialog.item = item;
  deleteDialog.ids = null;
  deleteDialog.error = '';
  deleteDialog.open = true;
}

function askBatchDelete() {
  deleteDialog.item = null;
  deleteDialog.ids = [...selected.value];
  deleteDialog.error = '';
  deleteDialog.open = true;
}

async function confirmDelete() {
  deleteDialog.busy = true;
  deleteDialog.error = '';
  try {
    if (deleteDialog.item) {
      await api.remove(deleteDialog.item.id);
    } else if (deleteDialog.ids?.length) {
      await api.batchDelete(deleteDialog.ids);
    }
    deleteDialog.open = false;
    success.value = t('form_update_success', 'Updated successfully');
    await refresh();
  } catch (e) {
    deleteDialog.error = e.message;
  } finally {
    deleteDialog.busy = false;
  }
}

onMounted(() => {
  const flash = typeof history !== 'undefined' ? history.state?.flashSuccess : null;
  if (flash) {
    success.value = flash;
    const rest = { ...history.state };
    delete rest.flashSuccess;
    history.replaceState(rest, '');
  }
  refresh();
});
</script>

<style>
.rsec-breadcrumbs {
  font-size: 0.8125rem;
  margin-bottom: 0.5rem;
}
.rsec-breadcrumbs .v-breadcrumbs-item,
.rsec-breadcrumbs .v-breadcrumbs-divider {
  opacity: 0.7;
}
.rsec-link {
  color: rgb(var(--v-theme-primary));
  text-decoration: none;
}
.rsec-link:hover {
  text-decoration: underline;
}
</style>
