<template>
  <v-card>
    <v-card-title class="d-flex align-center">
      Items
      <v-spacer />
      <v-btn size="small" color="primary" variant="outlined" @click="openCreateItem">
        <v-icon size="small" icon="mdi-plus" />
        <span class="text-caption">Add item</span>
      </v-btn>
    </v-card-title>
    <v-card-text>
      <v-data-table
        :headers="headers"
        :items="items"
        :loading="loading"
        :items-per-page="-1"
        item-value="id"
        class="elevation-0"
        hide-default-footer
      >
        <template #item.code="{ item }">
          <code>{{ item.code }}</code>
        </template>
        <template #item.translations="{ item }">
          <div class="text-body-2 d-flex flex-column flex-wrap gap-1">
            <template v-for="(title, lang) in (item.translations || {})" :key="lang">
              <div class="d-flex align-center gap-1">
                <v-chip size="small" variant="tonal" density="compact">{{ (lang || '').toUpperCase() }}</v-chip>
                <span>{{ title }}</span>
              </div>
            </template>
            <span v-if="!item.translations || !Object.keys(item.translations).length" class="text-medium-emphasis">—</span>
          </div>
        </template>
        <template #item.actions="{ item }">
          <v-btn size="small" variant="text" @click="openTranslations(item)">Translations</v-btn>
          <v-menu location="bottom end">
            <template #activator="{ props: menuProps }">
              <v-btn icon size="small" variant="text" v-bind="menuProps">
                <v-icon>mdi-dots-vertical</v-icon>
              </v-btn>
            </template>
            <v-list density="compact">
              <v-list-item prepend-icon="mdi-pencil" title="Edit" @click="openEditItem(item)" />
              <v-list-item prepend-icon="mdi-delete" title="Delete" color="error" @click="$emit('delete-item', item)" />
            </v-list>
          </v-menu>
        </template>
      </v-data-table>
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
import { reactive, computed } from 'vue';
import ItemFormDialog from './ItemFormDialog.vue';
import ItemTranslationsDialog from './ItemTranslationsDialog.vue';

defineOptions({ name: 'CodelistItemsTable' });

const props = defineProps({
  items: { type: Array, default: () => [] },
  loading: Boolean,
  enabledLanguages: { type: Array, default: () => [] },
});

const emit = defineEmits(['delete-item', 'item-form-saved', 'add-translation', 'remove-translation']);

const translationsDialog = reactive({ show: false, itemId: null });
const translationsDialogItem = computed(() =>
  translationsDialog.itemId != null
    ? props.items.find((i) => i.id === translationsDialog.itemId) || null
    : null
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
  itemForm.item = item;
  itemForm.show = true;
}

function openTranslations(item) {
  translationsDialog.itemId = item.id;
  translationsDialog.show = true;
}

defineExpose({ openCreateItem, openEditItem, openTranslations });
</script>
