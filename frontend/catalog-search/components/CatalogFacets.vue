<template>
  <div class="catalog-facets">
    <!-- Year -->
    <CatalogFacetBox
      v-if="facetEnabled('year') && facets?.years"
      :title="t('filter_by_year', 'Year')"
      :selected-count="yearSelectedCount"
      @clear="clearYear"
    >
      <v-row dense>
        <v-col cols="6">
          <div class="text-caption text-medium-emphasis mb-1">{{ t('from') }}</div>
          <v-select
            :model-value="query.from || null"
            :items="yearOptions"
            density="compact"
            variant="outlined"
            hide-details
            clearable
            @update:model-value="v => onYear('from', v)"
          />
        </v-col>
        <v-col cols="6">
          <div class="text-caption text-medium-emphasis mb-1">{{ t('to') }}</div>
          <v-select
            :model-value="query.to || null"
            :items="yearOptions"
            density="compact"
            variant="outlined"
            hide-details
            clearable
            @update:model-value="v => onYear('to', v)"
          />
        </v-col>
      </v-row>
    </CatalogFacetBox>

    <!-- Country -->
    <CatalogFacetBox
      v-if="facetEnabled('country') && countryItems.length"
      :title="t('filter_by_country', 'Country')"
      :selected-count="countSelected(query.country)"
      @clear="clearFacet('country')"
    >
      <CatalogFacetCheckboxList
        filter-key="country"
        :items="countryItems"
        :model-value="query.country"
        @update:model-value="v => setFacet('country', v)"
        @change="emit('change')"
      />
    </CatalogFacetBox>

    <!-- Region -->
    <CatalogFacetBox
      v-if="facetEnabled('region') && regionItems.length"
      :title="t('filter_by_region', 'Region')"
      :selected-count="countSelected(query.region)"
      @clear="clearFacet('region')"
    >
      <CatalogFacetCheckboxList
        filter-key="region"
        :items="regionItems"
        :model-value="query.region"
        @update:model-value="v => setFacet('region', v)"
        @change="emit('change')"
      />
    </CatalogFacetBox>

    <!-- Collection (central catalog only — matches legacy Catalog.php) -->
    <CatalogFacetBox
      v-if="facetEnabled('collection') && centralCatalog && collectionItems.length"
      :title="t('filter_by_collection', 'Collection')"
      :selected-count="countSelected(query.collection)"
      @clear="clearFacet('collection')"
    >
      <CatalogFacetCheckboxList
        filter-key="collection"
        :items="collectionItems"
        :model-value="query.collection"
        @update:model-value="v => setFacet('collection', v)"
        @change="emit('change')"
      />
    </CatalogFacetBox>

    <!-- Data type (all-tab sidebar only — matches legacy Catalog.php load_facets_html) -->
    <CatalogFacetBox
      v-if="facetEnabled('type') && showTypeFacet && typeItems.length"
      :title="t('filter_by_type', 'Data type')"
      :selected-count="countSelected(query.type)"
      @clear="clearFacet('type')"
    >
      <CatalogFacetCheckboxList
        filter-key="type"
        :items="typeItems"
        :model-value="query.type"
        @update:model-value="v => setFacet('type', v)"
        @change="emit('change')"
      />
    </CatalogFacetBox>

    <!-- Data access -->
    <CatalogFacetBox
      v-if="facetEnabled('dtype') && dtypeItems.length"
      :title="t('filter_by_dtype', 'Data Access')"
      :selected-count="countSelected(query.dtype)"
      @clear="clearFacet('dtype')"
    >
      <CatalogFacetCheckboxList
        filter-key="dtype"
        :items="dtypeItems"
        :model-value="query.dtype"
        @update:model-value="v => setFacet('dtype', v)"
        @change="emit('change')"
      />
    </CatalogFacetBox>

    <!-- Classification -->
    <CatalogFacetBox
      v-if="facetEnabled('data_class') && dataClassItems.length"
      :title="t('data_classification', 'Classification')"
      :selected-count="countSelected(query.data_class)"
      @clear="clearFacet('data_class')"
    >
      <CatalogFacetCheckboxList
        filter-key="data_class"
        :items="dataClassItems"
        :model-value="query.data_class"
        @update:model-value="v => setFacet('data_class', v)"
        @change="emit('change')"
      />
    </CatalogFacetBox>

    <!-- Tags -->
    <CatalogFacetBox
      v-if="facetEnabled('tag') && tagItems.length"
      :title="t('filter_by_tag', 'Tag')"
      :selected-count="countSelected(query.tag)"
      @clear="clearFacet('tag')"
    >
      <CatalogFacetCheckboxList
        filter-key="tag"
        :items="tagItems"
        :model-value="query.tag"
        @update:model-value="v => setFacet('tag', v)"
        @change="emit('change')"
      />
    </CatalogFacetBox>

    <!-- Dataset (timeseries database) -->
    <CatalogFacetBox
      v-if="facetEnabled('database') && databaseItems.length"
      :title="t('filter_by_database', 'Dataset')"
      :selected-count="countSelected(query.database)"
      @clear="clearFacet('database')"
    >
      <CatalogFacetCheckboxList
        filter-key="database"
        :items="databaseItems"
        :model-value="query.database"
        @update:model-value="v => setFacet('database', v)"
        @change="emit('change')"
      />
    </CatalogFacetBox>

    <!-- User-defined facets -->
    <CatalogFacetBox
      v-for="facet in userFacets"
      :key="facet.key"
      :title="facet.title"
      :selected-count="countSelected(query[facet.key])"
      @clear="clearFacet(facet.key)"
    >
      <CatalogFacetCheckboxList
        :filter-key="facet.key"
        :items="facet.items"
        :model-value="query[facet.key]"
        @update:model-value="v => setFacet(facet.key, v)"
        @change="emit('change')"
      />
    </CatalogFacetBox>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import { catalogDatasetTypeLabel } from '../catalogDatasetTypeLabel';
import CatalogFacetBox from './CatalogFacetBox.vue';
import CatalogFacetCheckboxList from './CatalogFacetCheckboxList.vue';
import { normalizeYearRange } from '../catalogQuery';

defineOptions({ name: 'CatalogFacets' });

const props = defineProps({
  facets:         { type: Object, default: null },
  query:          { type: Object, required: true },
  enabledFilters: { type: Array,  default: () => [] },
  /** True on central catalog (no active repository), like legacy !active_repo_id */
  centralCatalog: { type: Boolean, default: true },
});
const emit = defineEmits(['change']);

const { t } = useI18n();

function facetEnabled(name) {
  if (!props.enabledFilters.length) return true;
  return props.enabledFilters.includes(name);
}

/** Legacy catalog shows dataset-type facet on "All" tab, or always in variable view. */
const showTypeFacet = computed(() => {
  if (props.query.view === 'v') return true;
  return !props.query.tab_type;
});

function countSelected(val) {
  if (!val) return 0;
  return String(val).split(',').map((s) => s.trim()).filter(Boolean).length;
}

const yearSelectedCount = computed(() =>
  (props.query.from ? 1 : 0) + (props.query.to ? 1 : 0)
);

const yearOptions = computed(() => {
  const min = props.facets?.years?.min_year;
  const max = props.facets?.years?.max_year;
  if (min == null || max == null) return [];
  const out = [];
  for (let y = max; y >= min; y--) {
    out.push({ title: String(y), value: String(y) });
  }
  return out;
});

function setFacet(key, value) {
  props.query[key] = value ?? '';
}

function clearFacet(key) {
  props.query[key] = '';
  emit('change');
}

function clearYear() {
  props.query.from = '';
  props.query.to   = '';
  emit('change');
}

function onYear(field, value) {
  props.query[field] = value ?? '';
  const { from, to } = normalizeYearRange(props.query.from, props.query.to);
  props.query.from = from;
  props.query.to = to;
  emit('change');
}

function dictToItems(dict, labelFn, valueFn) {
  if (!dict || typeof dict !== 'object') return [];
  return Object.entries(dict)
    .map(([key, item]) => ({
      label: labelFn(item, key),
      value: valueFn(item, key),
      count: item.found != null ? item.found : null,
      groupName: item.group_name || '',
    }))
    .filter((i) => i.label && i.value != null && i.value !== '');
}

const countryItems = computed(() =>
  dictToItems(
    props.facets?.countries,
    (i) => i.title,
    (i, key) => String(key)
  )
);

const regionItems = computed(() =>
  dictToItems(
    props.facets?.regions,
    (i) => i.title,
    (i) => String(i.id)
  )
);

const collectionItems = computed(() =>
  dictToItems(
    props.facets?.repositories,
    (i) => i.title,
    (i) => i.repositoryid
  )
);

const typeItems = computed(() =>
  dictToItems(
    props.facets?.types,
    (i, key) => catalogDatasetTypeLabel(t, key, i?.title),
    (i, key) => String(key)
  )
);

const dtypeItems = computed(() =>
  dictToItems(
    props.facets?.da_types,
    (i) => t(i.title, i.code),
    (i, key) => String(key)
  )
);

const dataClassItems = computed(() =>
  dictToItems(
    props.facets?.data_class,
    (i) => t(i.title, i.code),
    (i) => String(i.id)
  )
);

const databaseItems = computed(() =>
  dictToItems(
    props.facets?.databases,
    (i) => i.title,
    (i) => i.idno
  )
);

const tagItems = computed(() =>
  dictToItems(
    props.facets?.tags,
    (i) => i.title,
    (i) => i.title
  )
);

const userFacets = computed(() => {
  if (!props.facets) return [];
  return Object.entries(props.facets)
    .filter(([, v]) => v && typeof v === 'object' && v.type === 'user')
    .filter(([key]) => facetEnabled(key))
    .map(([key, facet]) => ({
      key,
      title: facet.title || key,
      items: dictToItems(
        facet.values,
        (i) => i.title,
        (i) => String(i.id)
      ),
    }))
    .filter((f) => f.items.length > 0);
});
</script>

<style scoped>
.catalog-facets {
  position: sticky;
  top: 56px;
}
</style>
