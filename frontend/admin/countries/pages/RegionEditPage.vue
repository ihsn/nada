<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="ctry-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-4">{{ pageTitle }}</h1>

    <v-alert v-if="loadError" type="error" class="mb-4" closable @click:close="loadError = ''">
      {{ loadError }}
    </v-alert>

    <v-progress-linear v-if="pageLoading" indeterminate color="primary" class="mb-4" />

    <v-card v-else-if="!loadError" class="pa-4" elevation="1" max-width="640">
      <v-alert v-if="saveError" type="error" class="mb-4" density="compact" closable @click:close="saveError = ''">
        {{ saveError }}
      </v-alert>

      <div class="ctry-field-group">
        <div class="ctry-field-label">{{ t('Select Parent', 'Parent') }}</div>
        <v-select
          v-model="form.pid"
          :items="parentOptions"
          item-title="title"
          item-value="id"
          variant="outlined"
          density="compact"
          :disabled="hasChildren"
          :hint="hasChildren ? t('region_has_children_hint', 'This group has regions, so it stays top-level.') : ''"
          persistent-hint
          hide-details="auto"
        />
      </div>
      <div class="ctry-field-group">
        <div class="ctry-field-label">{{ t('name', 'Name') }} *</div>
        <v-text-field
          v-model="form.title"
          variant="outlined"
          density="compact"
          maxlength="45"
          hide-details="auto"
          autocomplete="off"
        />
      </div>
      <div class="ctry-field-group">
        <div class="ctry-field-label">{{ t('weight', 'Weight') }}</div>
        <v-text-field
          v-model.number="form.weight"
          type="number"
          min="0"
          max="999"
          variant="outlined"
          density="compact"
          hide-details="auto"
          style="max-width: 140px"
        />
      </div>

      <div v-if="form.pid" class="ctry-field-group">
        <div class="ctry-field-label d-flex align-center ga-2">
          {{ t('Select Countries', 'Countries') }}
          <span class="text-caption text-medium-emphasis font-weight-regular">
            {{ t('selected', 'Selected') }}: {{ form.countries.length }}
          </span>
          <v-btn
            v-if="form.countries.length"
            size="x-small"
            variant="text"
            color="primary"
            @click="form.countries = []"
          >
            {{ t('clear', 'Clear') }}
          </v-btn>
        </div>
        <v-autocomplete
          v-model="countryToAdd"
          :items="availableCountries"
          item-title="name"
          item-value="id"
          :placeholder="t('region_add_country', 'Search and add a country')"
          variant="outlined"
          density="compact"
          clearable
          hide-details="auto"
          no-data-text="No countries left to add"
          @update:model-value="addCountry"
        />
        <div class="ctry-selected-box mt-3">
          <div v-if="!selectedCountries.length" class="text-body-2 text-medium-emphasis pa-3">
            {{ t('region_no_countries_selected', 'No countries selected yet.') }}
          </div>
          <div v-else class="ctry-selected-list">
            <div v-for="country in selectedCountries" :key="country.id" class="ctry-selected-row">
              <span class="text-body-2">{{ country.name }}</span>
              <v-btn
                icon
                variant="text"
                size="x-small"
                color="error"
                :title="t('remove', 'Remove')"
                @click="removeCountry(country.id)"
              >
                <v-icon size="18" icon="mdi-close" />
              </v-btn>
            </div>
          </div>
        </div>
      </div>
      <p v-else class="text-caption text-medium-emphasis mt-3 mb-0">
        {{ t('region_top_level_hint', 'Top-level groups do not hold countries. Pick a parent to add countries.') }}
      </p>

      <div class="ctry-form-actions d-flex ga-2">
        <v-btn color="primary" :loading="saving" :disabled="!form.title.trim()" @click="submit">
          {{ isNew ? t('create', 'Create') : t('update', 'Update') }}
        </v-btn>
        <v-btn variant="text" :to="{ name: 'regions-list' }">{{ t('cancel', 'Cancel') }}</v-btn>
      </div>
    </v-card>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useI18n } from '@/shared/composables/useI18n';
import { useCountriesApi } from '../composables/useCountriesApi';

defineOptions({ name: 'RegionEditPage' });

const props = defineProps({
  id: { type: [String, Number], default: null },
});

const route = useRoute();
const router = useRouter();
const { t } = useI18n();
const { siteUrl } = useAppConfig();
const { listRegions, listRegionCountries, getRegion, createRegion, updateRegion } = useCountriesApi();

const isNew = computed(() => !props.id);
const pageTitle = computed(() =>
  isNew.value ? t('Create New Region', 'Create region') : t('Edit Region', 'Edit region')
);
const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const breadcrumbItems = computed(() => [
  { title: t('admin', 'Admin'), href: `${siteBaseUrl.value}/admin` },
  { title: t('Regions', 'Regions'), to: { name: 'regions-list' } },
  { title: pageTitle.value, disabled: true },
]);

const regions = ref([]);
const countries = ref([]);
const region = ref(null);
const pageLoading = ref(true);
const loadError = ref('');
const saving = ref(false);
const saveError = ref('');
const form = reactive({ pid: 0, title: '', weight: 0, countries: [] });
const countryToAdd = ref(null);

const hasChildren = computed(() => (region.value?.children || 0) > 0);
const parentOptions = computed(() => [
  { id: 0, title: t('region_top_level', '— None (top-level group) —') },
  ...regions.value
    .filter((r) => r.pid === 0 && r.id !== region.value?.id)
    .map((r) => ({ id: r.id, title: r.title })),
]);

const countryById = computed(() => {
  const map = new Map();
  for (const c of countries.value) map.set(c.id, c);
  return map;
});

const availableCountries = computed(() => {
  const selected = new Set(form.countries);
  return countries.value.filter((c) => !selected.has(c.id));
});

const selectedCountries = computed(() =>
  form.countries
    .map((id) => countryById.value.get(id) || { id, name: `#${id}` })
    .sort((a, b) => a.name.localeCompare(b.name))
);

function addCountry(id) {
  if (id == null || id === '') return;
  const cid = Number(id);
  if (!Number.isFinite(cid) || form.countries.includes(cid)) {
    countryToAdd.value = null;
    return;
  }
  form.countries.push(cid);
  countryToAdd.value = null;
}

function removeCountry(id) {
  form.countries = form.countries.filter((cid) => cid !== id);
}

async function load() {
  pageLoading.value = true;
  loadError.value = '';
  saveError.value = '';
  region.value = null;
  try {
    const [list, countryList, current] = await Promise.all([
      listRegions(),
      listRegionCountries(),
      isNew.value ? Promise.resolve(null) : getRegion(props.id),
    ]);
    regions.value = list;
    countries.value = countryList;
    region.value = current;
    if (current) {
      Object.assign(form, {
        pid: current.pid,
        title: current.title,
        weight: current.weight,
        countries: [...current.countries],
      });
    } else {
      const pid = Number(route.query.pid) || 0;
      Object.assign(form, {
        pid: list.some((r) => r.id === pid && r.pid === 0) ? pid : 0,
        title: '',
        weight: 0,
        countries: [],
      });
    }
  } catch (e) {
    loadError.value = e.message;
  } finally {
    pageLoading.value = false;
  }
}

async function submit() {
  saving.value = true;
  saveError.value = '';
  const payload = {
    pid: form.pid || 0,
    title: form.title.trim(),
    weight: Number.isFinite(form.weight) ? form.weight : 0,
    countries: form.pid ? form.countries : [],
  };
  try {
    if (isNew.value) {
      await createRegion(payload);
    } else {
      await updateRegion(props.id, payload);
    }
    await router.push({
      name: 'regions-list',
      state: { flashSuccess: t('form_update_success', 'Your changes have been saved!') },
    });
  } catch (e) {
    saveError.value = e.message;
  } finally {
    saving.value = false;
  }
}

watch(() => props.id, load, { immediate: true });
</script>

<style scoped>
.ctry-selected-box {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
  border-radius: 4px;
  background: rgba(var(--v-theme-on-surface), 0.02);
  max-height: 280px;
  overflow: auto;
}

.ctry-selected-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 4px 8px 4px 12px;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.06);
}

.ctry-selected-row:last-child {
  border-bottom: none;
}
</style>
