<template>
  <v-card>
    <v-card-title class="d-flex flex-wrap align-center ga-2">
      <span>Groups</span>
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
        placeholder="Search group name"
        prepend-inner-icon="mdi-magnify"
        class="cl-groups-search"
        style="max-width: 280px"
        @click:clear="clearSearch"
      />
      <v-btn small variant="outlined" color="primary" density="comfortable" :disabled="readOnly" @click="openCreateGroup">
        <v-icon size="small" icon="mdi-plus" />
        <span class="text-caption">Add group</span>
      </v-btn>
    </v-card-title>
    <v-card-text class="pa-0">
      <v-data-table-server
        v-if="serverTotal > 0 || tableLoading || searchDebounced"
        :headers="headers"
        :items="groups"
        :items-length="serverTotal"
        :page="page"
        :items-per-page="itemsPerPage"
        :items-per-page-options="[10, 25, 50, 100]"
        :loading="tableLoading"
        item-value="id"
        class="elevation-0 codelist-groups-table"
        hover
        @update:page="onUpdatePage"
        @update:items-per-page="onUpdateItemsPerPage"
      >
        <template #item.name="{ item: grp }">
          <strong>{{ grp.name }}</strong>
        </template>
        <template #item.sort_order="{ item: grp }">
          <input
            type="number"
            :value="grp.sort_order ?? ''"
            class="sort-input"
            :disabled="readOnly"
            @change="
              emit('update-group-sort', {
                groupId: grp.id,
                sort_order: $event.target.value === '' ? null : Number($event.target.value),
              })
            "
          />
        </template>
        <template #item.translations="{ item: grp }">
          <div class="text-body-2 d-flex flex-column gap-1">
            <template v-for="(title, lang) in grp.translations || {}" :key="lang">
              <div class="d-flex align-center gap-1">
                <v-chip size="small" variant="tonal" density="compact">{{ (lang || '').toUpperCase() }}</v-chip>
                <span>{{ title }}</span>
              </div>
            </template>
            <span v-if="!grp.translations || !Object.keys(grp.translations).length" class="text-medium-emphasis">—</span>
          </div>
        </template>
        <template #item.items="{ item: grp }">
          <div class="text-body-2 d-flex flex-column gap-1">
            <template v-for="itemId in grp.item_ids || []" :key="itemId">
              <div v-if="itemForId(itemId)">
                {{ itemLabel(itemForId(itemId)) }}
              </div>
              <span v-else class="text-medium-emphasis">(id {{ itemId }})</span>
            </template>
            <span v-if="!(grp.item_ids || []).length" class="text-medium-emphasis">—</span>
          </div>
        </template>
        <template #item.actions="{ item: grp }">
          <v-menu location="bottom end" :disabled="readOnly">
            <template #activator="{ props: menuProps }">
              <v-btn icon size="small" variant="text" density="compact" v-bind="menuProps" title="Actions" :disabled="readOnly">
                <v-icon>mdi-dots-vertical</v-icon>
              </v-btn>
            </template>
            <v-list density="compact">
              <v-list-item prepend-icon="mdi-translate" title="Translations" @click="openGroupTranslations(grp)" />
              <v-list-item prepend-icon="mdi-playlist-edit" title="Items" @click="openGroupItems(grp)" />
              <v-list-item prepend-icon="mdi-pencil" title="Edit" @click="openEditGroup(grp)" />
              <v-list-item prepend-icon="mdi-delete" title="Delete" color="error" @click="$emit('delete-group', grp)" />
            </v-list>
          </v-menu>
        </template>
      </v-data-table-server>
      <p v-else class="text-medium-emphasis pa-4">No groups. Add a group to organize items.</p>
    </v-card-text>

    <GroupFormDialog
      v-model="groupForm.show"
      :group="groupForm.group"
      @saved="$emit('group-form-saved', $event)"
    />
    <GroupItemsDialog
      v-model="groupItemsDialog.show"
      :group="groupItemsDialog.group"
      :items="pickerItems"
      :group-item-ids="groupItemsDialog.group?.item_ids || []"
      @add-item="(id) => $emit('add-group-item', groupItemsDialog.group?.id, id)"
      @remove-item="(id) => $emit('remove-group-item', groupItemsDialog.group?.id, id)"
    />
    <GroupTranslationsDialog
      v-model="groupTranslationsDialog.show"
      :group="groupTranslationsDialogGroup"
      :translations="groupTranslationsDialogGroup?.translations || {}"
      :enabled-languages="enabledLanguages"
      @add-translation="onAddGroupTranslation"
      @remove-translation="onRemoveGroupTranslation"
    />
  </v-card>
</template>

<script setup>
import { reactive, computed, ref, watch } from 'vue';
import GroupFormDialog from './GroupFormDialog.vue';
import GroupItemsDialog from './GroupItemsDialog.vue';
import GroupTranslationsDialog from './GroupTranslationsDialog.vue';
import { useCodelistsApi } from '../composables/useCodelistsApi';

defineOptions({ name: 'CodelistGroupsCard' });

const props = defineProps({
  codelistId: { type: [Number, String], required: true },
  reloadTick: { type: Number, default: 0 },
  enabledLanguages: { type: Array, default: () => [] },
  readOnly: { type: Boolean, default: false },
});

const emit = defineEmits([
  'delete-group',
  'group-form-saved',
  'add-group-item',
  'remove-group-item',
  'add-group-translation',
  'remove-group-translation',
  'update-group-sort',
]);

const { fetchGroupsPage, fetchItems } = useCodelistsApi();

const groups = ref([]);
const serverTotal = ref(0);
const page = ref(1);
const itemsPerPage = ref(25);
const search = ref('');
const searchDebounced = ref('');
const tableLoading = ref(false);
/** Full item list for group row labels and GroupItemsDialog (same as legacy detail payload). */
const pickerItems = ref([]);

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

watch(
  [() => props.codelistId, () => props.reloadTick],
  async () => {
    const cid = props.codelistId;
    pickerItems.value = [];
    if (cid == null || cid === '') return;
    try {
      pickerItems.value = await fetchItems(cid);
    } catch {
      pickerItems.value = [];
    }
  },
  { immediate: true }
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

async function loadGroups() {
  const cid = props.codelistId;
  if (cid == null || cid === '') {
    groups.value = [];
    serverTotal.value = 0;
    return;
  }
  tableLoading.value = true;
  try {
    const r = await fetchGroupsPage(cid, {
      page: page.value,
      per_page: itemsPerPage.value,
      search: searchDebounced.value,
    });
    groups.value = r.groups;
    serverTotal.value = r.total;
    const maxPage = Math.max(1, Math.ceil(r.total / r.per_page) || 1);
    if (r.groups.length === 0 && r.total > 0 && page.value > maxPage) {
      page.value = maxPage;
      return;
    }
  } catch {
    groups.value = [];
    serverTotal.value = 0;
  } finally {
    tableLoading.value = false;
  }
}

watch(
  [() => props.codelistId, () => props.reloadTick, page, itemsPerPage, searchDebounced],
  () => {
    loadGroups();
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

const groupForm = reactive({ show: false, group: null });
const groupItemsDialog = reactive({ show: false, group: null });
const groupTranslationsDialog = reactive({ show: false, groupId: null });

const groupTranslationsDialogGroup = computed(() =>
  groupTranslationsDialog.groupId != null
    ? groups.value.find((g) => g.id === groupTranslationsDialog.groupId) || null
    : null
);

const headers = [
  { title: 'Group', key: 'name', sortable: false },
  { title: 'Sort', key: 'sort_order', sortable: false, width: '80', align: 'end' },
  { title: 'Translations', key: 'translations', sortable: false },
  { title: 'Items', key: 'items', sortable: false },
  { title: '', key: 'actions', sortable: false, width: '60', align: 'end' },
];

function itemForId(id) {
  const numId = Number(id);
  if (Number.isNaN(numId)) return null;
  return pickerItems.value.find((i) => Number(i.id) === numId) || null;
}

function itemLabel(item) {
  if (!item) return '—';
  return item.title?.trim() || item.code || '—';
}

function openCreateGroup() {
  groupForm.group = null;
  groupForm.show = true;
}

function openEditGroup(grp) {
  groupForm.group = grp;
  groupForm.show = true;
}

function openGroupItems(grp) {
  groupItemsDialog.group = grp;
  groupItemsDialog.show = true;
}

function openGroupTranslations(grp) {
  groupTranslationsDialog.groupId = grp.id;
  groupTranslationsDialog.show = true;
}

function onAddGroupTranslation(payload) {
  if (groupTranslationsDialog.groupId == null) return;
  emit('add-group-translation', {
    groupId: groupTranslationsDialog.groupId,
    lang: payload.lang,
    title: payload.title,
  });
}

function onRemoveGroupTranslation(lang) {
  if (groupTranslationsDialog.groupId == null) return;
  emit('remove-group-translation', { groupId: groupTranslationsDialog.groupId, lang });
}

defineExpose({ openCreateGroup, openEditGroup, openGroupItems, openGroupTranslations });
</script>

<style scoped>
.codelist-groups-table :deep(td),
.codelist-groups-table :deep(th) {
  vertical-align: top;
}
.sort-input {
  margin-top: 2px;
  width: 60px;
  border: 1px solid rgba(0, 0, 0, 0.2);
  border-radius: 4px;
  padding: 2px 6px;
  text-align: right;
  font-size: 0.875rem;
}
.sort-input:focus {
  outline: none;
  border-color: rgba(var(--v-theme-primary), 0.8);
}
.cl-groups-search :deep(.v-field) {
  border-radius: 10px;
}
</style>
