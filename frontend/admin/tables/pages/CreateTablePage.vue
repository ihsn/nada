<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="tables-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <v-row align="center" class="mb-4">
      <v-col cols="12" md="8">
        <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-0">Create new table</h1>
      </v-col>
    </v-row>

    <v-card elevation="1" max-width="800">
      <v-card-title>Table information</v-card-title>
      <v-card-text>
        <v-form ref="formRef" @submit.prevent="createTable">
          <v-row>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.db_id"
                label="Database ID *"
                variant="outlined"
                density="compact"
                :rules="idRules"
                @update:model-value="sanitizeId('db_id')"
              />
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.table_id"
                label="Table ID *"
                variant="outlined"
                density="compact"
                :rules="idRules"
                @update:model-value="sanitizeId('table_id')"
              />
            </v-col>
            <v-col cols="12">
              <v-text-field v-model="formData.title" label="Title" variant="outlined" density="compact" />
            </v-col>
            <v-col cols="12">
              <v-textarea
                v-model="formData.description"
                label="Description"
                variant="outlined"
                rows="3"
              />
            </v-col>
          </v-row>
        </v-form>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" :to="{ path: '/' }">Cancel</v-btn>
        <v-btn
          color="primary"
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
    setTimeout(() => {
      router.push({
        name: 'edit',
        params: { db_id: formData.db_id, table_id: formData.table_id },
      });
    }, 800);
  } catch (e) {
    setMessage('Error creating table: ' + (e.response?.data?.message || e.message), 'error');
  } finally {
    creating.value = false;
  }
}
</script>

<style scoped>
.tables-breadcrumbs {
  font-size: 0.8125rem;
  margin-bottom: 0.5rem;
}
.tables-breadcrumbs :deep(.v-breadcrumbs-item),
.tables-breadcrumbs :deep(.v-breadcrumbs-divider) {
  font-size: 0.8125rem;
}
</style>
