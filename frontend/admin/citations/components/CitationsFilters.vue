<template>
  <div class="admin-catalog-filter-stack">
    <v-card class="admin-catalog-surface" rounded="lg" elevation="1">
      <div
        class="admin-catalog-filter-card__header d-flex align-center cursor-pointer ga-2"
        @click="expanded.published = !expanded.published"
      >
        <span class="text-subtitle-2 font-weight-medium">{{ t('status', 'Status') }}</span>
        <v-spacer />
        <v-icon size="small">{{ expanded.published ? 'mdi-chevron-up' : 'mdi-chevron-down' }}</v-icon>
      </div>
      <v-expand-transition>
        <div v-show="expanded.published" class="admin-catalog-filter-card__body">
          <v-select
            v-model="selected.published"
            :items="statusItems"
            item-title="title"
            item-value="value"
            density="compact"
            variant="outlined"
            hide-details
            clearable
            @update:model-value="emitChange"
          />
        </div>
      </v-expand-transition>
    </v-card>

    <v-card
      v-for="section in listSections"
      :key="section.key"
      class="admin-catalog-surface"
      rounded="lg"
      elevation="1"
    >
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
            v-if="section.options.length > 8"
            v-model="search[section.key]"
            :placeholder="t('search', 'Search')"
            density="compact"
            variant="outlined"
            hide-details
            clearable
          />
          <div
            class="filter-options-list"
            :class="{ scrollable: section.filtered.length > 20 }"
            style="max-height: 400px; overflow-y: auto"
          >
            <div
              v-for="option in section.filtered"
              :key="`${section.key}-${option.value}`"
              class="filter-option-row"
              @click="toggle(section.key, option.value)"
            >
              <v-checkbox
                :model-value="selected[section.key].includes(option.value)"
                density="compact"
                hide-details
                class="filter-option-row__checkbox"
                :aria-label="String(option.title)"
                @click.stop
                @update:model-value="(checked) => setSelection(section.key, option.value, !!checked)"
              />
              <span class="filter-option-row__name text-truncate" :title="String(option.title)">{{ option.title }}</span>
              <span
                v-if="option.count != null"
                class="filter-option-row__count text-medium-emphasis tabular-nums text-right"
              >{{ option.count }}</span>
            </div>
          </div>
        </div>
      </v-expand-transition>
    </v-card>

    <v-card class="admin-catalog-surface" rounded="lg" elevation="1">
      <div
        class="admin-catalog-filter-card__header d-flex align-center cursor-pointer ga-2"
        @click="expanded.year = !expanded.year"
      >
        <span class="text-subtitle-2 font-weight-medium">{{ t('cit_col_year', 'Year') }}</span>
        <v-spacer />
        <v-icon size="small">{{ expanded.year ? 'mdi-chevron-up' : 'mdi-chevron-down' }}</v-icon>
      </div>
      <v-expand-transition>
        <div v-show="expanded.year" class="admin-catalog-filter-card__body">
          <v-text-field
            v-model="selected.year_from"
            :label="t('cit_year_from', 'Year from')"
            density="compact"
            variant="outlined"
            hide-details
            maxlength="4"
            inputmode="numeric"
            clearable
            @update:model-value="emitChange"
          />
          <v-text-field
            v-model="selected.year_to"
            :label="t('cit_year_to', 'Year to')"
            density="compact"
            variant="outlined"
            hide-details
            maxlength="4"
            inputmode="numeric"
            clearable
            @update:model-value="emitChange"
          />
        </div>
      </v-expand-transition>
    </v-card>

    <v-card v-if="collectionItems.length > 1" class="admin-catalog-surface" rounded="lg" elevation="1">
      <div
        class="admin-catalog-filter-card__header d-flex align-center cursor-pointer ga-2"
        @click="expanded.collection = !expanded.collection"
      >
        <span class="text-subtitle-2 font-weight-medium">{{ t('cit_collection', 'Collection') }}</span>
        <v-spacer />
        <v-icon size="small">{{ expanded.collection ? 'mdi-chevron-up' : 'mdi-chevron-down' }}</v-icon>
      </div>
      <v-expand-transition>
        <div v-show="expanded.collection" class="admin-catalog-filter-card__body">
          <v-select
            v-model="selected.collection"
            :items="collectionItems"
            item-title="title"
            item-value="value"
            density="compact"
            variant="outlined"
            hide-details
            clearable
            @update:model-value="emitChange"
          />
        </div>
      </v-expand-transition>
    </v-card>

    <v-card class="admin-catalog-surface" rounded="lg" elevation="1">
      <div
        class="admin-catalog-filter-card__header d-flex align-center cursor-pointer ga-2"
        @click="expanded.flags = !expanded.flags"
      >
        <span class="text-subtitle-2 font-weight-medium">{{ t('cit_more_filters', 'More') }}</span>
        <v-spacer />
        <v-icon size="small">{{ expanded.flags ? 'mdi-chevron-up' : 'mdi-chevron-down' }}</v-icon>
      </div>
      <v-expand-transition>
        <div v-show="expanded.flags" class="admin-catalog-filter-card__body">
          <div class="filter-options-list">
            <div class="filter-option-row" @click="setFlag('has_notes', !selected.has_notes)">
              <v-checkbox
                :model-value="selected.has_notes"
                density="compact"
                hide-details
                class="filter-option-row__checkbox"
                :aria-label="notesLabel"
                @click.stop
                @update:model-value="(checked) => setFlag('has_notes', !!checked)"
              />
              <span class="filter-option-row__name text-truncate" :title="notesLabel">{{ notesLabel }}</span>
              <span
                v-if="facets && facets.has_notes != null"
                class="filter-option-row__count text-medium-emphasis tabular-nums text-right"
              >{{ facets.has_notes }}</span>
            </div>
            <div class="filter-option-row" @click="setFlag('no_studies', !selected.no_studies)">
              <v-checkbox
                :model-value="selected.no_studies"
                density="compact"
                hide-details
                class="filter-option-row__checkbox"
                :aria-label="noStudiesLabel"
                @click.stop
                @update:model-value="(checked) => setFlag('no_studies', !!checked)"
              />
              <span class="filter-option-row__name text-truncate" :title="noStudiesLabel">{{ noStudiesLabel }}</span>
              <span
                v-if="facets && facets.no_studies != null"
                class="filter-option-row__count text-medium-emphasis tabular-nums text-right"
              >{{ facets.no_studies }}</span>
            </div>
          </div>
        </div>
      </v-expand-transition>
    </v-card>

    <v-btn block color="primary" variant="elevated" rounded="lg" @click="emitChange">
      {{ t('apply_filters', 'Apply filters') }}
    </v-btn>
    <v-btn v-if="hasActive" block variant="text" color="primary" rounded="lg" @click="emit('clear')">
      {{ t('cit_clear_filters', 'Clear filters') }}
    </v-btn>
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import { hasFilters } from '../utils';

defineOptions({ name: 'CitationsFilters' });

const props = defineProps({
  filters: { type: Object, required: true },
  facets: { type: Object, default: null },
  options: { type: Object, default: null },
});

const emit = defineEmits(['filter-change', 'clear']);
const { t } = useI18n();

const expanded = reactive({
  published: true,
  ctype: true,
  flag: true,
  user: false,
  url_status: false,
  year: true,
  collection: false,
  flags: true,
});

const selected = reactive({
  published: '',
  ctype: [],
  flag: [],
  user: [],
  url_status: [],
  year_from: '',
  year_to: '',
  collection: '',
  has_notes: false,
  no_studies: false,
});

const search = reactive({
  ctype: '',
  flag: '',
  user: '',
  url_status: '',
});

const hasActive = computed(() => hasFilters({ ...selected, keywords: '' }));

function countOf(list, value) {
  const hit = (list || []).find((x) => String(x.value) === String(value));
  return hit ? hit.count : null;
}

function typeLabel(value) {
  const hit = (props.options?.ctypes || []).find((c) => c.value === value);
  return hit ? hit.label : t(value, value);
}

const statusItems = computed(() => [
  { title: t('cit_all', 'All'), value: '' },
  {
    title: props.facets
      ? `${t('published-status-1', 'Published')} (${countOf(props.facets.published, 1) ?? 0})`
      : t('published-status-1', 'Published'),
    value: '1',
  },
  {
    title: props.facets
      ? `${t('published-status-0', 'Draft')} (${countOf(props.facets.published, 0) ?? 0})`
      : t('published-status-0', 'Draft'),
    value: '0',
  },
]);

const typeOptions = computed(() => {
  const known = (props.options?.ctypes || []).map((c) => c.value);
  const inUse = (props.facets?.ctype || []).map((c) => c.value).filter((v) => v && !known.includes(v));
  return [...known, ...inUse].map((v) => ({
    title: typeLabel(v),
    value: v,
    count: props.facets ? countOf(props.facets.ctype, v) : null,
  }));
});

const flagOptions = computed(() => {
  const items = (props.options?.flags || []).map((f) => ({
    title: f.label,
    value: f.code,
    count: f.count,
  }));
  (props.facets?.flag || []).forEach((f) => {
    if (!f.known && !items.some((i) => i.value === f.value)) {
      items.push({ title: f.value, value: f.value, count: f.count });
    }
  });
  return items;
});

const userOptions = computed(() =>
  (props.options?.users || []).map((u) => ({
    title: u.username,
    value: u.id,
    count: countOf(props.facets?.user, u.id),
  }))
);

const collectionItems = computed(() =>
  (props.options?.collections || []).map((c) => ({ title: c.title, value: c.id }))
);

const notesLabel = computed(() => t('cit_has_notes', 'Has notes'));
const noStudiesLabel = computed(() => t('cit_no_studies_filter', 'Has no studies'));

function filtered(options, query) {
  const q = String(query || '').trim().toLowerCase();
  if (!q) return options;
  return options.filter((o) => String(o.title).toLowerCase().includes(q));
}

const listSections = computed(() => [
  {
    key: 'ctype',
    title: t('citation_type', 'Citation type'),
    options: typeOptions.value,
    filtered: filtered(typeOptions.value, search.ctype),
  },
  {
    key: 'flag',
    title: t('Flags', 'Flags'),
    options: flagOptions.value,
    filtered: filtered(flagOptions.value, search.flag),
  },
  {
    key: 'user',
    title: t('cit_updated_by', 'Updated by'),
    options: userOptions.value,
    filtered: filtered(userOptions.value, search.user),
  },
]);

function setSelection(key, value, checked) {
  const arr = selected[key];
  const idx = arr.indexOf(value);
  if (checked && idx === -1) arr.push(value);
  if (!checked && idx !== -1) arr.splice(idx, 1);
  emitChange();
}

function setFlag(key, checked) {
  selected[key] = checked;
  emitChange();
}

function toggle(key, value) {
  setSelection(key, value, !selected[key].includes(value));
}

function emitChange() {
  emit('filter-change', {
    published: selected.published ?? '',
    ctype: [...selected.ctype],
    flag: [...selected.flag],
    user: [...selected.user],
    url_status: [...selected.url_status],
    year_from: selected.year_from || '',
    year_to: selected.year_to || '',
    collection: selected.collection || '',
    has_notes: !!selected.has_notes,
    no_studies: !!selected.no_studies,
  });
}

watch(
  () => props.filters,
  (f) => {
    if (!f || typeof f !== 'object') return;
    selected.published = f.published ?? '';
    selected.ctype = Array.isArray(f.ctype) ? [...f.ctype] : [];
    selected.flag = Array.isArray(f.flag) ? [...f.flag] : [];
    selected.user = Array.isArray(f.user) ? [...f.user] : [];
    selected.url_status = Array.isArray(f.url_status) ? [...f.url_status] : [];
    selected.year_from = f.year_from || '';
    selected.year_to = f.year_to || '';
    selected.collection = f.collection || '';
    selected.has_notes = !!f.has_notes;
    selected.no_studies = !!f.no_studies;
  },
  { immediate: true, deep: true }
);
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
