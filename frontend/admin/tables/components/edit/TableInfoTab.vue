<template>
  <div class="tables-tab-panel pa-4">
    <div class="d-flex align-center mb-4">
      <v-spacer />
      <v-btn color="primary" size="small" prepend-icon="mdi-content-save" :loading="saving" @click="save">
        Save changes
      </v-btn>
    </div>
    <v-row>
      <v-col cols="12" md="6">
        <TablesFormField label="Database ID">
          <v-text-field v-model="info.db_id" readonly variant="outlined" density="compact" hide-details />
        </TablesFormField>
      </v-col>
      <v-col cols="12" md="6">
        <TablesFormField label="Table ID">
          <v-text-field v-model="info.table_id" readonly variant="outlined" density="compact" hide-details />
        </TablesFormField>
      </v-col>
      <v-col cols="12">
        <TablesFormField label="Title">
          <v-text-field v-model="info.title" variant="outlined" density="compact" hide-details />
        </TablesFormField>
      </v-col>
      <v-col cols="12">
        <TablesFormField label="Description">
          <v-textarea v-model="info.description" variant="outlined" rows="3" hide-details />
        </TablesFormField>
      </v-col>
    </v-row>
  </div>
</template>

<script setup>
import { reactive, ref, watch } from 'vue';
import { useTablesApi } from '../../composables/useTablesApi';
import TablesFormField from '../TablesFormField.vue';

const props = defineProps({
  dbId: { type: String, required: true },
  tableId: { type: String, required: true },
  initialTitle: { type: String, default: '' },
  initialDescription: { type: String, default: '' },
});

const emit = defineEmits(['saved', 'error']);

const api = useTablesApi();
const saving = ref(false);
const info = reactive({
  db_id: props.dbId,
  table_id: props.tableId,
  title: props.initialTitle,
  description: props.initialDescription,
});

watch(
  () => [props.initialTitle, props.initialDescription],
  () => {
    info.title = props.initialTitle;
    info.description = props.initialDescription;
  }
);

async function save() {
  saving.value = true;
  try {
    await api.updateTableInfo(props.dbId, props.tableId, {
      title: info.title || '',
      description: info.description || '',
    });
    emit('saved', 'Table information updated successfully');
  } catch (e) {
    emit('error', e.response?.data?.message || e.message);
  } finally {
    saving.value = false;
  }
}
</script>
