<template>
  <div>
    <v-btn variant="text" prepend-icon="mdi-arrow-left" class="pa-0 mb-3" @click="emit('back')">
      Back to codelists
    </v-btn>
    <h2 v-if="codelist?.name" class="text-h5 font-weight-medium mb-4">{{ codelist.name }}</h2>
    <v-tabs v-if="codelist?.id" v-model="tab" class="mb-4">
      <v-tab value="details">Codelist details</v-tab>
      <v-tab value="items">Codelist items</v-tab>
      <v-tab value="groups">Groups</v-tab>
    </v-tabs>

    <v-window v-if="codelist?.id" v-model="tab">
      <v-window-item value="details">
        <v-card class="mb-8" border>
          <v-card-title class="d-flex align-center flex-wrap ga-2">
            <v-chip size="small" variant="tonal" color="primary">{{ codelist.name }}</v-chip>
            <v-spacer />
            <v-btn
              v-if="codelist?.has_defaults"
              variant="outlined"
              size="small"
              prepend-icon="mdi-restore"
              :disabled="isLocked"
              @click="restoreDialog.show = true"
            >
              Restore defaults
            </v-btn>
          </v-card-title>
          <v-card-text>
            <v-alert
              v-if="isLocked"
              type="info"
              variant="tonal"
              density="comfortable"
              class="mb-4"
              text="Locked codelists only allow status changes."
            />
            <v-row dense>
              <v-col cols="12" md="4">
                <div class="text-body-2 mb-1 text-medium-emphasis">Name</div>
                <v-text-field
                  :model-value="codelist.name"
                  readonly
                  variant="outlined"
                  density="comfortable"
                />
              </v-col>
              <v-col cols="6" md="2">
                <div class="text-body-2 mb-1 text-medium-emphasis">Agency</div>
                <v-text-field
                  :model-value="codelist.agency"
                  readonly
                  variant="outlined"
                  density="comfortable"
                />
              </v-col>
              <v-col cols="6" md="2">
                <div class="text-body-2 mb-1 text-medium-emphasis">Version</div>
                <v-text-field
                  :model-value="codelist.version"
                  readonly
                  variant="outlined"
                  density="comfortable"
                />
              </v-col>
              <v-col cols="12" md="4">
                <div class="text-body-2 mb-1 text-medium-emphasis">Status</div>
                <v-select
                  v-model="metaForm.status"
                  :items="statusItems"
                  item-title="title"
                  item-value="value"
                  variant="outlined"
                  density="comfortable"
                />
              </v-col>
              <v-col cols="12" md="6">
                <div class="text-body-2 mb-1 text-medium-emphasis">IDNO</div>
                <v-text-field
                  v-model="metaForm.idno"
                  variant="outlined"
                  density="comfortable"
                  :disabled="isLocked"
                  hint="Leave blank to regenerate from agency/name/version."
                  persistent-hint
                >
                  <template #append-inner>
                    <v-btn
                      size="x-small"
                      variant="text"
                      icon="mdi-refresh"
                      title="Regenerate IDNO"
                      :disabled="isLocked"
                      @click="metaForm.idno = ''"
                    />
                  </template>
                </v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <div class="text-body-2 mb-1 text-medium-emphasis">Description</div>
                <v-text-field
                  v-model="metaForm.description"
                  variant="outlined"
                  density="comfortable"
                  :disabled="isLocked"
                  hint="Optional short description"
                  persistent-hint
                />
              </v-col>
            </v-row>
          </v-card-text>
          <v-card-actions class="px-4 pb-4">
            <v-spacer />
            <v-btn
              variant="text"
              :disabled="!isDirty || metaSaving"
              @click="resetMetaForm"
            >
              Cancel
            </v-btn>
            <v-btn
              color="primary"
              :disabled="!isDirty"
              :loading="metaSaving"
              @click="saveCodelistMeta"
            >
              Save
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-window-item>

      <v-window-item value="items">
        <v-alert
          v-if="isLocked"
          type="info"
          variant="tonal"
          density="comfortable"
          class="mb-3"
          text="This codelist is locked. Item edits are disabled."
        />
        <CodelistItemsTable
          ref="itemsTableRef"
          class="mb-8"
          :codelist-id="codelist.id"
          :reload-tick="reloadTick"
          :enabled-languages="enabledLanguages"
          :read-only="isLocked"
          @delete-item="confirmDeleteItem"
          @item-form-saved="onItemFormSaved"
          @add-translation="onAddTranslation"
          @remove-translation="onRemoveTranslation"
          @update-item-sort="onUpdateItemSort"
        />
      </v-window-item>

      <v-window-item value="groups">
        <v-alert
          v-if="isLocked"
          type="info"
          variant="tonal"
          density="comfortable"
          class="mb-3"
          text="This codelist is locked. Group edits are disabled."
        />
        <CodelistGroupsCard
          ref="groupsCardRef"
          class="mb-8"
          :codelist-id="codelist.id"
          :reload-tick="reloadTick"
          :enabled-languages="enabledLanguages"
          :read-only="isLocked"
          @delete-group="confirmDeleteGroup"
          @group-form-saved="onGroupFormSaved"
          @add-group-item="onAddGroupItem"
          @remove-group-item="onRemoveGroupItem"
          @add-group-translation="onAddGroupTranslation"
          @remove-group-translation="onRemoveGroupTranslation"
          @update-group-sort="onUpdateGroupSort"
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

    <!-- Restore defaults confirm -->
    <v-dialog v-model="restoreDialog.show" max-width="440" persistent>
      <v-card>
        <v-card-title>Restore defaults?</v-card-title>
        <v-card-text>
          This will reset all items and groups for <strong>{{ codelist?.name }}</strong> to the built-in defaults.
          Existing translations will be preserved. This cannot be undone.
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="restoreDialog.show = false">Cancel</v-btn>
          <v-btn color="warning" :loading="restoreDialog.saving" @click="doRestore">Restore</v-btn>
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
import { ref, reactive, computed, watch } from 'vue';
import CodelistItemsTable from './CodelistItemsTable.vue';
import CodelistGroupsCard from './CodelistGroupsCard.vue';
import { useCodelistsApi } from '../composables/useCodelistsApi';

defineOptions({ name: 'CodelistDetail' });

const props = defineProps({
  codelist: { type: Object, default: null },
  /** Incremented by parent after mutations so items/groups tables refetch. */
  reloadTick: { type: Number, default: 0 },
  enabledLanguages: { type: Array, default: () => [] },
});

const emit = defineEmits(['refresh', 'back', 'error']);

const tab = ref('details');
const itemsTableRef = ref(null);
const groupsCardRef = ref(null);
const metaSaving = ref(false);
const metaForm = reactive({
  idno: '',
  description: '',
  status: 0,
});
const statusItems = [
  { title: 'Draft', value: 0 },
  { title: 'Review', value: 10 },
  { title: 'Published', value: 20 },
  { title: 'Deprecated', value: 30 },
  { title: 'Archived', value: 40 },
];
const isLocked = computed(() => {
  const s = Number(props.codelist?.status ?? 0);
  return s === 20 || s === 40;
});
const isDirty = computed(() => {
  if (!props.codelist?.id) return false;
  const currentIdno = String(props.codelist?.idno ?? '').trim();
  const currentDesc = String(props.codelist?.description ?? '').trim();
  const currentStatus = Number(props.codelist?.status ?? 0);
  return (
    String(metaForm.idno ?? '').trim() !== currentIdno
    || String(metaForm.description ?? '').trim() !== currentDesc
    || Number(metaForm.status ?? 0) !== currentStatus
  );
});

const {
  createItem,
  updateItem,
  deleteItem,
  saveItemTranslation,
  deleteItemTranslation,
  createGroup,
  updateGroup,
  updateCodelist,
  deleteGroup,
  addGroupItem,
  removeGroupItem,
  saveGroupTranslation,
  deleteGroupTranslation,
  restoreCodelist,
} = useCodelistsApi();

const deleteItemDialog = reactive({ show: false, saving: false, item: null });
const deleteGroupDialog = reactive({ show: false, saving: false, group: null });
const restoreDialog = reactive({ show: false, saving: false });

watch(
  () => props.codelist,
  (c) => {
    metaForm.idno = c?.idno ?? '';
    metaForm.description = c?.description ?? '';
    metaForm.status = Number(c?.status ?? 0);
  },
  { immediate: true, deep: true }
);

async function saveCodelistMeta() {
  const id = props.codelist?.id;
  if (!id || !isDirty.value) return;
  const payload = {};
  const currentIdno = String(props.codelist?.idno ?? '').trim();
  const currentDesc = String(props.codelist?.description ?? '').trim();
  const currentStatus = Number(props.codelist?.status ?? 0);
  const nextStatus = Number(metaForm.status ?? 0);
  const nextIdno = String(metaForm.idno ?? '').trim();
  const nextDesc = String(metaForm.description ?? '').trim();

  if (nextStatus !== currentStatus) {
    payload.status = nextStatus;
  }
  if (!isLocked.value) {
    if (nextIdno !== currentIdno) {
      payload.idno = nextIdno;
    }
    if (nextDesc !== currentDesc) {
      payload.description = nextDesc || null;
    }
  }
  if (!Object.keys(payload).length) return;
  metaSaving.value = true;
  try {
    await updateCodelist(id, payload);
    emit('refresh');
  } catch (e) {
    emit('error', e?.response?.data?.message || e?.message || 'Failed to update codelist');
  } finally {
    metaSaving.value = false;
  }
}

function resetMetaForm() {
  metaForm.idno = props.codelist?.idno ?? '';
  metaForm.description = props.codelist?.description ?? '';
  metaForm.status = Number(props.codelist?.status ?? 0);
}

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

function onUpdateItemSort({ itemId, sort_order }) {
  updateItem(itemId, { sort_order })
    .then(() => emit('refresh'))
    .catch((e) => emit('error', e?.response?.data?.message || e?.message));
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

function onUpdateGroupSort({ groupId, sort_order }) {
  updateGroup(groupId, { sort_order })
    .then(() => emit('refresh'))
    .catch((e) => emit('error', e?.response?.data?.message || e?.message));
}

async function doRestore() {
  if (!props.codelist?.id) return;
  restoreDialog.saving = true;
  try {
    await restoreCodelist(props.codelist.id);
    restoreDialog.show = false;
    emit('refresh');
  } catch (e) {
    emit('error', e?.response?.data?.message || e?.message || 'Restore failed');
  } finally {
    restoreDialog.saving = false;
  }
}
</script>
