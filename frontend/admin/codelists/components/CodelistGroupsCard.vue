<template>
  <v-card>
    <v-card-title class="d-flex align-center">
      Groups
      <v-spacer />
      <v-btn small variant="outlined" color="primary" density="comfortable" @click="openCreateGroup">
        <v-icon size="small" icon="mdi-plus" />
        <span class="text-caption">Add group</span>
      </v-btn>
    </v-card-title>
    <v-card-text>
      <v-data-table
        v-if="groups.length"
        :headers="headers"
        :items="groups"
        :items-per-page="-1"
        item-value="id"
        class="elevation-0 codelist-groups-table"
        hide-default-footer
      >
        <template #item.name="{ item: grp }">
          <strong>{{ grp.name }}</strong>
        </template>
        <template #item.sort_order="{ item: grp }">
          {{ grp.sort_order ?? '—' }}
        </template>
        <template #item.translations="{ item: grp }">
          <div class="text-body-2 d-flex flex-column gap-1">
            <template v-for="(title, lang) in (grp.translations || {})" :key="lang">
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
            <template v-for="itemId in (grp.item_ids || [])" :key="itemId">
              <div v-if="itemForId(itemId)">
                {{ itemLabel(itemForId(itemId)) }}
              </div>
              <span v-else class="text-medium-emphasis">(id {{ itemId }})</span>
            </template>
            <span v-if="!(grp.item_ids || []).length" class="text-medium-emphasis">—</span>
          </div>
        </template>
        <template #item.actions="{ item: grp }">
          <v-menu location="bottom end">
            <template #activator="{ props: menuProps }">
              <v-btn icon size="small" variant="text" density="compact" v-bind="menuProps" title="Actions">
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
      </v-data-table>
      <p v-else class="text-medium-emphasis">No groups. Add a group to organize items.</p>
    </v-card-text>

    <GroupFormDialog
      v-model="groupForm.show"
      :group="groupForm.group"
      @saved="$emit('group-form-saved', $event)"
    />
    <GroupItemsDialog
      v-model="groupItemsDialog.show"
      :group="groupItemsDialog.group"
      :items="items"
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
import { reactive, computed } from 'vue';
import GroupFormDialog from './GroupFormDialog.vue';
import GroupItemsDialog from './GroupItemsDialog.vue';
import GroupTranslationsDialog from './GroupTranslationsDialog.vue';

defineOptions({ name: 'CodelistGroupsCard' });

const props = defineProps({
  groups: { type: Array, default: () => [] },
  items: { type: Array, default: () => [] },
  enabledLanguages: { type: Array, default: () => [] },
});

const emit = defineEmits([
  'delete-group',
  'group-form-saved',
  'add-group-item',
  'remove-group-item',
  'add-group-translation',
  'remove-group-translation',
]);

const groupForm = reactive({ show: false, group: null });
const groupItemsDialog = reactive({ show: false, group: null });
const groupTranslationsDialog = reactive({ show: false, groupId: null });

const groupTranslationsDialogGroup = computed(() =>
  groupTranslationsDialog.groupId != null
    ? props.groups.find((g) => g.id === groupTranslationsDialog.groupId) || null
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
  return props.items.find((i) => Number(i.id) === numId) || null;
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
</style>
