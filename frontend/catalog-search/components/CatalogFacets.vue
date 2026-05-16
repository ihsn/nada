<template>
  <div class="catalog-facets">
    <div class="d-flex align-center justify-space-between mb-3">
      <span class="text-subtitle-2 font-weight-bold">{{ t('filter_by_type', 'Filters') }}</span>
      <v-btn v-if="hasActive" size="x-small" variant="text" color="error" @click="clearAll">
        {{ t('reset_search', 'Clear all') }}
      </v-btn>
    </div>

    <v-expansion-panels
      v-model="openPanels"
      multiple
      variant="accordion"
      class="facet-panels"
    >
      <!-- Year range -->
      <v-expansion-panel v-if="facets?.years">
        <v-expansion-panel-title class="facet-title">
          {{ t('filter_by_year', 'Year') }}
          <v-chip v-if="query.from || query.to" size="x-small" color="primary" variant="tonal" class="ms-2">
            {{ [query.from, query.to].filter(Boolean).join('–') }}
          </v-chip>
        </v-expansion-panel-title>
        <v-expansion-panel-text>
          <v-row dense>
            <v-col cols="6">
              <v-text-field
                :model-value="query.from"
                :placeholder="facets.years.min_year ? String(facets.years.min_year) : t('from')"
                density="compact" variant="outlined" hide-details type="number"
                @update:model-value="v => (query.from = v ?? '')"
                @change="emit('change')"
              />
            </v-col>
            <v-col cols="6">
              <v-text-field
                :model-value="query.to"
                :placeholder="facets.years.max_year ? String(facets.years.max_year) : t('to')"
                density="compact" variant="outlined" hide-details type="number"
                @update:model-value="v => (query.to = v ?? '')"
                @change="emit('change')"
              />
            </v-col>
          </v-row>
        </v-expansion-panel-text>
      </v-expansion-panel>

      <!-- Countries — shown when regional_search=yes -->
      <v-expansion-panel v-if="showCountries && countryItems.length">
        <v-expansion-panel-title class="facet-title">
          {{ t('filter_by_country', 'Country') }}
          <v-chip v-if="query.country" size="x-small" color="primary" variant="tonal" class="ms-2">1</v-chip>
        </v-expansion-panel-title>
        <v-expansion-panel-text>
          <v-autocomplete
            :model-value="query.country"
            :items="countryItems"
            item-title="label" item-value="value"
            density="compact" variant="outlined" hide-details clearable
            :placeholder="t('select_countries', 'Select country')"
            @update:model-value="v => onFilter('country', v)"
          />
        </v-expansion-panel-text>
      </v-expansion-panel>

      <!-- Regions -->
      <v-expansion-panel v-if="regionItems.length">
        <v-expansion-panel-title class="facet-title">
          {{ t('filter_by_region', 'Region') }}
          <v-chip v-if="query.region" size="x-small" color="primary" variant="tonal" class="ms-2">1</v-chip>
        </v-expansion-panel-title>
        <v-expansion-panel-text>
          <v-select
            :model-value="query.region"
            :items="regionItems"
            item-title="label" item-value="value"
            density="compact" variant="outlined" hide-details clearable
            :placeholder="t('filter_by_region', 'Select region')"
            @update:model-value="v => onFilter('region', v)"
          />
        </v-expansion-panel-text>
      </v-expansion-panel>

      <!-- Collections — shown when collection_search=yes -->
      <v-expansion-panel v-if="showCollections && collectionItems.length">
        <v-expansion-panel-title class="facet-title">
          {{ t('filter_by_collection', 'Collection') }}
          <v-chip v-if="query.collection" size="x-small" color="primary" variant="tonal" class="ms-2">1</v-chip>
        </v-expansion-panel-title>
        <v-expansion-panel-text>
          <v-autocomplete
            :model-value="query.collection"
            :items="collectionItems"
            item-title="label" item-value="value"
            density="compact" variant="outlined" hide-details clearable
            :placeholder="t('select_collections', 'Select collection')"
            @update:model-value="v => onFilter('collection', v)"
          />
        </v-expansion-panel-text>
      </v-expansion-panel>

      <!-- Data access type -->
      <v-expansion-panel v-if="dtypeItems.length">
        <v-expansion-panel-title class="facet-title">
          {{ t('filter_by_dtype', 'Data Access') }}
          <v-chip v-if="query.dtype" size="x-small" color="primary" variant="tonal" class="ms-2">1</v-chip>
        </v-expansion-panel-title>
        <v-expansion-panel-text>
          <v-select
            :model-value="query.dtype"
            :items="dtypeItems"
            item-title="label" item-value="value"
            density="compact" variant="outlined" hide-details clearable
            :placeholder="t('filter_by_dtype', 'Select access type')"
            @update:model-value="v => onFilter('dtype', v)"
          />
        </v-expansion-panel-text>
      </v-expansion-panel>

      <!-- Data classification -->
      <v-expansion-panel v-if="dataClassItems.length">
        <v-expansion-panel-title class="facet-title">
          {{ t('data_classification', 'Classification') }}
          <v-chip v-if="query.data_class" size="x-small" color="primary" variant="tonal" class="ms-2">1</v-chip>
        </v-expansion-panel-title>
        <v-expansion-panel-text>
          <v-select
            :model-value="query.data_class"
            :items="dataClassItems"
            item-title="label" item-value="value"
            density="compact" variant="outlined" hide-details clearable
            :placeholder="t('data_classification', 'Select classification')"
            @update:model-value="v => onFilter('data_class', v)"
          />
        </v-expansion-panel-text>
      </v-expansion-panel>

      <!-- Tags -->
      <v-expansion-panel v-if="tagItems.length">
        <v-expansion-panel-title class="facet-title">
          {{ t('filter_by_tag', 'Tag') }}
          <v-chip v-if="query.tag" size="x-small" color="primary" variant="tonal" class="ms-2">1</v-chip>
        </v-expansion-panel-title>
        <v-expansion-panel-text>
          <v-autocomplete
            :model-value="query.tag"
            :items="tagItems"
            item-title="label" item-value="value"
            density="compact" variant="outlined" hide-details clearable
            :placeholder="t('filter_by_tag', 'Select tag')"
            @update:model-value="v => onFilter('tag', v)"
          />
        </v-expansion-panel-text>
      </v-expansion-panel>

      <!-- User-defined facets -->
      <v-expansion-panel v-for="facet in userFacets" :key="facet.key">
        <v-expansion-panel-title class="facet-title">
          {{ facet.title }}
          <v-chip v-if="query[facet.key]" size="x-small" color="primary" variant="tonal" class="ms-2">1</v-chip>
        </v-expansion-panel-title>
        <v-expansion-panel-text>
          <v-autocomplete
            :model-value="query[facet.key]"
            :items="facet.items"
            item-title="label" item-value="value"
            density="compact" variant="outlined" hide-details clearable
            :placeholder="facet.title"
            @update:model-value="v => onFilter(facet.key, v)"
          />
        </v-expansion-panel-text>
      </v-expansion-panel>
    </v-expansion-panels>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import { useAppConfig } from '@/shared/composables/useAppConfig';

defineOptions({ name: 'CatalogFacets' });

const props = defineProps({
  facets: { type: Object, default: null },
  query:  { type: Object, required: true },
});
const emit = defineEmits(['change']);

const { t }          = useI18n();
const { siteConfig } = useAppConfig();

// Open all panels by default
const openPanels = ref([0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

const showCountries  = computed(() => siteConfig.value?.regional_search   === 'yes');
const showCollections = computed(() => siteConfig.value?.collection_search === 'yes');

const hasActive = computed(() =>
  ['country', 'region', 'dtype', 'data_class', 'tag', 'from', 'to', 'collection'].some(
    (k) => props.query[k] !== '' && props.query[k] != null
  ) || userFacets.value.some((f) => props.query[f.key])
);

function onFilter(key, value) {
  props.query[key] = value ?? '';
  emit('change');
}

function clearAll() {
  ['country', 'region', 'dtype', 'data_class', 'tag', 'from', 'to', 'collection'].forEach(
    (k) => { props.query[k] = ''; }
  );
  userFacets.value.forEach((f) => { props.query[f.key] = ''; });
  emit('change');
}

/** Convert a dict-keyed facet to a [{label, value}] array */
function dictToItems(dict, labelFn, valueFn) {
  if (!dict || typeof dict !== 'object') return [];
  return Object.values(dict)
    .map((item) => {
      const label = labelFn(item);
      const value = valueFn(item);
      const count = item.found != null ? ` (${item.found})` : '';
      return { label: label + count, value };
    })
    .filter((i) => i.label && i.value != null && i.value !== '');
}

const countryItems = computed(() =>
  dictToItems(props.facets?.countries, (i) => i.title, (i) => i.title)
);

const regionItems = computed(() =>
  dictToItems(props.facets?.regions, (i) => i.title, (i) => String(i.id))
);

const collectionItems = computed(() =>
  dictToItems(props.facets?.repositories, (i) => i.title, (i) => i.repositoryid)
);

const dtypeItems = computed(() =>
  dictToItems(
    props.facets?.da_types,
    (i) => t(i.title, i.code),   // title is a translation key
    (i) => i.code
  )
);

const dataClassItems = computed(() =>
  dictToItems(
    props.facets?.data_class,
    (i) => t(i.title, i.code),   // title is a translation key
    (i) => String(i.id)
  )
);

const tagItems = computed(() =>
  dictToItems(props.facets?.tags, (i) => i.title, (i) => i.title)
);

/** User-defined facets: { type:'user', title, values:{id:{id,title,found},...} } */
const USER_STANDARD_KEYS = new Set([
  'years','repositories','regions','da_types','data_class','countries','tags','types',
]);

const userFacets = computed(() => {
  if (!props.facets) return [];
  return Object.entries(props.facets)
    .filter(([, v]) => v && typeof v === 'object' && v.type === 'user')
    .map(([key, facet]) => ({
      key,
      title: facet.title || key,
      items: dictToItems(facet.values, (i) => i.title, (i) => String(i.id)),
    }))
    .filter((f) => f.items.length > 0);
});
</script>

<style scoped>
.catalog-facets {
  position: sticky;
  top: 56px;
}

.facet-panels :deep(.v-expansion-panel-title.facet-title) {
  font-size: 0.8125rem;
  font-weight: 600;
  min-height: 44px;
  padding: 8px 16px;
  color: rgba(0, 0, 0, 0.75);
}

.facet-panels :deep(.v-expansion-panel-text__wrapper) {
  padding: 0 16px 14px;
}

.facet-panels :deep(.v-expansion-panel) {
  border-radius: 0 !important;
}

.facet-panels :deep(.v-expansion-panel + .v-expansion-panel) {
  border-top: 1px solid rgba(0, 0, 0, 0.07);
}
</style>
