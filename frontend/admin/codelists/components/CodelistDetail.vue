<template>
  <div>
    <v-btn variant="text" prepend-icon="mdi-arrow-left" class="pa-0 mb-3" @click="emit('back')">
      Back to codelists
    </v-btn>
    <h2 class="text-h6 mb-3">{{ codelist?.name }}</h2>
    <p v-if="codelist?.description" class="text-body-2 text-medium-emphasis mb-4">
      {{ codelist.description }}
    </p>

    <v-tabs v-model="tab" class="mb-4">
      <v-tab value="items">Items</v-tab>
      <v-tab value="groups">Groups</v-tab>
    </v-tabs>

    <v-window v-model="tab">
      <v-window-item value="items">
        <CodelistItemsTable
          ref="itemsTableRef"
          :items="codelist?.items || []"
          :loading="loading"
          :enabled-languages="enabledLanguages"
          @delete-item="confirmDeleteItem"
          @item-form-saved="onItemFormSaved"
          @add-translation="onAddTranslation"
          @remove-translation="onRemoveTranslation"
        />
      </v-window-item>
      <v-window-item value="groups">
        <CodelistGroupsCard
          ref="groupsCardRef"
          :groups="codelist?.groups || []"
          :items="codelist?.items || []"
          :enabled-languages="enabledLanguages"
          @delete-group="confirmDeleteGroup"
          @group-form-saved="onGroupFormSaved"
          @add-group-item="onAddGroupItem"
          @remove-group-item="onRemoveGroupItem"
          @add-group-translation="onAddGroupTranslation"
          @remove-group-translation="onRemoveGroupTranslation"
        />
      </v-window-item>
    </v-window>

    <!-- Delete item confirm -->
    <v-dialog v-model="deleteItemDialog.show" max-width="400" persistent>
      <v-card>
        <v-card-title>Delete item?</v-card-title>
        <v-card-text>
          Delete &quot;{{ deleteItemDialog.item?.code }}&quot;? Translations will be removed.
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="deleteItemDialog.show = false">Cancel</v-btn>
          <v-btn color="error" :loading="deleteItemDialog.saving" @click="doDeleteItem">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Delete group confirm -->
    <v-dialog v-model="deleteGroupDialog.show" max-width="400" persistent>
      <v-card>
        <v-card-title>Delete group?</v-card-title>
        <v-card-text>
          Delete group &quot;{{ deleteGroupDialog.group?.name }}&quot;? Item assignments will be removed.
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="deleteGroupDialog.show = false">Cancel</v-btn>
          <v-btn color="error" :loading="deleteGroupDialog.saving" @click="doDeleteGroup">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import CodelistItemsTable from './CodelistItemsTable.vue';
import CodelistGroupsCard from './CodelistGroupsCard.vue';
import { useCodelistsApi } from '../composables/useCodelistsApi';

defineOptions({ name: 'CodelistDetail' });

const props = defineProps({
  codelist: { type: Object, default: null },
  loading: Boolean,
  enabledLanguages: { type: Array, default: () => [] },
});

const emit = defineEmits(['refresh', 'back']);

const tab = ref('items');
const itemsTableRef = ref(null);
const groupsCardRef = ref(null);

const {
  createItem,
  updateItem,
  deleteItem,
  saveItemTranslation,
  deleteItemTranslation,
  createGroup,
  updateGroup,
  deleteGroup,
  addGroupItem,
  removeGroupItem,
  saveGroupTranslation,
  deleteGroupTranslation,
} = useCodelistsApi();

const deleteItemDialog = reactive({ show: false, saving: false, item: null });
const deleteGroupDialog = reactive({ show: false, saving: false, group: null });

function confirmDeleteItem(item) {
  deleteItemDialog.item = item;
  deleteItemDialog.show = true;
}

async function doDeleteItem() {
  if (!deleteItemDialog.item) return;
  deleteItemDialog.saving = true;
  try {
    await deleteItem(deleteItemDialog.item.id);
    deleteItemDialog.show = false;
    emit('refresh');
  } catch (e) {
    emit('error', e?.response?.data?.message || e?.message || 'Delete failed');
  } finally {
    deleteItemDialog.saving = false;
  }
}

function onItemFormSaved(payload) {
  const codelistId = props.codelist?.id;
  if (!codelistId) return;
  if (payload.isEdit) {
    updateItem(payload.itemId, {
      code: payload.code,
      title: payload.title,
      sort_order: payload.sort_order,
    }).then(() => emit('refresh')).catch((e) => emit('error', e?.response?.data?.message || e?.message));
  } else {
    createItem(codelistId, {
      code: payload.code,
      title: payload.title,
      sort_order: payload.sort_order,
    }).then(() => emit('refresh')).catch((e) => emit('error', e?.response?.data?.message || e?.message));
  }
}

function onAddTranslation({ itemId, lang, title }) {
  saveItemTranslation(itemId, lang, title).then(() => emit('refresh')).catch((e) => emit('error', e?.response?.data?.message || e?.message));
}

function onRemoveTranslation({ itemId, lang }) {
  deleteItemTranslation(itemId, lang).then(() => emit('refresh')).catch((e) => emit('error', e?.response?.data?.message || e?.message));
}

function confirmDeleteGroup(group) {
  deleteGroupDialog.group = group;
  deleteGroupDialog.show = true;
}

async function doDeleteGroup() {
  if (!deleteGroupDialog.group) return;
  deleteGroupDialog.saving = true;
  try {
    await deleteGroup(deleteGroupDialog.group.id);
    deleteGroupDialog.show = false;
    emit('refresh');
  } catch (e) {
    emit('error', e?.response?.data?.message || e?.message || 'Delete failed');
  } finally {
    deleteGroupDialog.saving = false;
  }
}

function onGroupFormSaved(payload) {
  const codelistId = props.codelist?.id;
  if (!codelistId) return;
  if (payload.isEdit) {
    updateGroup(payload.groupId, { name: payload.name, sort_order: payload.sort_order })
      .then(() => emit('refresh'))
      .catch((e) => emit('error', e?.response?.data?.message || e?.message));
  } else {
    createGroup(codelistId, { name: payload.name, sort_order: payload.sort_order })
      .then(() => emit('refresh'))
      .catch((e) => emit('error', e?.response?.data?.message || e?.message));
  }
}

function onAddGroupItem(groupId, itemId) {
  addGroupItem(groupId, itemId).then(() => emit('refresh')).catch((e) => emit('error', e?.response?.data?.message || e?.message));
}

function onRemoveGroupItem(groupId, itemId) {
  removeGroupItem(groupId, itemId).then(() => emit('refresh')).catch((e) => emit('error', e?.response?.data?.message || e?.message));
}

function onAddGroupTranslation({ groupId, lang, title }) {
  saveGroupTranslation(groupId, lang, title)
    .then(() => emit('refresh'))
    .catch((e) => emit('error', e?.response?.data?.message || e?.message));
}

function onRemoveGroupTranslation({ groupId, lang }) {
  deleteGroupTranslation(groupId, lang)
    .then(() => emit('refresh'))
    .catch((e) => emit('error', e?.response?.data?.message || e?.message));
}
</script>
