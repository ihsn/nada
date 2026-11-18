<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="daw-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <div class="mb-5">
      <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-1">
        {{ t('Whitelist data access', 'Whitelist data access') }}
      </h1>
      <p class="text-body-2 text-medium-emphasis mb-0">
        Grant selected users data-access rights for a collection without a licensed request.
      </p>
    </div>

    <v-alert v-if="error" type="error" class="mb-4" density="compact" closable @click:close="error = ''">
      {{ error }}
    </v-alert>
    <v-alert v-if="success" type="success" class="mb-4" density="compact" closable @click:close="success = ''">
      {{ success }}
    </v-alert>

    <v-card class="pa-4 mb-5" elevation="1">
      <div class="text-body-2 font-weight-bold text-high-emphasis mb-3">Add to whitelist</div>
      <v-row dense align="end">
        <v-col cols="12" md="5">
          <div class="text-body-2 font-weight-bold text-high-emphasis mb-1">{{ t('Email', 'Email') }}</div>
          <v-text-field
            v-model="form.email"
            type="email"
            density="compact"
            variant="outlined"
            hide-details="auto"
            :error-messages="formErrors.email"
            placeholder="user@example.com"
          />
        </v-col>
        <v-col cols="12" md="5">
          <div class="text-body-2 font-weight-bold text-high-emphasis mb-1">Collection</div>
          <v-select
            v-model="form.repositoryId"
            :items="collectionItems"
            item-title="label"
            item-value="id"
            density="compact"
            variant="outlined"
            hide-details="auto"
            :error-messages="formErrors.repositoryId"
            placeholder="Select"
          />
        </v-col>
        <v-col cols="12" md="2">
          <v-btn color="primary" block :loading="saving" :disabled="saving" @click="onCreate">
            Whitelist
          </v-btn>
        </v-col>
      </v-row>
    </v-card>

    <v-card elevation="1">
      <v-data-table
        :headers="headers"
        :items="rows"
        :loading="loading"
        :items-per-page="25"
        :items-per-page-options="[25, 50, 100, -1]"
        density="comfortable"
        class="text-body-2"
        item-value="id"
      >
        <template #item.actions="{ item }">
          <v-btn
            icon="mdi-delete-outline"
            size="small"
            variant="text"
            color="error"
            :title="t('delete', 'Delete')"
            @click="openDelete(item)"
          />
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
            <strong v-if="deleteDialog.item">
              “{{ deleteDialog.item.email }}” / {{ deleteDialog.item.collection_name }}
            </strong>
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
import { useWhitelistApi } from '../composables/useWhitelistApi';

defineOptions({ name: 'WhitelistPage' });

const { siteUrl, config } = useAppConfig();
const api = useWhitelistApi();

const loading = ref(false);
const saving = ref(false);
const error = ref('');
const success = ref('');
const rows = ref([]);
const collections = ref([]);
const form = reactive({ email: '', repositoryId: null });
const formErrors = reactive({ email: '', repositoryId: '' });
const deleteDialog = reactive({ open: false, item: null, busy: false, error: '' });

const t = (key, fallback) => config.value?.translations?.[key] || fallback || key;

const siteBase = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const breadcrumbItems = computed(() => [
  { title: t('Home', 'Home'), href: `${siteBase.value}/admin` },
  { title: t('Whitelist data access', 'Whitelist data access'), disabled: true },
]);

const collectionItems = computed(() =>
  collections.value.map((c) => ({
    id: c.id,
    label: `${c.title} (${c.repositoryid})`,
  }))
);

const headers = [
  { title: t('Email', 'Email'), key: 'email', sortable: true },
  { title: 'Collection', key: 'collection_name', sortable: true },
  { title: t('actions', 'Actions'), key: 'actions', sortable: false, align: 'end' },
];

async function refresh() {
  loading.value = true;
  error.value = '';
  try {
    const data = await api.load();
    rows.value = data.rows;
    collections.value = data.collections;
  } catch (e) {
    error.value = e.message;
  } finally {
    loading.value = false;
  }
}

function validateForm() {
  formErrors.email = '';
  formErrors.repositoryId = '';
  let ok = true;
  if (!String(form.email || '').trim()) {
    formErrors.email = 'Email is required';
    ok = false;
  }
  if (!form.repositoryId) {
    formErrors.repositoryId = 'Collection is required';
    ok = false;
  }
  return ok;
}

async function onCreate() {
  if (!validateForm()) return;
  saving.value = true;
  error.value = '';
  success.value = '';
  try {
    await api.create({ email: String(form.email).trim(), repositoryId: form.repositoryId });
    form.email = '';
    form.repositoryId = null;
    success.value = t('form_update_success', 'Updated successfully');
    await refresh();
  } catch (e) {
    error.value = e.message;
  } finally {
    saving.value = false;
  }
}

function openDelete(item) {
  deleteDialog.item = item;
  deleteDialog.error = '';
  deleteDialog.open = true;
}

async function confirmDelete() {
  if (!deleteDialog.item) return;
  deleteDialog.busy = true;
  deleteDialog.error = '';
  try {
    await api.remove(deleteDialog.item.id);
    deleteDialog.open = false;
    success.value = t('form_update_success', 'Updated successfully');
    await refresh();
  } catch (e) {
    deleteDialog.error = e.message;
  } finally {
    deleteDialog.busy = false;
  }
}

onMounted(refresh);
</script>

<style>
.daw-breadcrumbs {
  font-size: 0.8125rem;
  margin-bottom: 0.5rem;
}
.daw-breadcrumbs .v-breadcrumbs-item,
.daw-breadcrumbs .v-breadcrumbs-divider {
  opacity: 0.7;
}
</style>
