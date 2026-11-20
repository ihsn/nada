<template>
  <v-card>
    <v-card-title class="d-flex flex-wrap align-center ga-2">
      <span>Items</span>
      <v-chip v-if="serverTotal >= 0" size="small" variant="tonal" class="font-weight-medium">
        {{ serverTotal }} total
      </v-chip>
      <v-spacer />
      <v-text-field
        v-model="search"
        density="compact"
        variant="outlined"
        hide-details
        clearable
        placeholder="Search code or title"
        prepend-inner-icon="mdi-magnify"
        class="cl-items-search"
        style="max-width: 280px"
        @click:clear="clearSearch"
      />
      <v-btn
        size="small"
        variant="outlined"
        :disabled="readOnly || !items.length"
        title="Make every row on this page editable"
        @click="editAllOnPage"
      >
        <v-icon size="small" icon="mdi-table-edit" />
        <span class="text-caption">Edit page</span>
      </v-btn>
      <v-menu location="bottom end">
        <template #activator="{ props: menuProps }">
          <v-btn size="small" variant="outlined" v-bind="menuProps" :disabled="readOnly || !serverTotal">
            <v-icon size="small" icon="mdi-sort" />
            <span class="text-caption">Auto sort</span>
            <v-icon size="small" icon="mdi-menu-down" />
          </v-btn>
        </template>
        <v-list density="compact">
          <v-list-subheader>Renumber sort order of all items by…</v-list-subheader>
          <v-list-item
            v-for="opt in resortOptions"
            :key="opt.label"
            :prepend-icon="opt.icon"
            :title="opt.label"
            @click="openResort(opt)"
          />
        </v-list>
      </v-menu>
      <v-menu location="bottom end">
        <template #activator="{ props: menuProps }">
          <v-btn size="small" variant="outlined" v-bind="menuProps">
            <v-icon size="small" icon="mdi-file-delimited-outline" />
            <span class="text-caption">CSV</span>
            <v-icon size="small" icon="mdi-menu-down" />
          </v-btn>
        </template>
        <v-list density="compact">
          <v-list-item prepend-icon="mdi-download" title="Export CSV" :disabled="exporting" @click="exportCsv" />
          <v-list-item prepend-icon="mdi-upload" title="Import CSV" :disabled="readOnly" @click="importDialog = true" />
        </v-list>
      </v-menu>
      <v-btn size="small" color="primary" variant="outlined" :disabled="readOnly" @click="openCreateItem">
        <v-icon size="small" icon="mdi-plus" />
        <span class="text-caption">Add item</span>
      </v-btn>
    </v-card-title>

    <v-expand-transition>
      <div v-if="draftCount" class="edit-bar d-flex flex-wrap align-center ga-2 px-4 py-2">
        <v-icon size="small" icon="mdi-pencil" />
        <span class="text-body-2">
          <strong>{{ dirtyRows.length }}</strong> of {{ draftCount }} edited row{{ draftCount === 1 ? '' : 's' }} changed
          <span class="text-medium-emphasis">· Enter saves, Esc undoes a row</span>
        </span>
        <v-spacer />
        <v-btn size="small" variant="text" :disabled="saving" @click="discardAll">Discard</v-btn>
        <v-btn
          size="small"
          color="primary"
          variant="flat"
          :loading="saving"
          :disabled="readOnly || !dirtyRows.length"
          @click="saveAll"
        >Save {{ dirtyRows.length || '' }} change{{ dirtyRows.length === 1 ? '' : 's' }}</v-btn>
      </div>
    </v-expand-transition>

    <v-alert
      v-if="message.text"
      :type="message.type"
      variant="tonal"
      density="compact"
      closable
      class="mx-4 mb-2"
      @click:close="message.text = ''; message.list = []"
    >
      {{ message.text }}
      <ul v-if="message.list.length" class="mt-1 ml-4">
        <li v-for="(e, i) in message.list.slice(0, 10)" :key="i">{{ e }}</li>
        <li v-if="message.list.length > 10">…and {{ message.list.length - 10 }} more</li>
      </ul>
    </v-alert>

    <v-card-text class="pa-0">
      <v-data-table-server
        :headers="headers"
        :items="items"
        :items-length="serverTotal"
        :page="page"
        :items-per-page="itemsPerPage"
        :sort-by="sortBy"
        :items-per-page-options="[10, 25, 50, 100]"
        :loading="tableLoading"
        :row-props="rowProps"
        item-value="id"
        class="elevation-0"
        hover
        @update:page="onUpdatePage"
        @update:items-per-page="onUpdateItemsPerPage"
        @update:sort-by="onUpdateSortBy"
      >
        <template #item.code="{ item }">
          <input
            v-if="drafts[item.id]"
            v-model="drafts[item.id].code"
            class="inline-edit-input code-input"
            :class="{ changed: isFieldDirty(item.id, 'code') }"
            :disabled="readOnly || saving"
            @keydown.enter.prevent="saveAll"
            @keydown.esc.prevent="discardRow(item.id)"
          />
          <code
            v-else
            class="inline-edit-trigger"
            :class="{ disabled: readOnly }"
            @click="startEdit(item)"
          >{{ item.code }}</code>
        </template>
        <template #item.title="{ item }">
          <input
            v-if="drafts[item.id]"
            v-model="drafts[item.id].title"
            class="inline-edit-input"
            :class="{ changed: isFieldDirty(item.id, 'title') }"
            :disabled="readOnly || saving"
            @keydown.enter.prevent="saveAll"
            @keydown.esc.prevent="discardRow(item.id)"
          />
          <span
            v-else
            class="inline-edit-trigger"
            :class="{ disabled: readOnly }"
            @click="startEdit(item)"
          >{{ item.title || '—' }}</span>
        </template>
        <template #item.sort_order="{ item }">
          <input
            v-if="drafts[item.id]"
            v-model="drafts[item.id].sort_order"
            type="number"
            class="inline-edit-input sort-input"
            :class="{ changed: isFieldDirty(item.id, 'sort_order') }"
            :disabled="readOnly || saving"
            @keydown.enter.prevent="saveAll"
            @keydown.esc.prevent="discardRow(item.id)"
          />
          <span
            v-else
            class="inline-edit-trigger"
            :class="{ disabled: readOnly }"
            @click="startEdit(item)"
          >{{ item.sort_order ?? 0 }}</span>
        </template>
        <template #item.translations="{ item }">
          <div class="text-body-2 d-flex flex-column flex-wrap gap-1">
            <template v-for="(title, lang) in item.translations || {}" :key="lang">
              <div class="d-flex align-center gap-1">
                <v-chip size="small" variant="tonal" density="compact">{{ (lang || '').toUpperCase() }}</v-chip>
                <span>{{ title }}</span>
              </div>
            </template>
            <span v-if="!item.translations || !Object.keys(item.translations).length" class="text-medium-emphasis">—</span>
          </div>
        </template>
        <template #item.actions="{ item }">
          <v-btn
            v-if="drafts[item.id]"
            icon
            size="small"
            variant="text"
            title="Undo changes to this row"
            :disabled="saving"
            @click="discardRow(item.id)"
          >
            <v-icon>mdi-undo</v-icon>
          </v-btn>
          <v-btn size="small" variant="text" :disabled="readOnly" @click="openTranslations(item)">Translations</v-btn>
          <v-menu location="bottom end" :disabled="readOnly">
            <template #activator="{ props: menuProps }">
              <v-btn icon size="small" variant="text" v-bind="menuProps" :disabled="readOnly">
                <v-icon>mdi-dots-vertical</v-icon>
              </v-btn>
            </template>
            <v-list density="compact">
              <v-list-item prepend-icon="mdi-delete" title="Delete" color="error" @click="$emit('delete-item', item)" />
            </v-list>
          </v-menu>
        </template>
      </v-data-table-server>
    </v-card-text>

    <!-- Auto sort confirm -->
    <v-dialog v-model="resortDialog.show" max-width="440" persistent>
      <v-card>
        <v-card-title class="pt-5 px-6">Renumber sort order?</v-card-title>
        <v-card-text class="px-6 pt-2 pb-4">
          <p class="mb-4 text-body-2" style="line-height: 1.6">
            Sets the sort order of all <strong>{{ serverTotal }}</strong> items by
            <strong>{{ resortDialog.opt?.label }}</strong>, replacing the current values.
          </p>
          <p class="mb-6 text-body-2 text-medium-emphasis" style="line-height: 1.6">
            Numbers are compared naturally (2 before 10).
          </p>
          <div class="text-body-2 font-weight-medium mb-2">Step between items</div>
          <v-text-field
            v-model.number="resortDialog.step"
            type="number"
            min="1"
            hint="10 leaves room to insert items later (10, 20, 30…)"
            persistent-hint
            variant="outlined"
            density="compact"
            style="max-width: 160px"
          />
        </v-card-text>
        <v-card-actions class="px-6 pb-5">
          <v-spacer />
          <v-btn variant="text" :disabled="resortDialog.saving" @click="resortDialog.show = false">Cancel</v-btn>
          <v-btn color="primary" :loading="resortDialog.saving" @click="doResort">Renumber</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <ItemFormDialog
      v-model="itemForm.show"
      :item="itemForm.item"
      @saved="$emit('item-form-saved', $event)"
    />
    <ItemsCsvImportDialog
      v-model="importDialog"
      :codelist-id="codelistId"
      @saved="onImported"
    />
    <ItemTranslationsDialog
      v-model="translationsDialog.show"
      :item="translationsDialogItem"
      :translations="translationsDialogItem?.translations || {}"
      :enabled-languages="enabledLanguages"
      @add-translation="onAddTranslation"
      @remove-translation="onRemoveTranslation"
    />
  </v-card>
</template>

<script setup>
import { reactive, computed, ref, watch } from 'vue';
import ItemFormDialog from './ItemFormDialog.vue';
import ItemTranslationsDialog from './ItemTranslationsDialog.vue';
import ItemsCsvImportDialog from './ItemsCsvImportDialog.vue';
import { useCodelistsApi } from '../composables/useCodelistsApi';
import { toCsv, downloadCsv } from '../composables/csv';

defineOptions({ name: 'CodelistItemsTable' });

const props = defineProps({
  codelistId: { type: [Number, String], required: true },
  codelistName: { type: String, default: '' },
  reloadTick: { type: Number, default: 0 },
  enabledLanguages: { type: Array, default: () => [] },
  readOnly: { type: Boolean, default: false },
});

const emit = defineEmits(['delete-item', 'item-form-saved', 'add-translation', 'remove-translation', 'items-changed']);

const { fetchItemsPage, fetchAllItems, batchUpdateItems, resortItems } = useCodelistsApi();

const items = ref([]);
const serverTotal = ref(0);
const page = ref(1);
const itemsPerPage = ref(25);
const sortBy = ref([]);
const search = ref('');
const searchDebounced = ref('');
const tableLoading = ref(false);
const saving = ref(false);
const exporting = ref(false);
const importDialog = ref(false);
const message = reactive({ type: 'info', text: '', list: [] });

/** Row drafts keyed by item id: { code, title, sort_order, orig: {code,title,sort_order} }. Kept across pages. */
const drafts = reactive({});

let searchDebounceTimer;

watch(
  () => props.codelistId,
  (n, o) => {
    if (n !== o) {
      page.value = 1;
      search.value = '';
      searchDebounced.value = '';
      sortBy.value = [];
      clearDrafts();
      message.text = '';
      clearTimeout(searchDebounceTimer);
    }
  }
);

watch(search, (val) => {
  clearTimeout(searchDebounceTimer);
  searchDebounceTimer = setTimeout(() => {
    searchDebounced.value = typeof val === 'string' ? val.trim() : '';
    page.value = 1;
  }, 350);
});

function clearSearch() {
  search.value = '';
  searchDebounced.value = '';
  page.value = 1;
}

function showMessage(type, text, list = []) {
  message.type = type;
  message.text = text;
  message.list = list;
}

async function loadItems() {
  const cid = props.codelistId;
  if (cid == null || cid === '') {
    items.value = [];
    serverTotal.value = 0;
    return;
  }
  tableLoading.value = true;
  try {
    const r = await fetchItemsPage(cid, {
      page: page.value,
      per_page: itemsPerPage.value,
      search: searchDebounced.value,
      sort_by: sortBy.value[0]?.key || '',
      sort_order: sortBy.value[0]?.order || 'asc',
    });
    items.value = r.items;
    serverTotal.value = r.total;
    const maxPage = Math.max(1, Math.ceil(r.total / r.per_page) || 1);
    if (r.items.length === 0 && r.total > 0 && page.value > maxPage) {
      page.value = maxPage;
      return;
    }
  } catch {
    items.value = [];
    serverTotal.value = 0;
  } finally {
    tableLoading.value = false;
  }
}

watch(
  [() => props.codelistId, () => props.reloadTick, page, itemsPerPage, searchDebounced, sortBy],
  () => {
    loadItems();
  },
  { immediate: true }
);

/* ---------- multi-row inline editing ---------- */

function normalize(v) {
  return {
    code: String(v.code ?? '').trim(),
    title: String(v.title ?? '').trim(),
    sort_order: Number(v.sort_order ?? 0) || 0,
  };
}

function startEdit(item) {
  if (props.readOnly || !item?.id || drafts[item.id]) return;
  const orig = normalize(item);
  drafts[item.id] = { ...orig, orig };
}

function editAllOnPage() {
  items.value.forEach(startEdit);
}

function discardRow(id) {
  delete drafts[id];
}

function clearDrafts() {
  Object.keys(drafts).forEach((k) => delete drafts[k]);
}

function discardAll() {
  clearDrafts();
  message.text = '';
}

function isFieldDirty(id, field) {
  const d = drafts[id];
  if (!d) return false;
  return normalize(d)[field] !== d.orig[field];
}

const draftCount = computed(() => Object.keys(drafts).length);

const dirtyRows = computed(() =>
  Object.entries(drafts)
    .map(([id, d]) => ({ id: Number(id), now: normalize(d), orig: d.orig }))
    .filter(({ now, orig }) => now.code !== orig.code || now.title !== orig.title || now.sort_order !== orig.sort_order)
);

function rowProps({ item }) {
  return drafts[item.id] ? { class: 'row-editing' } : {};
}

async function saveAll() {
  if (props.readOnly || saving.value) return;
  const rows = dirtyRows.value;
  if (!rows.length) {
    clearDrafts();
    return;
  }
  const empty = rows.filter((r) => !r.now.code);
  if (empty.length) {
    showMessage('error', 'Code cannot be empty.');
    return;
  }
  const payload = rows.map(({ now, orig }) => {
    const p = { code: orig.code };
    if (now.title !== orig.title) p.title = now.title;
    if (now.sort_order !== orig.sort_order) p.sort_order = now.sort_order;
    if (now.code !== orig.code) p.new_code = now.code;
    return p;
  });
  saving.value = true;
  try {
    const r = await batchUpdateItems(props.codelistId, payload);
    clearDrafts();
    showMessage('success', `Saved ${r.updated} item${r.updated === 1 ? '' : 's'}.`);
    emit('items-changed');
  } catch (e) {
    const d = e?.response?.data;
    showMessage('error', d?.message || e?.message || 'Save failed', Array.isArray(d?.errors) ? d.errors : []);
  } finally {
    saving.value = false;
  }
}

/* ---------- auto sort ---------- */

const resortOptions = [
  { label: 'Code, ascending (A→Z)', by: 'code', order: 'asc', icon: 'mdi-sort-alphabetical-ascending' },
  { label: 'Code, descending (Z→A)', by: 'code', order: 'desc', icon: 'mdi-sort-alphabetical-descending' },
  { label: 'Title, ascending (A→Z)', by: 'title', order: 'asc', icon: 'mdi-sort-alphabetical-ascending' },
  { label: 'Title, descending (Z→A)', by: 'title', order: 'desc', icon: 'mdi-sort-alphabetical-descending' },
];
const resortDialog = reactive({ show: false, saving: false, opt: null, step: 10 });

function openResort(opt) {
  resortDialog.opt = opt;
  resortDialog.show = true;
}

async function doResort() {
  const opt = resortDialog.opt;
  if (!opt) return;
  resortDialog.saving = true;
  try {
    const r = await resortItems(props.codelistId, {
      by: opt.by,
      order: opt.order,
      step: Math.max(1, parseInt(resortDialog.step, 10) || 1),
    });
    resortDialog.show = false;
    clearDrafts();
    sortBy.value = [{ key: 'sort_order', order: 'asc' }];
    page.value = 1;
    showMessage('success', `Sort order renumbered by ${opt.label.toLowerCase()} (${r.updated} changed).`);
    emit('items-changed');
  } catch (e) {
    showMessage('error', e?.response?.data?.message || e?.message || 'Renumber failed');
    resortDialog.show = false;
  } finally {
    resortDialog.saving = false;
  }
}

/* ---------- CSV ---------- */

async function exportCsv() {
  exporting.value = true;
  try {
    const all = await fetchAllItems(props.codelistId);
    all.sort((a, b) => (Number(a.sort_order) || 0) - (Number(b.sort_order) || 0) || Number(a.id) - Number(b.id));
    const csv = toCsv(
      ['code', 'title', 'sort_order'],
      all.map((it) => [it.code, it.title ?? '', it.sort_order ?? 0])
    );
    const base = (props.codelistName || `codelist-${props.codelistId}`).replace(/[^\w.-]+/g, '_');
    downloadCsv(`${base}-items.csv`, csv);
  } catch (e) {
    showMessage('error', e?.response?.data?.message || e?.message || 'Export failed');
  } finally {
    exporting.value = false;
  }
}

function onImported(r) {
  clearDrafts();
  const parts = [`${r.created} added`, `${r.updated} updated`];
  if (r.deleted) parts.push(`${r.deleted} deleted`);
  showMessage('success', `CSV imported: ${parts.join(', ')}.`);
  emit('items-changed');
}

/* ---------- table events ---------- */

function onUpdatePage(p) {
  page.value = p;
}

function onUpdateSortBy(v) {
  sortBy.value = Array.isArray(v) ? v : [];
  page.value = 1;
}

function onUpdateItemsPerPage(n) {
  itemsPerPage.value = n;
  page.value = 1;
}

const translationsDialog = reactive({ show: false, itemId: null });
const translationsDialogItem = computed(() =>
  translationsDialog.itemId != null ? items.value.find((i) => i.id === translationsDialog.itemId) || null : null
);

function onAddTranslation(payload) {
  emit('add-translation', { itemId: translationsDialog.itemId, ...payload });
}

function onRemoveTranslation(lang) {
  emit('remove-translation', { itemId: translationsDialog.itemId, lang });
}

const headers = [
  { title: 'ID', key: 'id', width: '80' },
  { title: 'Code', key: 'code', width: '160' },
  { title: 'Title', key: 'title' },
  { title: 'Sort', key: 'sort_order', width: '100' },
  { title: 'Translations', key: 'translations', sortable: false },
  { title: '', key: 'actions', sortable: false, width: '200', align: 'end' },
];

const itemForm = reactive({ show: false, item: null });

function openCreateItem() {
  itemForm.item = null;
  itemForm.show = true;
}

function openEditItem(item) {
  startEdit(item);
}

function openTranslations(item) {
  translationsDialog.itemId = item.id;
  translationsDialog.show = true;
}

defineExpose({ openCreateItem, openEditItem, openTranslations });
</script>

<style scoped>
.inline-edit-input {
  width: 100%;
  min-width: 72px;
  border: 1px solid rgba(0, 0, 0, 0.24);
  border-radius: 6px;
  padding: 4px 8px;
  font-size: 0.875rem;
  background: rgb(var(--v-theme-surface));
}
.inline-edit-input:focus {
  outline: none;
  border-color: rgba(var(--v-theme-primary), 0.8);
}
.inline-edit-input.changed {
  border-color: rgb(var(--v-theme-warning));
  background: rgba(var(--v-theme-warning), 0.08);
}
.code-input {
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
}
.sort-input {
  width: 80px;
  text-align: right;
}
.inline-edit-trigger {
  cursor: pointer;
  border-radius: 4px;
  padding: 2px 4px;
}
.inline-edit-trigger:hover {
  background: rgba(var(--v-theme-primary), 0.08);
}
.inline-edit-trigger.disabled {
  cursor: default;
}
.inline-edit-trigger.disabled:hover {
  background: transparent;
}
.edit-bar {
  background: rgba(var(--v-theme-primary), 0.06);
  border-top: 1px solid rgba(var(--v-theme-primary), 0.2);
  border-bottom: 1px solid rgba(var(--v-theme-primary), 0.2);
}
:deep(.row-editing) {
  background: rgba(var(--v-theme-primary), 0.03);
}
.cl-items-search :deep(.v-field) {
  border-radius: 10px;
}
</style>
