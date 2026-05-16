<template>
  <div class="admin-catalog-filter-stack">
    <v-card class="admin-catalog-surface" rounded="lg" elevation="1">
      <div
        class="admin-catalog-filter-card__header d-flex align-center cursor-pointer ga-2"
        @click="expanded.idno = !expanded.idno"
      >
        <span class="text-subtitle-2 font-weight-medium">{{ t('idno') }}</span>
        <v-spacer />
        <v-icon size="small">{{ expanded.idno ? 'mdi-chevron-up' : 'mdi-chevron-down' }}</v-icon>
      </div>
      <v-expand-transition>
        <div v-show="expanded.idno" class="admin-catalog-filter-card__body">
          <v-text-field
            v-model="selected.idno"
            :placeholder="t('filter_by_idno_placeholder')"
            density="compact"
            variant="outlined"
            hide-details
            clearable
            @update:model-value="emitFilterChange"
          />
        </div>
      </v-expand-transition>
    </v-card>

    <v-card class="admin-catalog-surface" rounded="lg" elevation="1">
      <div
        class="admin-catalog-filter-card__header d-flex align-center cursor-pointer ga-2"
        @click="expanded.published = !expanded.published"
      >
        <span class="text-subtitle-2 font-weight-medium">{{ t('published') }}</span>
        <v-spacer />
        <v-icon size="small">{{ expanded.published ? 'mdi-chevron-up' : 'mdi-chevron-down' }}</v-icon>
      </div>
      <v-expand-transition>
        <div v-show="expanded.published" class="admin-catalog-filter-card__body">
          <v-select
            v-model="selected.published"
            :items="publishedOptions"
            item-title="title"
            item-value="value"
            density="compact"
            variant="outlined"
            hide-details
            clearable
            @update:model-value="emitFilterChange"
          />
        </div>
      </v-expand-transition>
    </v-card>

    <v-card v-for="section in filterSections" :key="section.key" class="admin-catalog-surface" rounded="lg" elevation="1">
      <div
        class="admin-catalog-filter-card__header d-flex align-center cursor-pointer ga-2"
        @click="expanded[section.key] = !expanded[section.key]"
      >
        <span class="text-subtitle-2 font-weight-medium">{{ section.title }}</span>
        <v-chip v-if="selected[section.key].length" size="x-small" class="flex-shrink-0" color="primary">
          {{ selected[section.key].length }}
        </v-chip>
        <v-spacer />
        <v-icon size="small">{{ expanded[section.key] ? 'mdi-chevron-up' : 'mdi-chevron-down' }}</v-icon>
      </div>
      <v-expand-transition>
        <div v-show="expanded[section.key]" class="admin-catalog-filter-card__body">
          <v-text-field
            v-model="search[section.key]"
            :placeholder="t('search_placeholder', 'Search...', section.title.toLowerCase())"
            density="compact"
            variant="outlined"
            hide-details
            clearable
          />
            <div
              :class="['filter-options-list', { scrollable: section.filtered.length > 20 }]"
              style="max-height: 400px; overflow-y: auto"
            >
              <div
                v-for="option in section.filtered"
                :key="`${section.key}-${option.id}`"
                class="filter-option-row"
                @click="toggleFilterOption(section.key, option.id)"
              >
                <v-checkbox
                  :model-value="selected[section.key].includes(option.id)"
                  density="compact"
                  hide-details
                  class="filter-option-row__checkbox"
                  :aria-label="String(option.name)"
                  @click.stop
                  @update:model-value="(checked) => setSelection(section.key, option.id, !!checked)"
                />
                <span class="filter-option-row__name text-truncate" :title="String(option.name)">{{ option.name }}</span>
                <span
                  v-if="option.count != null"
                  class="filter-option-row__count text-medium-emphasis tabular-nums text-right"
                  >{{ option.count }}</span
                >
              </div>
            </div>
        </div>
      </v-expand-transition>
    </v-card>

    <v-btn block color="primary" variant="elevated" rounded="lg" size="default" @click="emitFilterChange">
      {{ t('apply_filters') }}
    </v-btn>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useCatalogApi } from '../composables/useCatalogApi';
import { useI18n } from '@/shared/composables/useI18n';

defineOptions({ name: 'AdminCatalogFilters' });

const { t } = useI18n();

const props = defineProps({
  filters: { type: Object, default: () => ({}) },
  ownerRepo: { type: String, default: '' },
});

const emit = defineEmits(['filter-change', 'filter-options']);

const { fetchFilterOptions } = useCatalogApi();

const countries = ref([]);
const collections = ref([]);
const tags = ref([]);
const dataAccess = ref([]);
const dataTypes = ref([]);

const publishedOptions = computed(() => [
  { title: t('all'), value: '' },
  { title: t('published'), value: '1' },
  { title: t('unpublished'), value: '0' },
]);

const expanded = ref({
  idno: true,
  published: true,
  countries: true,
  collections: true,
  tags: true,
  dataAccess: true,
  dataTypes: true,
});

const selected = ref({
  countries: [],
  collections: [],
  tags: [],
  dataAccess: [],
  dataTypes: [],
  idno: '',
  published: '',
});

const search = ref({
  countries: '',
  collections: '',
  tags: '',
  dataAccess: '',
  dataTypes: '',
});

const filterSections = computed(() => [
  { key: 'collections', title: t('repositories'), filtered: filteredCollections.value },
  { key: 'dataTypes', title: t('data_types'), filtered: filteredDataTypes.value },
  { key: 'dataAccess', title: t('data_access'), filtered: filteredDataAccess.value },
  { key: 'countries', title: t('countries'), filtered: filteredCountries.value },
  { key: 'tags', title: t('tags'), filtered: filteredTags.value },
]);

const filteredCountries = computed(() =>
  !search.value.countries
    ? countries.value
    : countries.value.filter((o) =>
        o.name.toLowerCase().includes(search.value.countries.toLowerCase())
      )
);
const filteredCollections = computed(() =>
  !search.value.collections
    ? collections.value
    : collections.value.filter((o) =>
        o.name.toLowerCase().includes(search.value.collections.toLowerCase())
      )
);
const filteredTags = computed(() =>
  !search.value.tags
    ? tags.value
    : tags.value.filter((o) =>
        o.name.toLowerCase().includes(search.value.tags.toLowerCase())
      )
);
const filteredDataAccess = computed(() =>
  !search.value.dataAccess
    ? dataAccess.value
    : dataAccess.value.filter((o) =>
        o.name.toLowerCase().includes(search.value.dataAccess.toLowerCase())
      )
);
const filteredDataTypes = computed(() =>
  !search.value.dataTypes
    ? dataTypes.value
    : dataTypes.value.filter((o) =>
        o.name.toLowerCase().includes(search.value.dataTypes.toLowerCase())
      )
);

function setSelection(filterName, optionId, checked) {
  const arr = selected.value[filterName];
  const idx = arr.indexOf(optionId);
  if (checked && idx === -1) arr.push(optionId);
  if (!checked && idx !== -1) arr.splice(idx, 1);
  emitFilterChange();
}

function toggleFilterOption(filterName, optionId) {
  const arr = selected.value[filterName];
  setSelection(filterName, optionId, !arr.includes(optionId));
}

function emitFilterChange() {
  const payload = { ...selected.value };
  if (payload.idno === null || payload.idno === undefined) {
    payload.idno = '';
  }
  emit('filter-change', payload);
}

watch(
  () => props.filters,
  (f) => {
    if (f && typeof f === 'object') {
      selected.value = {
        countries: Array.isArray(f.countries) ? [...f.countries] : [],
        collections: Array.isArray(f.collections) ? [...f.collections] : [],
        tags: Array.isArray(f.tags) ? [...f.tags] : [],
        dataAccess: Array.isArray(f.dataAccess) ? [...f.dataAccess] : [],
        dataTypes: Array.isArray(f.dataTypes) ? [...f.dataTypes] : [],
        idno: f.idno ?? '',
        published: f.published ?? '',
      };
    }
  },
  { immediate: true, deep: true }
);

onMounted(async () => {
  const owner_repo = props.ownerRepo && props.ownerRepo !== 'central' ? props.ownerRepo : undefined;
  const opts = await fetchFilterOptions(owner_repo != null ? { owner_repo } : {});
  countries.value = opts.countries;
  collections.value = opts.collections;
  tags.value = opts.tags;
  dataAccess.value = opts.dataAccess;
  dataTypes.value = opts.dataTypes;
  emit('filter-options', {
    countries: opts.countries,
    collections: opts.collections,
    tags: opts.tags,
    dataAccess: opts.dataAccess,
    dataTypes: opts.dataTypes,
  });
});
</script>

<style scoped>
.filter-options-list.scrollable {
  max-height: 400px;
  overflow-y: auto;
}

.cursor-pointer {
  cursor: pointer;
}
</style>
