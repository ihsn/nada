<template>
  <v-card flat rounded="0">
    <v-card-title class="d-flex align-center">
      <span>Table information</span>
      <v-spacer />
      <v-btn color="primary" size="small" prepend-icon="mdi-content-save" :loading="saving" @click="save">
        Save changes
      </v-btn>
    </v-card-title>
    <v-card-text>
      <v-row>
        <v-col cols="12" md="6">
          <v-text-field v-model="info.db_id" label="Database ID" readonly variant="outlined" density="compact" />
        </v-col>
        <v-col cols="12" md="6">
          <v-text-field v-model="info.table_id" label="Table ID" readonly variant="outlined" density="compact" />
        </v-col>
        <v-col cols="12">
          <v-text-field v-model="info.title" label="Title" variant="outlined" density="compact" />
        </v-col>
        <v-col cols="12">
          <v-textarea v-model="info.description" label="Description" variant="outlined" rows="3" />
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { reactive, ref, watch } from 'vue';
import { useTablesApi } from '../../composables/useTablesApi';

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
