<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="ctry-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-1">
      {{ t('Fix Country Mapping', 'Fix country mapping') }}
    </h1>
    <p class="text-body-2 text-medium-emphasis mb-4">
      {{
        t(
          'country_mappings_intro',
          'Country names in study metadata that do not match a country. Assigning one adds the name as an alias and links those studies.'
        )
      }}
    </p>

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
          {{ t('Total rows', 'Total rows') }}: {{ mappings.length }}
        </span>
      </div>
      <v-divider />
      <v-data-table
        :headers="headers"
        :items="mappings"
        :search="search"
        :loading="loading"
        :items-per-page="50"
        :items-per-page-options="[25, 50, 100, -1]"
        density="comfortable"
        class="ctry-table text-body-2"
        item-value="country_name"
      >
        <template #item.assign="{ item }">
          <div class="d-flex align-center ga-2 py-1">
            <v-autocomplete
              v-model="selection[item.country_name]"
              :items="countries"
              item-title="name"
              item-value="id"
              :placeholder="t('--SELECT--', 'Select country')"
              variant="outlined"
              density="compact"
              hide-details
              clearable
              style="min-width: 240px; max-width: 320px"
            />
            <v-btn
              color="primary"
              size="small"
              :disabled="!selection[item.country_name]"
              :loading="saving === item.country_name"
              @click="assign(item)"
            >
              {{ t('update', 'Update') }}
            </v-btn>
          </div>
        </template>
        <template #no-data>
          <div class="text-medium-emphasis py-6">
            {{ t('country_mappings_none', 'All study countries are mapped.') }}
          </div>
        </template>
      </v-data-table>
    </v-card>

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

defineOptions({ name: 'CountryMappingsPage' });

const { t } = useI18n();
const { siteUrl } = useAppConfig();
const { listCountries, listMappings, assignMapping } = useCountriesApi();
const { toast, showToast } = useFlashToast();

const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const breadcrumbItems = computed(() => [
  { title: t('admin', 'Admin'), href: `${siteBaseUrl.value}/admin` },
  { title: t('Countries', 'Countries'), to: { name: 'countries-list' } },
  { title: t('country_mappings', 'Country mappings'), disabled: true },
]);

const headers = computed(() => [
  { title: t('Country', 'Country'), key: 'country_name' },
  { title: t('Studies', 'Studies'), key: 'total', align: 'end', width: 100 },
  { title: t('Assign Country', 'Assign country'), key: 'assign', sortable: false },
]);

const mappings = ref([]);
const countries = ref([]);
const selection = reactive({});
const loading = ref(true);
const loadError = ref('');
const search = ref('');
const saving = ref(null);

async function load() {
  loading.value = true;
  loadError.value = '';
  try {
    const [rows, list] = await Promise.all([listMappings(), listCountries()]);
    mappings.value = rows;
    countries.value = list.map((c) => ({ id: c.id, name: `${c.name} (${c.iso})` }));
  } catch (e) {
    loadError.value = e.message;
  } finally {
    loading.value = false;
  }
}

async function assign(item) {
  saving.value = item.country_name;
  try {
    const country = await assignMapping(item.country_name, selection[item.country_name]);
    mappings.value = mappings.value.filter((m) => m.country_name !== item.country_name);
    delete selection[item.country_name];
    showToast(t('country_mapping_assigned', '“%s” now maps to %s.', item.country_name, country.name));
  } catch (e) {
    showToast(e.message, 'error');
  } finally {
    saving.value = null;
  }
}

onMounted(load);
</script>
