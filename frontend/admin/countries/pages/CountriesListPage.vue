<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="ctry-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <div class="d-flex flex-wrap align-start justify-space-between ga-3 mb-4">
      <div>
        <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-1">
          {{ t('Countries', 'Countries') }}
        </h1>
        <p class="text-body-2 text-medium-emphasis mb-0">
          {{
            t(
              'countries_page_intro',
              'Aliases map other spellings found in study metadata to a country.'
            )
          }}
        </p>
      </div>
      <div class="d-flex flex-wrap ga-2">
        <v-btn variant="outlined" prepend-icon="mdi-shuffle-variant" :to="{ name: 'country-mappings' }">
          {{ t('country_mappings', 'Country mappings') }}
        </v-btn>
        <v-btn color="primary" prepend-icon="mdi-plus" :to="{ name: 'country-new' }">
          {{ t('add_country', 'Add country') }}
        </v-btn>
      </div>
    </div>

    <v-alert v-if="loadError" type="error" class="mb-4" closable @click:close="loadError = ''">
      {{ loadError }}
    </v-alert>

    <v-card elevation="1">
      <div class="d-flex align-center ga-3 px-4 py-3 flex-wrap">
        <v-text-field
          v-model="search"
          prepend-inner-icon="mdi-magnify"
          :placeholder="t('search', 'Search')"
          variant="outlined"
          density="compact"
          hide-details
          clearable
          style="max-width: 320px"
        />
        <v-spacer />
        <span class="text-caption text-medium-emphasis">
          {{ t('Countries', 'Countries') }}: {{ countries.length }}
        </span>
      </div>
      <v-divider />
      <v-data-table
        :headers="headers"
        :items="countries"
        :search="search"
        :custom-filter="filterCountry"
        :loading="loading"
        :items-per-page="50"
        :items-per-page-options="[25, 50, 100, -1]"
        density="comfortable"
        class="ctry-table text-body-2"
        item-value="id"
      >
        <template #item.name="{ item }">
          <router-link :to="{ name: 'country-edit', params: { id: item.id } }" class="ctry-link font-weight-medium">
            {{ item.name }}
          </router-link>
        </template>
        <template #item.aliases="{ item }">
          <div class="d-flex flex-wrap ga-1 py-1">
            <v-chip v-for="alias in item.aliases" :key="alias" size="x-small" variant="tonal">
              {{ alias }}
            </v-chip>
          </div>
        </template>
        <template #item.actions="{ item }">
          <v-tooltip :text="t('edit', 'Edit')" location="top">
            <template #activator="{ props: tip }">
              <v-btn
                v-bind="tip"
                icon
                variant="text"
                size="small"
                :to="{ name: 'country-edit', params: { id: item.id } }"
              >
                <v-icon size="20" icon="mdi-pencil-outline" />
              </v-btn>
            </template>
          </v-tooltip>
          <v-tooltip :text="t('delete', 'Delete')" location="top">
            <template #activator="{ props: tip }">
              <v-btn v-bind="tip" icon variant="text" size="small" color="error" @click="openDelete(item)">
                <v-icon size="20" icon="mdi-delete-outline" />
              </v-btn>
            </template>
          </v-tooltip>
        </template>
        <template #no-data>
          <div class="text-medium-emphasis py-6">{{ t('no_records_found', 'No records found') }}</div>
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
          <p class="mb-2">
            {{ t('js_confirm_delete', 'Are you sure you want to delete the selected item(s)?') }}
            <strong v-if="deleteDialog.item">“{{ deleteDialog.item.name }}”</strong>
          </p>
          <v-alert v-if="deleteDialog.item?.studies" type="warning" variant="tonal" density="compact">
            {{
              t(
                'country_delete_studies_warning',
                '%s studies use this country. They will appear under country mappings until reassigned.',
                deleteDialog.item.studies
              )
            }}
          </v-alert>
        </v-card-text>
        <v-divider />
        <v-card-actions class="pa-3">
          <v-spacer />
          <v-btn variant="text" @click="deleteDialog.open = false">{{ t('cancel', 'Cancel') }}</v-btn>
          <v-btn color="error" variant="flat" :loading="deleteDialog.loading" @click="confirmDelete">
            {{ t('delete', 'Delete') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="toast.open" :color="toast.color" location="bottom right" :timeout="4000">
      {{ toast.message }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useI18n } from '@/shared/composables/useI18n';
import { useCountriesApi } from '../composables/useCountriesApi';
import { useFlashToast } from '../composables/useFlashToast';

defineOptions({ name: 'CountriesListPage' });

const { t } = useI18n();
const { siteUrl } = useAppConfig();
const { listCountries, deleteCountry } = useCountriesApi();
const { toast, showToast, consumeFlash } = useFlashToast();

const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const breadcrumbItems = computed(() => [
  { title: t('admin', 'Admin'), href: `${siteBaseUrl.value}/admin` },
  { title: t('Countries', 'Countries'), disabled: true },
]);

const headers = computed(() => [
  { title: t('ID', 'ID'), key: 'id', width: 80 },
  { title: t('iso', 'ISO'), key: 'iso', width: 90 },
  { title: t('Country', 'Country'), key: 'name' },
  { title: t('Aliases', 'Aliases'), key: 'aliases', sortable: false },
  { title: t('Studies', 'Studies'), key: 'studies', align: 'end', width: 100 },
  { title: '', key: 'actions', sortable: false, align: 'end', cellProps: { class: 'ctry-col-actions' } },
]);

const countries = ref([]);
const loading = ref(true);
const loadError = ref('');
const search = ref('');
const deleteDialog = reactive({ open: false, item: null, error: '', loading: false });

function filterCountry(_value, query, item) {
  const q = String(query || '').toLowerCase();
  const row = item.raw;
  return (
    row.name.toLowerCase().includes(q) ||
    row.iso.toLowerCase().includes(q) ||
    row.aliases.some((a) => a.toLowerCase().includes(q))
  );
}

async function load() {
  loading.value = true;
  loadError.value = '';
  try {
    countries.value = await listCountries();
  } catch (e) {
    loadError.value = e.message;
  } finally {
    loading.value = false;
  }
}

function openDelete(item) {
  deleteDialog.item = item;
  deleteDialog.error = '';
  deleteDialog.open = true;
}

async function confirmDelete() {
  deleteDialog.loading = true;
  deleteDialog.error = '';
  try {
    await deleteCountry(deleteDialog.item.id);
    deleteDialog.open = false;
    showToast(t('form_update_success', 'Your changes have been saved!'));
    await load();
  } catch (e) {
    deleteDialog.error = e.message;
  } finally {
    deleteDialog.loading = false;
  }
}

onMounted(() => {
  consumeFlash();
  load();
});
</script>
