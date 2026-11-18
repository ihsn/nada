<template>
  <div class="cit-stack">
    <v-breadcrumbs :items="breadcrumbItems" class="cit-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <v-row align="center" class="mb-1">
      <v-col cols="12" class="pa-0">
        <div class="cit-page-header__inner">
          <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-0 cit-page-header__title">
            {{ t('title_citations', 'Citations') }}
          </h1>
          <div class="d-flex flex-wrap ga-2 cit-page-header__actions">
            <v-btn v-if="can.create" color="primary" size="small" prepend-icon="mdi-plus" :to="{ name: 'citation-create' }">
              {{ t('add_new_citation', 'Add new citation') }}
            </v-btn>
            <v-btn v-if="can.create" variant="outlined" size="small" prepend-icon="mdi-file-import" :to="{ name: 'citations-import' }">
              {{ t('import_citation', 'Import citations') }}
            </v-btn>
            <v-btn variant="outlined" size="small" prepend-icon="mdi-download" :to="{ name: 'citations-export', query: exportQuery }">
              {{ t('export_citations', 'Export citations') }}
            </v-btn>
          </div>
        </div>
      </v-col>
    </v-row>

    <v-alert v-if="loadError" type="error" density="compact">{{ loadError }}</v-alert>

    <v-row>
      <v-col cols="12" md="3" class="admin-catalog-filters-column">
        <CitationsFilters
          :filters="filters"
          :facets="facets"
          :options="options"
          @filter-change="onSidebarFilters"
          @clear="clearFilters"
        />
      </v-col>

      <v-col cols="12" md="9" class="admin-catalog-main-column">
        <v-card class="admin-catalog-surface" rounded="lg" elevation="1">
          <div class="admin-catalog-search-inner">
            <v-text-field
              v-model="filters.keywords"
              density="comfortable"
              variant="outlined"
              hide-details
              clearable
              maxlength="200"
              prepend-inner-icon="mdi-magnify"
              class="admin-catalog-search-field"
              :placeholder="t('cit_search_hint', 'Title, author, journal, DOI, keywords, notes')"
              :disabled="loading"
              @keydown.enter.prevent="applyFilters"
              @click:clear="onClearKeywords"
            />
          </div>
        </v-card>

        <div v-if="filterChips.length" class="admin-catalog-filter-chips filter-chips">
          <v-chip v-for="chip in filterChips" :key="chip.key" closable @click:close="removeChip(chip)">
            {{ chip.label }}
          </v-chip>
        </div>

        <v-card class="admin-catalog-results-card admin-catalog-surface" rounded="lg" elevation="1">
      <div class="catalog-results-toolbar catalog-results-toolbar--padded">
        <v-row class="mb-0 align-center">
          <v-col cols="12" sm="4" class="d-flex align-center ga-1 flex-wrap">
            <v-checkbox
              v-model="selectAll"
              :indeterminate="isIndeterminate"
              hide-details
              density="compact"
              class="ma-0 pa-0 cit-select-checkbox"
              :aria-label="t('select_all', 'Select all')"
            />
            <v-menu>
              <template #activator="{ props: menuProps }">
                <v-btn
                  color="primary"
                  variant="text"
                  icon
                  v-bind="menuProps"
                  :loading="busy"
                  :title="t('batch_actions', 'Batch actions')"
                >
                  <v-icon>mdi-dots-vertical</v-icon>
                </v-btn>
              </template>
              <v-list density="compact" class="menu-list-compact" min-width="220">
                <template v-if="can.edit">
                  <v-list-item :disabled="busy || tooMany" @click="runBatch('publish')">
                    <template #prepend><v-icon size="small">mdi-check-circle</v-icon></template>
                    <v-list-item-title class="text-caption">{{ t('option_publish', 'Publish') }}</v-list-item-title>
                  </v-list-item>
                  <v-list-item :disabled="busy || tooMany" @click="runBatch('unpublish')">
                    <template #prepend><v-icon size="small">mdi-cancel</v-icon></template>
                    <v-list-item-title class="text-caption">{{ t('cit_unpublish', 'Unpublish') }}</v-list-item-title>
                  </v-list-item>
                  <v-list-item :disabled="busy || tooMany">
                    <template #prepend><v-icon size="small">mdi-tag</v-icon></template>
                    <v-list-item-title class="text-caption">{{ t('flag_entry_as', 'Set flag') }}</v-list-item-title>
                    <template #append><v-icon size="small">mdi-chevron-right</v-icon></template>
                    <v-menu activator="parent" location="end" open-on-hover :close-on-content-click="true">
                      <v-list density="compact" class="menu-list-compact" min-width="200">
                        <v-list-item
                          v-for="f in flags"
                          :key="f.code"
                          @click="runBatch('set_flag', f.code)"
                        >
                          <v-list-item-title class="text-caption">{{ f.label }}</v-list-item-title>
                        </v-list-item>
                        <v-divider />
                        <v-list-item @click="runBatch('set_flag', '')">
                          <v-list-item-title class="text-caption">{{ t('cit_clear_flag', 'Clear the flag') }}</v-list-item-title>
                        </v-list-item>
                      </v-list>
                    </v-menu>
                  </v-list-item>
                </template>
                <v-list-item
                  :disabled="!selected.length"
                  :to="selected.length ? { name: 'citations-export', query: { ids: selected.join(',') } } : undefined"
                >
                  <template #prepend><v-icon size="small">mdi-download</v-icon></template>
                  <v-list-item-title class="text-caption">{{ t('cit_export_selected', 'Export selected') }}</v-list-item-title>
                </v-list-item>
                <v-list-item
                  v-if="can.delete"
                  :disabled="busy || tooMany"
                  @click="runBatch('delete')"
                >
                  <template #prepend><v-icon size="small" color="error">mdi-trash-can</v-icon></template>
                  <v-list-item-title class="text-caption">{{ t('delete', 'Delete') }}</v-list-item-title>
                </v-list-item>
                <v-divider />
                <v-list-item :disabled="!selected.length || busy" @click="selected = []">
                  <template #prepend><v-icon size="small">mdi-close</v-icon></template>
                  <v-list-item-title class="text-caption">{{ t('clear_selection', 'Clear selection') }}</v-list-item-title>
                </v-list-item>
              </v-list>
            </v-menu>
            <span v-if="selected.length" class="text-body-2 text-medium-emphasis">
              {{ t('cit_n_selected', '%d selected', selected.length) }}
            </span>
            <v-alert v-if="tooMany" type="warning" density="compact" class="py-0 mb-0">
              {{ t('cit_err_too_many', 'Too many citations selected (500 at most).') }}
            </v-alert>
          </v-col>
          <v-col cols="12" sm="8" class="d-flex align-center justify-end ga-3 flex-wrap text-body-2">
            <span>
              {{
                t(
                  'cit_showing_range',
                  'Showing %s - %s of %s citations',
                  showingFrom,
                  showingTo,
                  total
                )
              }}
            </span>
            <span class="d-inline-flex align-center ga-1">
              <a
                href="#"
                class="cit-page-link"
                :class="{ 'cit-page-link--disabled': page <= 1 || loading }"
                @click.prevent="page > 1 && !loading && goPage(page - 1)"
              >{{ t('prev', 'Prev') }}</a>
              <span class="text-medium-emphasis">|</span>
              <a
                href="#"
                class="cit-page-link"
                :class="{ 'cit-page-link--disabled': page >= totalPages || loading }"
                @click.prevent="page < totalPages && !loading && goPage(page + 1)"
              >{{ t('next', 'Next') }}</a>
            </span>
          </v-col>
        </v-row>
      </div>

      <v-data-table-server
        v-model:page="page"
        v-model:items-per-page="pageSize"
        v-model:sort-by="sortBy"
        :headers="headers"
        :items="rows"
        :items-length="total"
        :loading="loading"
        :items-per-page-options="[25, 50, 100, 300]"
        item-value="id"
        density="comfortable"
        class="cit-results-table"
        @update:options="load"
        @update:sort-by="sortTouched = true"
      >
        <template #item.select="{ item }">
          <v-checkbox
            :model-value="selected.includes(item.id)"
            hide-details
            density="compact"
            class="ma-0 pa-0 cit-select-checkbox"
            :aria-label="t('select', 'Select')"
            @update:model-value="(checked) => toggleRow(item.id, !!checked)"
            @click.stop
          />
        </template>
        <template #item.title="{ item }">
          <router-link :to="{ name: 'citation-edit', params: { id: item.id } }" class="cit-title-link">{{ item.title }}</router-link>
          <div v-if="item.authors_text" class="cit-muted">{{ item.authors_text }}</div>
          <div v-if="item.container_title" class="cit-muted font-italic">{{ item.container_title }}</div>
          <div class="d-flex align-center ga-1 mt-1">
            <v-chip v-if="item.flag" size="x-small" color="orange-darken-2" variant="tonal" prepend-icon="mdi-tag">
              {{ flagLabel(item.flag) }}
            </v-chip>
            <v-tooltip v-if="item.has_notes" location="top" max-width="360">
              <template #activator="{ props: tipProps }">
                <v-icon v-bind="tipProps" icon="mdi-comment-text-outline" size="16" />
              </template>
              {{ item.notes_excerpt }}
            </v-tooltip>
            <v-icon v-if="item.has_attachment" icon="mdi-paperclip" size="16" :title="t('attach_file', 'Attach file')" />
            <v-icon
              v-if="item.url_status === 0 || item.url_status === 2"
              icon="mdi-link-off"
              size="16"
              color="warning"
              :title="linkLabel(item.url_status)"
            />
          </div>
        </template>
        <template #item.ctype="{ item }">
          <v-chip size="x-small" variant="tonal">{{ typeLabel(item.ctype) }}</v-chip>
        </template>
        <template #item.pub_year="{ item }">{{ item.pub_year || '' }}</template>
        <template #item.survey_count="{ item }">
          <span
            :title="item.survey_count ? t('icon_related_study', 'Related studies') : t('cit_no_studies', 'No studies linked')"
          >{{ item.survey_count }}</span>
        </template>
        <template #item.published="{ item }">
          <v-chip
            size="x-small"
            :color="item.published ? 'success' : 'grey'"
            variant="flat"
            :prepend-icon="item.published ? 'mdi-check-circle' : 'mdi-minus-circle'"
            class="cit-status-chip"
            :class="{ 'cursor-pointer': can.edit }"
            :title="can.edit ? t('cit_toggle_publish', 'Click to change') : ''"
            @click="can.edit && togglePublished(item)"
          >
            {{ item.published ? t('published-status-1', 'Published') : t('published-status-0', 'Draft') }}
          </v-chip>
        </template>
        <template #item.changed="{ item }">
          <div class="cit-changed">{{ formatDate(item.changed) }}</div>
          <div v-if="item.changed_by_user" class="cit-muted cit-changed">{{ item.changed_by_user }}</div>
        </template>
        <template #item.actions="{ item }">
          <div class="d-flex justify-end">
            <v-menu location="bottom end">
              <template #activator="{ props: menuProps }">
                <v-btn
                  v-bind="menuProps"
                  icon="mdi-dots-vertical"
                  size="small"
                  variant="text"
                  :title="t('actions', 'Actions')"
                />
              </template>
              <v-list density="compact" min-width="180">
                <v-list-item
                  prepend-icon="mdi-pencil"
                  :title="t('edit', 'Edit')"
                  :to="{ name: 'citation-edit', params: { id: item.id } }"
                />
                <v-list-item
                  v-if="item.published"
                  prepend-icon="mdi-open-in-new"
                  :title="t('cit_view_public', 'View on the site')"
                  :href="`${publicBase}${item.id}`"
                  target="_blank"
                  rel="noopener"
                />
                <v-list-item
                  v-if="can.delete"
                  prepend-icon="mdi-delete"
                  :title="t('delete', 'Delete')"
                  base-color="error"
                  @click="deleteOne(item)"
                />
              </v-list>
            </v-menu>
          </div>
        </template>
        <template #no-data>
          <div class="py-6 text-medium-emphasis">{{ t('no_records_found', 'No records found') }}</div>
        </template>
      </v-data-table-server>
        </v-card>
      </v-col>
    </v-row>

    <v-snackbar v-model="toast.open" :color="toast.color" location="bottom right" :timeout="5000">
      {{ toast.message }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from '@/shared/composables/useI18n';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import $dialog from '@/shared/composables/dialog';
import CitationsFilters from '../components/CitationsFilters.vue';
import { useCitationsApi, apiErrorMessage, apiFieldErrors, formatDate } from '../composables/useCitationsApi';
import { emptyFilters, filtersFromQuery, filtersToParams } from '../utils';

defineOptions({ name: 'CitationsListPage' });

const SORT_KEYS = ['title', 'ctype', 'pub_year', 'published', 'changed'];

const { t } = useI18n();
const { siteUrl, config } = useAppConfig();
const api = useCitationsApi();
const route = useRoute();
const router = useRouter();

const siteBase = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const publicBase = computed(() => String(config.value?.publicCitationBaseUrl || `${siteBase.value}/citations/`));
const breadcrumbItems = computed(() => [
  { title: t('home', 'Home'), href: `${siteBase.value}/admin` },
  { title: t('title_citations', 'Citations'), disabled: true },
]);

const filters = reactive(filtersFromQuery(route.query));
const appliedFilters = ref(filtersFromQuery(route.query));
const page = ref(Math.max(1, Number(route.query.page) || 1));
const pageSize = ref([25, 50, 100, 300].includes(Number(route.query.ps)) ? Number(route.query.ps) : 25);
const sortTouched = ref(SORT_KEYS.includes(String(route.query.sort_by)));
const sortBy = ref([
  {
    key: SORT_KEYS.includes(String(route.query.sort_by)) ? String(route.query.sort_by) : 'changed',
    order: route.query.sort_order === 'asc' ? 'asc' : 'desc',
  },
]);

const rows = ref([]);
const total = ref(0);
const facets = ref(null);
const options = ref(null);
const loading = ref(false);
const busy = ref(false);
const loadError = ref('');
const selected = ref([]);
const toast = ref({ open: false, message: '', color: 'success' });
let needFacets = true;

const can = computed(() => options.value?.can || { create: false, edit: false, delete: false });
const flags = computed(() => options.value?.flags || []);
const tooMany = computed(() => selected.value.length > 500);
const exportQuery = computed(() => filtersToParams(appliedFilters.value));
const showingFrom = computed(() => (total.value === 0 ? 0 : (page.value - 1) * pageSize.value + 1));
const showingTo = computed(() => Math.min(page.value * pageSize.value, total.value));
const totalPages = computed(() => Math.max(1, Math.ceil(total.value / pageSize.value) || 1));
const pageIds = computed(() => rows.value.map((r) => r.id));
const selectAll = computed({
  get: () => pageIds.value.length > 0 && pageIds.value.every((id) => selected.value.includes(id)),
  set: (val) => {
    selected.value = val ? [...pageIds.value] : [];
  },
});
const isIndeterminate = computed(() => {
  const n = pageIds.value.filter((id) => selected.value.includes(id)).length;
  return n > 0 && n < pageIds.value.length;
});

function goPage(next) {
  const p = Math.max(1, Math.min(totalPages.value, Number(next) || 1));
  if (p === page.value) return;
  page.value = p;
  load();
}

const headers = computed(() => [
  { title: '', key: 'select', sortable: false, width: '48px' },
  { title: t('title', 'Title'), key: 'title', sortable: true },
  { title: t('citation_type', 'Citation type'), key: 'ctype', sortable: true, width: '150px' },
  { title: t('cit_col_year', 'Year'), key: 'pub_year', sortable: true, width: '80px' },
  { title: t('cit_studies', 'Studies'), key: 'survey_count', sortable: false, width: '90px', align: 'center' },
  { title: t('status', 'Status'), key: 'published', sortable: true, width: '120px' },
  { title: t('cit_col_changed', 'Changed'), key: 'changed', sortable: true, width: '150px' },
  { title: t('actions', 'Actions'), key: 'actions', sortable: false, width: '60px', align: 'end' },
]);

function toggleRow(id, checked) {
  const idx = selected.value.indexOf(id);
  if (checked && idx === -1) selected.value = [...selected.value, id];
  if (!checked && idx !== -1) selected.value = selected.value.filter((x) => x !== id);
}

function typeLabel(value) {
  const hit = (options.value?.ctypes || []).find((c) => c.value === value);
  return hit ? hit.label : t(value, value);
}
function flagLabel(code) {
  const hit = flags.value.find((f) => f.code === code);
  return hit ? hit.label : code;
}
function linkLabel(code) {
  const hit = (options.value?.url_status || []).find((s) => s.code === code);
  return hit ? hit.label : '';
}
function userLabel(id) {
  const hit = (options.value?.users || []).find((u) => u.id === id);
  return hit ? hit.username : String(id);
}
function collectionLabel(id) {
  const hit = (options.value?.collections || []).find((c) => c.id === id);
  return hit ? hit.title : String(id);
}
function showToast(message, color = 'success') {
  toast.value = { open: true, message, color };
}

const filterChips = computed(() => {
  const f = appliedFilters.value;
  const chips = [];
  if (f.keywords) chips.push({ key: 'keywords', label: f.keywords, clear: () => { filters.keywords = ''; } });
  if (f.published === '0' || f.published === '1') {
    chips.push({
      key: 'published',
      label: f.published === '1' ? t('published-status-1', 'Published') : t('published-status-0', 'Draft'),
      clear: () => { filters.published = ''; },
    });
  }
  (f.ctype || []).forEach((v) => chips.push({ key: `ctype-${v}`, label: typeLabel(v), clear: () => { filters.ctype = filters.ctype.filter((x) => x !== v); } }));
  (f.flag || []).forEach((v) => chips.push({ key: `flag-${v}`, label: flagLabel(v), clear: () => { filters.flag = filters.flag.filter((x) => x !== v); } }));
  (f.user || []).forEach((v) => chips.push({ key: `user-${v}`, label: userLabel(v), clear: () => { filters.user = filters.user.filter((x) => x !== v); } }));
  (f.url_status || []).forEach((v) => chips.push({ key: `url-${v}`, label: linkLabel(v), clear: () => { filters.url_status = filters.url_status.filter((x) => x !== v); } }));
  if (f.year_from) chips.push({ key: 'year_from', label: `${t('cit_year_from', 'Year from')}: ${f.year_from}`, clear: () => { filters.year_from = ''; } });
  if (f.year_to) chips.push({ key: 'year_to', label: `${t('cit_year_to', 'Year to')}: ${f.year_to}`, clear: () => { filters.year_to = ''; } });
  if (f.collection) chips.push({ key: 'collection', label: collectionLabel(f.collection), clear: () => { filters.collection = ''; } });
  if (f.has_notes) chips.push({ key: 'has_notes', label: t('cit_has_notes', 'Has notes'), clear: () => { filters.has_notes = false; } });
  if (f.no_studies) chips.push({ key: 'no_studies', label: t('cit_no_studies_filter', 'Has no studies'), clear: () => { filters.no_studies = false; } });
  return chips;
});

function removeChip(chip) {
  chip.clear();
  applyFilters();
}

function onSidebarFilters(next) {
  Object.assign(filters, next);
  applyFilters();
}

function onClearKeywords() {
  filters.keywords = '';
  applyFilters();
}

function syncQuery() {
  const query = filtersToParams(appliedFilters.value);
  if (page.value > 1) query.page = String(page.value);
  if (pageSize.value !== 25) query.ps = String(pageSize.value);
  const sort = sortBy.value[0];
  if (sortTouched.value && sort) {
    query.sort_by = sort.key;
    query.sort_order = sort.order === 'asc' ? 'asc' : 'desc';
  }
  router.replace({ query });
}

async function load() {
  loading.value = true;
  loadError.value = '';
  try {
    if (!options.value) options.value = await api.options();
    const params = { page: page.value, ps: pageSize.value, ...filtersToParams(appliedFilters.value) };
    const sort = sortBy.value[0];
    // without a sort the server ranks keyword searches by relevance and lists everything else newest first
    if (sort && (sortTouched.value || !appliedFilters.value.keywords)) {
      params.sort_by = sort.key;
      params.sort_order = sort.order === 'asc' ? 'asc' : 'desc';
    }
    if (needFacets) params.with_facets = 1;
    const data = await api.list(params);
    rows.value = data.result?.rows || [];
    total.value = data.result?.total || 0;
    if (data.facets) {
      facets.value = data.facets;
      needFacets = false;
    }
    syncQuery();
  } catch (e) {
    rows.value = [];
    total.value = 0;
    const fields = apiFieldErrors(e, t);
    loadError.value = fields.keywords || apiErrorMessage(e, t);
  } finally {
    loading.value = false;
  }
}

function applyFilters() {
  appliedFilters.value = { ...filters, keywords: (filters.keywords || '').trim() };
  if (page.value !== 1) page.value = 1;
  else load();
}

function clearFilters() {
  Object.assign(filters, emptyFilters());
  applyFilters();
}

function refreshAfterChange() {
  needFacets = true;
  selected.value = [];
  return load();
}

async function togglePublished(item) {
  const next = item.published ? 0 : 1;
  const before = item.published;
  item.published = next;
  try {
    await api.publish(item.id, next);
    needFacets = true;
    api.list({ page: 1, ps: 1, with_facets: 1 }).then((d) => {
      if (d.facets) facets.value = d.facets;
      needFacets = false;
    });
  } catch (e) {
    item.published = before;
    showToast(apiErrorMessage(e, t), 'error');
  }
}

async function deleteOne(item) {
  const ok = await $dialog.confirm({
    title: t('delete', 'Delete'),
    message: t('cit_confirm_delete_one', 'Delete the citation "%s"? This cannot be undone.', item.title),
    confirmText: t('delete', 'Delete'),
    cancelText: t('cancel', 'Cancel'),
  });
  if (!ok) return;
  try {
    await api.remove(item.id);
    showToast(t('cit_deleted', 'Citation deleted.'));
    await refreshAfterChange();
  } catch (e) {
    showToast(apiErrorMessage(e, t), 'error');
  }
}

function batchConfirmation(action, count, flag) {
  switch (action) {
    case 'publish':
      return {
        title: t('option_publish', 'Publish'),
        message: t('cit_confirm_publish', 'Publish %d citation(s)? They will show on the site.', count),
        confirmText: t('option_publish', 'Publish'),
      };
    case 'unpublish':
      return {
        title: t('cit_unpublish', 'Unpublish'),
        message: t('cit_confirm_unpublish', 'Unpublish %d citation(s)? They will no longer show on the site.', count),
        confirmText: t('cit_unpublish', 'Unpublish'),
      };
    case 'set_flag': {
      const label = flag === '' ? t('cit_clear_flag', 'Clear the flag') : flagLabel(flag);
      return {
        title: t('flag_entry_as', 'Set flag'),
        message:
          flag === ''
            ? t('cit_confirm_clear_flag', 'Clear the flag on %d citation(s)?', count)
            : t('cit_confirm_set_flag', 'Set the flag "%s" on %d citation(s)?', label, count),
        confirmText: t('flag_entry_as', 'Set flag'),
      };
    }
    case 'delete':
    default:
      return {
        title: t('delete', 'Delete'),
        message: t('cit_confirm_delete_many', 'Delete %d citation(s)? This cannot be undone.', count),
        confirmText: t('delete', 'Delete'),
      };
  }
}

async function runBatch(action, flag) {
  if (!selected.value.length) {
    await $dialog.alert({
      title: t('no_selection', 'No selection'),
      message: t('cit_select_at_least_one', 'Select at least one citation.'),
      buttonText: t('ok', 'OK'),
    });
    return;
  }
  if (tooMany.value) return;
  const n = selected.value.length;
  const ok = await $dialog.confirm({
    ...batchConfirmation(action, n, flag),
    cancelText: t('cancel', 'Cancel'),
  });
  if (!ok) return;
  busy.value = true;
  try {
    const body = { action, ids: selected.value };
    if (action === 'set_flag') body.flag = flag;
    const res = await api.batch(body);
    const failed = (res.failed || []).length;
    showToast(
      failed
        ? t('cit_batch_partial', '%d updated, %d could not be changed.', res.affected, failed)
        : t('cit_batch_done', '%d citation(s) updated.', res.affected),
      failed ? 'warning' : 'success'
    );
    await refreshAfterChange();
  } catch (e) {
    showToast(apiErrorMessage(e, t), 'error');
  } finally {
    busy.value = false;
  }
}

onMounted(() => {
  if (route.query.notice === 'deleted') {
    showToast(t('cit_deleted', 'Citation deleted.'));
    const query = { ...route.query };
    delete query.notice;
    router.replace({ query });
  }
});
</script>

<style scoped>
.cit-page-header__inner {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: center;
  gap: 12px;
  width: 100%;
}

.cit-page-header__title {
  min-width: 0;
}

.filter-chips :deep(.v-chip__close) {
  margin-left: 6px;
}

.menu-list-compact :deep(.v-list-item) {
  min-height: 32px;
  padding-top: 2px;
  padding-bottom: 2px;
}

.cit-page-link {
  color: rgb(var(--v-theme-primary));
  text-decoration: none;
  font-weight: 500;
}

.cit-page-link:hover {
  text-decoration: underline;
}

.cit-page-link--disabled {
  color: rgba(0, 0, 0, 0.38);
  pointer-events: none;
  text-decoration: none;
}

.cit-status-chip {
  font-size: 0.75rem !important;
  height: 22px !important;
}

.cit-changed {
  font-size: 0.75rem;
  line-height: 1.35;
}

.cit-results-table :deep(.v-table__wrapper > table > tbody > tr > td) {
  vertical-align: top !important;
  height: auto !important;
  padding: 1rem 1rem !important;
}

.cit-results-table :deep(.v-table__wrapper > table > tbody > tr > td:first-child),
.cit-results-table :deep(.v-table__wrapper > table > thead > tr > th:first-child) {
  width: 48px;
  padding-left: 0.5rem !important;
  padding-right: 0.25rem !important;
}

.cit-select-checkbox :deep(.v-selection-control) {
  min-height: 0;
  height: auto;
  align-items: flex-start;
}

.cit-select-checkbox :deep(.v-selection-control__wrapper),
.cit-select-checkbox :deep(.v-selection-control__input) {
  width: 24px;
  height: 24px;
}

@media (max-width: 599px) {
  .cit-page-header__inner {
    grid-template-columns: 1fr;
    justify-items: start;
  }

  .cit-page-header__actions {
    justify-self: end;
  }
}
</style>
