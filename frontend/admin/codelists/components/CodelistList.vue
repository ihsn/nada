<template>
  <div>
    <v-card>
      <v-card-text>
        <v-data-table
          :headers="headers"
          :items="codelists"
          :loading="loading"
          :items-per-page="-1"
          item-value="id"
          class="elevation-0"
          hide-default-footer
        >
          <template #item.item_count="{ item }">
            {{ item.item_count ?? '—' }}
          </template>
          <template #item.group_count="{ item }">
            {{ item.group_count ?? '—' }}
          </template>
          <template #item.actions="{ item }">
            <v-menu location="bottom end" transition="scale-transition">
              <template #activator="{ props: menuProps }">
                <v-btn icon size="small" variant="text" v-bind="menuProps" title="Actions">
                  <v-icon>mdi-dots-vertical</v-icon>
                </v-btn>
              </template>
              <v-list density="compact">
                <v-list-item prepend-icon="mdi-pencil" title="Edit" @click="openEdit(item)" />
                <v-list-item prepend-icon="mdi-delete" title="Delete" color="error" @click="$emit('delete', item)" />
                <v-list-item prepend-icon="mdi-playlist-edit" title="Manage" @click="$emit('manage', item)"/>
              </v-list>
            </v-menu>
          </template>
        </v-data-table>
      </v-card-text>
    </v-card>

    <CodelistFormDialog
      v-model="formDialog.show"
      :codelist="formDialog.codelist"
      @saved="onFormSaved"
    />
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import CodelistFormDialog from './CodelistFormDialog.vue';

defineOptions({ name: 'CodelistList' });

defineProps({
  codelists: { type: Array, default: () => [] },
  loading: Boolean,
});

const emit = defineEmits(['manage', 'delete', 'saved']);

defineExpose({ openCreate });

const headers = [
  { title: 'ID', key: 'id', width: '80' },
  { title: 'Name', key: 'name', width: '160' },
  { title: 'Description', key: 'description', sortable: false },
  { title: 'Items', key: 'item_count', width: '80' },
  { title: 'Groups', key: 'group_count', width: '80' },
  { title: '', key: 'actions', sortable: false, width: '140', align: 'end' },
];

const formDialog = reactive({
  show: false,
  codelist: null,
});

function openCreate() {
  formDialog.codelist = null;
  formDialog.show = true;
}

function openEdit(item) {
  formDialog.codelist = item;
  formDialog.show = true;
}

function onFormSaved(payload) {
  if (formDialog.codelist?.id) {
    emit('saved', { ...payload, codelistId: formDialog.codelist.id });
  } else {
    emit('saved', payload);
  }
}
</script>
