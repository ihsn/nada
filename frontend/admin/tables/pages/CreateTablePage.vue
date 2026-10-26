<template>
  <div>
    <TablesBreadcrumbs :items="breadcrumbItems" />

    <TablesPageHeader
      title="Create new table"
      subtitle="Define database and table IDs, then import data on the edit screen"
      icon="mdi-table-plus"
    />

    <v-card class="admin-tables-surface" rounded="lg" elevation="1" max-width="800">
      <v-card-text class="pa-5">
        <v-alert type="info" variant="tonal" density="compact" class="mb-4">
          After creating the table, open the <strong>Data management</strong> tab to upload a CSV and import data. Field
          definitions can be created from the CSV header or edited under <strong>Data dictionary</strong>.
        </v-alert>
        <v-form ref="formRef" @submit.prevent="createTable">
          <v-row>
            <v-col cols="12" md="6">
              <TablesFormField label="Database ID" required>
                <v-text-field
                  v-model="formData.db_id"
                  variant="outlined"
                  density="compact"
                  hide-details="auto"
                  :rules="idRules"
                  @update:model-value="sanitizeId('db_id')"
                />
              </TablesFormField>
            </v-col>
            <v-col cols="12" md="6">
              <TablesFormField label="Table ID" required>
                <v-text-field
                  v-model="formData.table_id"
                  variant="outlined"
                  density="compact"
                  hide-details="auto"
                  :rules="idRules"
                  @update:model-value="sanitizeId('table_id')"
                />
              </TablesFormField>
            </v-col>
            <v-col cols="12">
              <TablesFormField label="Title">
                <v-text-field v-model="formData.title" variant="outlined" density="compact" hide-details />
              </TablesFormField>
            </v-col>
            <v-col cols="12">
              <TablesFormField label="Description">
                <v-textarea v-model="formData.description" variant="outlined" rows="3" hide-details />
              </TablesFormField>
            </v-col>
          </v-row>
        </v-form>
      </v-card-text>
      <v-divider />
      <v-card-actions class="pa-4">
        <v-spacer />
        <v-btn variant="text" :to="{ path: '/' }">Cancel</v-btn>
        <v-btn
          color="primary"
          variant="flat"
          :loading="creating"
          :disabled="!formData.db_id || !formData.table_id"
          prepend-icon="mdi-content-save"
          @click="createTable"
        >
          Create table
        </v-btn>
      </v-card-actions>
    </v-card>
  </div>
</template>

<script setup>
import { ref, reactive, computed, inject } from 'vue';
import { useRouter } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useTablesApi } from '../composables/useTablesApi';
import TablesBreadcrumbs from '../components/TablesBreadcrumbs.vue';
import TablesPageHeader from '../components/TablesPageHeader.vue';
import TablesFormField from '../components/TablesFormField.vue';

defineOptions({ name: 'CreateTablePage' });

const router = useRouter();
const { siteUrl } = useAppConfig();
const setMessage = inject('setMessage', () => {});
const api = useTablesApi();

const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const breadcrumbItems = computed(() => [
  { title: 'Admin', href: `${siteBaseUrl.value}/admin` },
  { title: 'Tables', to: { path: '/' } },
  { title: 'Create table', disabled: true },
]);

const formRef = ref(null);
const creating = ref(false);
const formData = reactive({
  db_id: '',
  table_id: '',
  title: '',
  description: '',
  data_dictionary: [],
});

const idRules = [
  (v) => !!v || 'Required',
  (v) => !v || /^[a-z0-9_]+$/.test(v) || 'Only lowercase letters, numbers, and underscores',
];

function sanitizeId(field) {
  formData[field] = (formData[field] || '').toLowerCase().replace(/[^a-z0-9_]/g, '');
}

async function createTable() {
  const { valid } = await formRef.value?.validate();
  if (!valid) return;
  if (!formData.db_id || !formData.table_id) {
    setMessage('Database ID and Table ID are required', 'error');
    return;
  }
  creating.value = true;
  try {
    await api.createTable(formData.db_id, formData.table_id, {
      title: formData.title || '',
      description: formData.description || '',
      data_dictionary: formData.data_dictionary || [],
    });
    setMessage('Table created successfully', 'success');
    router.push({
      name: 'edit',
      params: { db_id: formData.db_id, table_id: formData.table_id },
    });
  } catch (e) {
    setMessage('Error creating table: ' + (e.response?.data?.message || e.message), 'error');
  } finally {
    creating.value = false;
  }
}
</script>
