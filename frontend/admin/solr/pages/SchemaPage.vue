<template>
  <div class="admin-solr-stack">
    <v-row dense>
      <v-col cols="12" md="6">
        <v-card elevation="1">
          <v-card-title class="solr-card-title">Schema overview</v-card-title>
          <v-divider />
          <v-card-text class="pa-4">
            <v-progress-linear v-if="loadingInfo" indeterminate class="mb-3" />
            <v-alert v-else-if="infoError" type="error" density="compact" variant="tonal">
              {{ infoError }}
            </v-alert>
            <template v-else-if="schemaMeta">
              <div class="text-body-2 mb-1">
                <span class="text-medium-emphasis">Name</span>
                {{ schemaMeta.name || 'Default' }}
              </div>
              <div class="text-body-2 mb-1">
                <span class="text-medium-emphasis">Version</span>
                {{ schemaMeta.version || '—' }}
              </div>
              <div class="text-body-2">
                <span class="text-medium-emphasis">Unique key</span>
                <span class="solr-mono ml-1">{{ schemaMeta.uniqueKey || '—' }}</span>
              </div>
            </template>
            <div v-else class="text-medium-emphasis text-body-2">Not loaded yet</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="6">
        <v-card elevation="1">
          <v-card-title class="solr-card-title">Field statistics</v-card-title>
          <v-divider />
          <v-card-text class="pa-4">
            <v-progress-linear v-if="loadingStats" indeterminate class="mb-3" />
            <v-alert v-else-if="statsError" type="error" density="compact" variant="tonal">
              {{ statsError }}
            </v-alert>
            <template v-else-if="stats">
              <div class="text-body-2 mb-1">
                Total fields
                <strong class="ml-1">{{ stats.total_fields ?? fields.length }}</strong>
              </div>
              <div class="text-body-2 mb-1">
                Variable fields
                <strong class="ml-1">
                  {{ stats.variable_fields ?? stats.variable_fields_count ?? '—' }}
                </strong>
              </div>
              <div class="text-body-2 mb-1">
                Survey fields
                <strong class="ml-1">
                  {{ stats.survey_fields ?? stats.survey_fields_count ?? '—' }}
                </strong>
              </div>
              <div class="text-body-2">
                Copy fields
                <strong class="ml-1">
                  {{ stats.copy_fields ?? stats.copy_fields_count ?? '—' }}
                </strong>
              </div>
            </template>
            <div v-else class="text-medium-emphasis text-body-2">Not loaded yet</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-card elevation="1">
      <v-card-title class="solr-card-title">Setup &amp; validation</v-card-title>
      <v-divider />
      <v-card-text class="pa-4">
        <v-checkbox
          v-model="replaceExisting"
          label="Replace existing fields"
          density="compact"
          hide-details
          class="mb-3"
          :disabled="indexing.schema"
        />
        <div class="d-flex flex-wrap ga-2 mb-3">
          <v-btn
            color="primary"
            size="small"
            :loading="indexing.schema && pending === 'variables'"
            :disabled="indexing.schema"
            @click="confirmSetup('variables')"
          >
            Setup variable fields
          </v-btn>
          <v-btn
            color="primary"
            size="small"
            :loading="indexing.schema && pending === 'surveys'"
            :disabled="indexing.schema"
            @click="confirmSetup('surveys')"
          >
            Setup survey fields
          </v-btn>
          <v-btn
            color="success"
            size="small"
            :loading="indexing.schema && pending === 'complete'"
            :disabled="indexing.schema"
            @click="confirmSetup('complete')"
          >
            Setup complete schema
          </v-btn>
          <v-btn
            variant="tonal"
            size="small"
            :loading="indexing.schema && pending === 'validate'"
            :disabled="indexing.schema"
            @click="runValidate"
          >
            Validate
          </v-btn>
          <v-btn
            variant="text"
            size="small"
            prepend-icon="mdi-refresh"
            :disabled="indexing.schema"
            @click="refresh"
          >
            Refresh
          </v-btn>
        </div>

        <v-alert
          v-if="indexing.schema"
          type="info"
          density="compact"
          variant="tonal"
          class="mb-3"
        >
          {{ statusMessage }}
        </v-alert>

        <div v-for="(row, i) in results" :key="i" class="mb-2">
          <v-alert :type="row.status === 'success' ? 'success' : 'error'" variant="tonal">
            <strong>{{ row.operation }}</strong>
            <div class="text-body-2 mt-1">{{ row.message }}</div>
            <details v-if="row.details" class="mt-2">
              <summary class="text-caption">Details</summary>
              <pre class="solr-pre mt-2">{{ JSON.stringify(row.details, null, 2) }}</pre>
            </details>
          </v-alert>
        </div>
      </v-card-text>
    </v-card>

    <v-card elevation="1">
      <v-card-title class="solr-card-title d-flex align-center">
        Fields
        <span v-if="fields.length" class="text-body-2 font-weight-regular text-medium-emphasis ml-2">
          ({{ fields.length }})
        </span>
        <v-spacer />
        <v-btn
          size="small"
          variant="text"
          prepend-icon="mdi-refresh"
          :loading="loadingFields"
          @click="loadFields"
        >
          Reload fields
        </v-btn>
      </v-card-title>
      <v-divider />
      <v-data-table
        :headers="fieldHeaders"
        :items="fields"
        :loading="loadingFields"
        :items-per-page="25"
        density="compact"
        class="text-body-2"
      >
        <template #item.name="{ item }">
          <span class="solr-mono">{{ item.name }}</span>
        </template>
        <template #item.indexed="{ item }">
          {{ boolLabel(item.indexed) }}
        </template>
        <template #item.stored="{ item }">
          {{ boolLabel(item.stored) }}
        </template>
        <template #item.multiValued="{ item }">
          {{ boolLabel(item.multiValued) }}
        </template>
        <template #no-data>
          <div class="text-medium-emphasis py-6">
            {{ fieldsError || 'No fields loaded' }}
          </div>
        </template>
      </v-data-table>
    </v-card>

    <v-dialog v-model="confirm.open" max-width="480">
      <v-card>
        <v-card-title class="text-h6">{{ confirm.title }}</v-card-title>
        <v-card-text>{{ confirm.body }}</v-card-text>
        <v-divider />
        <v-card-actions class="pa-3">
          <v-spacer />
          <v-btn variant="text" @click="confirm.open = false">Cancel</v-btn>
          <v-btn color="primary" variant="flat" @click="confirm.action">Continue</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useSolrOps } from '../composables/useSolrOps.js';

defineOptions({ name: 'SolrSchemaPage' });

const { indexing, showToast, api } = useSolrOps();

const loadingInfo = ref(false);
const loadingStats = ref(false);
const loadingFields = ref(false);
const infoError = ref('');
const statsError = ref('');
const schemaInfo = ref(null);
const stats = ref(null);
const fields = ref([]);
const fieldsError = ref('');
const replaceExisting = ref(false);
const results = ref([]);
const pending = ref('');
const statusMessage = ref('');
const confirm = reactive({ open: false, title: '', body: '', action: () => {} });

const fieldHeaders = [
  { title: 'Name', key: 'name' },
  { title: 'Type', key: 'type' },
  { title: 'Indexed', key: 'indexed' },
  { title: 'Stored', key: 'stored' },
  { title: 'Multi', key: 'multiValued' },
];

const schemaMeta = computed(() => {
  const r = schemaInfo.value?.result || schemaInfo.value;
  return r?.schema || null;
});

async function loadInfo() {
  loadingInfo.value = true;
  infoError.value = '';
  try {
    schemaInfo.value = await api().schema();
  } catch (e) {
    infoError.value = e.message;
    schemaInfo.value = null;
  } finally {
    loadingInfo.value = false;
  }
}

async function loadStats() {
  loadingStats.value = true;
  statsError.value = '';
  try {
    const data = await api().schemaStats();
    stats.value = data.result || null;
  } catch (e) {
    statsError.value = e.message;
    stats.value = null;
  } finally {
    loadingStats.value = false;
  }
}

function normalizeFields(list) {
  if (!Array.isArray(list)) return [];
  // api/solr/schema_fields returns name strings; schema.fields are full objects
  return list.map((item) => {
    if (typeof item === 'string') {
      return { name: item, type: '—', indexed: null, stored: null, multiValued: null };
    }
    return {
      name: item.name || '',
      type: item.type || '—',
      indexed: item.indexed,
      stored: item.stored,
      multiValued: item.multiValued,
    };
  });
}

function boolLabel(v) {
  if (v === true || v === 'true') return 'yes';
  if (v === false || v === 'false') return 'no';
  return '—';
}

function fieldsFromSchemaPayload(data) {
  const schema = data?.result?.schema || data?.schema || null;
  return Array.isArray(schema?.fields) ? schema.fields : null;
}

async function loadFields() {
  loadingFields.value = true;
  fieldsError.value = '';
  try {
    // Prefer full field definitions from /schema (objects with type/flags).
    // /schema_fields only returns an array of names.
    let list = fieldsFromSchemaPayload(schemaInfo.value);
    if (!list) {
      const schemaData = await api().schema();
      schemaInfo.value = schemaData;
      list = fieldsFromSchemaPayload(schemaData);
    }
    if (!list) {
      const data = await api().schemaFields();
      list = data.result?.fields || data.result || [];
    }
    fields.value = normalizeFields(list);
    if (!fields.value.length) {
      fieldsError.value = 'Schema returned no fields';
    }
  } catch (e) {
    fields.value = [];
    fieldsError.value = e.message;
    showToast(e.message, 'error');
  } finally {
    loadingFields.value = false;
  }
}

async function refresh() {
  await Promise.all([loadInfo(), loadStats(), loadFields()]);
}

function pushResult(operation, status, message, details) {
  results.value.unshift({ operation, status, message, details });
}

function confirmSetup(kind) {
  const labels = {
    variables: 'Setup variable fields',
    surveys: 'Setup survey fields',
    complete: 'Setup complete schema (variables + surveys)',
  };
  confirm.title = labels[kind];
  confirm.body = replaceExisting.value
    ? 'Existing matching fields may be replaced. Continue?'
    : 'Add missing schema fields. Continue?';
  confirm.action = () => {
    confirm.open = false;
    runSetup(kind);
  };
  confirm.open = true;
}

async function runSetup(kind) {
  indexing.schema = true;
  pending.value = kind;
  statusMessage.value =
    kind === 'complete'
      ? 'Setting up complete schema…'
      : kind === 'variables'
        ? 'Setting up variable fields…'
        : 'Setting up survey fields…';
  try {
    const replace = replaceExisting.value;
    let data;
    if (kind === 'variables') data = await api().schemaSetupVariables(replace);
    else if (kind === 'surveys') data = await api().schemaSetupSurveys(replace);
    else data = await api().schemaSetupComplete(replace);
    pushResult(
      kind === 'complete' ? 'Setup complete schema' : `Setup ${kind} fields`,
      'success',
      'Completed successfully',
      data
    );
    showToast('Schema setup completed');
    await refresh();
  } catch (e) {
    pushResult(`Setup ${kind}`, 'error', e.message, e.cause?.response?.data);
    showToast(e.message, 'error');
  } finally {
    indexing.schema = false;
    pending.value = '';
    statusMessage.value = '';
  }
}

async function runValidate() {
  indexing.schema = true;
  pending.value = 'validate';
  statusMessage.value = 'Validating schema…';
  try {
    const data = await api().schemaValidate();
    pushResult('Validate schema', 'success', 'Validation finished', data);
    showToast('Schema validation finished');
  } catch (e) {
    pushResult('Validate schema', 'error', e.message, e.cause?.response?.data);
    showToast(e.message, 'error');
  } finally {
    indexing.schema = false;
    pending.value = '';
    statusMessage.value = '';
  }
}

onMounted(refresh);
</script>
