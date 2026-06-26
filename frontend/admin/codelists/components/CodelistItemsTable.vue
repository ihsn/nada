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
      <v-btn size="small" color="primary" variant="outlined" :disabled="readOnly" @click="openCreateItem">
        <v-icon size="small" icon="mdi-plus" />
        <span class="text-caption">Add item</span>
      </v-btn>
    </v-card-title>
    <v-card-text class="pa-0">
      <v-data-table-server
        :headers="headers"
        :items="items"
        :items-length="serverTotal"
        :page="page"
        :items-per-page="itemsPerPage"
        :items-per-page-options="[10, 25, 50, 100]"
        :loading="tableLoading"
        item-value="id"
        class="elevation-0"
        hover
        @update:page="onUpdatePage"
        @update:items-per-page="onUpdateItemsPerPage"
      >
        <template #item.code="{ item }">
          <div v-if="isEditing(item)" class="inline-edit-cell">
            <input
              v-model="editDraft.code"
              class="inline-edit-input code-input"
              :disabled="readOnly || editSaving"
              @keydown.enter.prevent="saveInlineEdit(item)"
              @keydown.esc.prevent="cancelInlineEdit"
            />
          </div>
          <code
            v-else
            class="inline-edit-trigger"
            :class="{ disabled: readOnly }"
            @click="startInlineEdit(item)"
          >{{ item.code }}</code>
        </template>
        <template #item.title="{ item }">
          <div v-if="isEditing(item)" class="inline-edit-cell">
            <input
              v-model="editDraft.title"
              class="inline-edit-input"
              :disabled="readOnly || editSaving"
              @keydown.enter.prevent="saveInlineEdit(item)"
              @keydown.esc.prevent="cancelInlineEdit"
            />
          </div>
          <span
            v-else
            class="inline-edit-trigger"
            :class="{ disabled: readOnly }"
            @click="startInlineEdit(item)"
          >{{ item.title || '—' }}</span>
        </template>
        <template #item.sort_order="{ item }">
          <div v-if="isEditing(item)" class="inline-edit-cell">
            <input
              v-model="editDraft.sort_order"
              type="number"
              class="inline-edit-input sort-input"
              :disabled="readOnly || editSaving"
              @keydown.enter.prevent="saveInlineEdit(item)"
              @keydown.esc.prevent="cancelInlineEdit"
            />
          </div>
          <span
            v-else
            class="inline-edit-trigger"
            :class="{ disabled: readOnly }"
            @click="startInlineEdit(item)"
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
          <template v-if="isEditing(item)">
            <v-btn
              size="small"
              color="primary"
              variant="flat"
              :loading="editSaving"
              :disabled="readOnly"
              @click="saveInlineEdit(item)"
            >Save</v-btn>
            <v-btn size="small" variant="text" :disabled="editSaving" @click="cancelInlineEdit">Cancel</v-btn>
          </template>
          <template v-else>
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
        </template>
      </v-data-table-server>
    </v-card-text>

    <ItemFormDialog
      v-model="itemForm.show"
      :item="itemForm.item"
      @saved="$emit('item-form-saved', $event)"
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
import { useCodelistsApi } from '../composables/useCodelistsApi';

defineOptions({ name: 'CodelistItemsTable' });

const props = defineProps({
  codelistId: { type: [Number, String], required: true },
  reloadTick: { type: Number, default: 0 },
  enabledLanguages: { type: Array, default: () => [] },
  readOnly: { type: Boolean, default: false },
});

const emit = defineEmits(['delete-item', 'item-form-saved', 'add-translation', 'remove-translation', 'update-item-sort']);

const { fetchItemsPage } = useCodelistsApi();

const items = ref([]);
const serverTotal = ref(0);
const page = ref(1);
const itemsPerPage = ref(25);
const search = ref('');
const searchDebounced = ref('');
const tableLoading = ref(false);
const editingItemId = ref(null);
const editSaving = ref(false);
const editDraft = reactive({ code: '', title: '', sort_order: '' });

let searchDebounceTimer;

watch(
  () => props.codelistId,
  (n, o) => {
    if (n !== o) {
      page.value = 1;
      search.value = '';
      searchDebounced.value = '';
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

function isEditing(item) {
  return Number(editingItemId.value) === Number(item?.id);
}

function startInlineEdit(item) {
  if (props.readOnly || !item?.id) return;
  editingItemId.value = item.id;
  editDraft.code = item.code ?? '';
  editDraft.title = item.title ?? '';
  editDraft.sort_order = item.sort_order ?? 0;
}

function cancelInlineEdit() {
  editingItemId.value = null;
  editSaving.value = false;
}

async function saveInlineEdit(item) {
  if (props.readOnly || !item?.id || !isEditing(item) || editSaving.value) return;
  editSaving.value = true;
  try {
    emit('item-form-saved', {
      isEdit: true,
      itemId: item.id,
      code: String(editDraft.code ?? '').trim(),
      title: String(editDraft.title ?? '').trim() || null,
      sort_order: Number(editDraft.sort_order ?? 0) || 0,
    });
    editingItemId.value = null;
  } finally {
    editSaving.value = false;
  }
}

watch(
  [() => props.codelistId, () => props.reloadTick, page, itemsPerPage, searchDebounced],
  () => {
    loadItems();
  },
  { immediate: true }
);

function onUpdatePage(p) {
  page.value = p;
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
  { title: 'Code', key: 'code', width: '140' },
  { title: 'Title', key: 'title', width: '200' },
  { title: 'Sort', key: 'sort_order', width: '80' },
  { title: 'Translations', key: 'translations', sortable: false },
  { title: '', key: 'actions', sortable: false, width: '180', align: 'end' },
];

const itemForm = reactive({ show: false, item: null });

function openCreateItem() {
  itemForm.item = null;
  itemForm.show = true;
}

function openEditItem(item) {
  startInlineEdit(item);
}

function openTranslations(item) {
  translationsDialog.itemId = item.id;
  translationsDialog.show = true;
}

defineExpose({ openCreateItem, openEditItem, openTranslations });
</script>

<style scoped>
.sort-input {
  margin-top: 2px;
  width: 60px;
  border: 1px solid rgba(0, 0, 0, 0.2);
  border-radius: 4px;
  padding: 2px 6px;
  text-align: right;
  font-size: 0.875rem;
}
.inline-edit-cell {
  min-width: 72px;
}
.inline-edit-input {
  width: 100%;
  border: 1px solid rgba(0, 0, 0, 0.24);
  border-radius: 6px;
  padding: 4px 8px;
  font-size: 0.875rem;
}
.inline-edit-input:focus {
  outline: none;
  border-color: rgba(var(--v-theme-primary), 0.8);
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
.sort-input:focus {
  outline: none;
  border-color: rgba(var(--v-theme-primary), 0.8);
}
.cl-items-search :deep(.v-field) {
  border-radius: 10px;
}
</style>
