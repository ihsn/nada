<template>
  <div class="admin-solr-stack">
    <v-card elevation="1">
      <v-card-title class="solr-card-title">Configured core</v-card-title>
      <v-divider />
      <v-card-text class="pa-4">
        <p class="text-body-2 text-medium-emphasis mb-4">
          Core name comes from <code>application/config/solr.php</code>
          (<span class="solr-mono">solr_collection</span>).
        </p>

        <div class="d-flex flex-wrap ga-2 mb-4">
          <v-btn
            color="primary"
            prepend-icon="mdi-refresh"
            :loading="core.loading"
            @click="refreshCore"
          >
            Check status
          </v-btn>
          <v-btn
            color="success"
            variant="tonal"
            prepend-icon="mdi-plus"
            :disabled="!core.name || core.exists || busy"
            :loading="busy === 'create'"
            @click="createDialog = true"
          >
            Create core
          </v-btn>
          <v-btn
            variant="tonal"
            prepend-icon="mdi-reload"
            :disabled="!core.name || !core.exists || busy"
            :loading="busy === 'reload'"
            @click="reloadCore"
          >
            Reload
          </v-btn>
        </div>

        <v-alert v-if="core.message && !core.name" type="warning" variant="tonal" class="mb-3">
          {{ core.message }}
        </v-alert>

        <template v-if="core.name">
          <div class="d-flex align-center flex-wrap ga-2 mb-3">
            <span class="text-h6 solr-mono">{{ core.name }}</span>
            <v-chip size="small" :color="core.exists ? 'success' : 'error'" variant="tonal">
              {{ core.exists ? 'Exists' : 'Not found' }}
            </v-chip>
          </div>

          <v-row v-if="core.exists && core.status" dense>
            <v-col cols="12" sm="6" md="3">
              <div class="text-caption text-medium-emphasis">Instance dir</div>
              <div class="text-body-2 solr-mono">{{ core.status.instanceDir || '—' }}</div>
            </v-col>
            <v-col cols="12" sm="6" md="3">
              <div class="text-caption text-medium-emphasis">Config</div>
              <div class="text-body-2">{{ core.status.config || '—' }}</div>
            </v-col>
            <v-col cols="12" sm="6" md="3">
              <div class="text-caption text-medium-emphasis">Documents</div>
              <div class="text-body-2">
                {{
                  core.status.index?.numDocs != null
                    ? Number(core.status.index.numDocs).toLocaleString()
                    : '—'
                }}
              </div>
            </v-col>
            <v-col cols="12" sm="6" md="3">
              <div class="text-caption text-medium-emphasis">Index size</div>
              <div class="text-body-2">{{ core.status.index?.size || '—' }}</div>
            </v-col>
          </v-row>
        </template>
      </v-card-text>
    </v-card>

    <v-card v-if="results.length" elevation="1">
      <v-card-title class="solr-card-title">Results</v-card-title>
      <v-divider />
      <v-card-text class="pa-4">
        <v-alert
          v-for="(row, i) in results"
          :key="i"
          class="mb-2"
          :type="row.status === 'success' ? 'success' : 'error'"
          variant="tonal"
        >
          <strong>{{ row.operation }}</strong>
          <div class="text-body-2 mt-1">{{ row.message }}</div>
          <details v-if="row.details" class="mt-2">
            <summary class="text-caption">Details</summary>
            <pre class="solr-pre mt-2">{{ JSON.stringify(row.details, null, 2) }}</pre>
          </details>
        </v-alert>
      </v-card-text>
    </v-card>

    <v-dialog v-model="createDialog" max-width="480">
      <v-card>
        <v-card-title class="text-h6">Create core “{{ core.name }}”?</v-card-title>
        <v-card-text>
          Creates a new Solr core/collection with the default configuration for this name.
        </v-card-text>
        <v-divider />
        <v-card-actions class="pa-3">
          <v-spacer />
          <v-btn variant="text" @click="createDialog = false">Cancel</v-btn>
          <v-btn color="success" variant="flat" :loading="busy === 'create'" @click="createCore">
            Create
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useSolrOps } from '../composables/useSolrOps.js';

defineOptions({ name: 'SolrCorePage' });

const { core, refreshCore, showToast, api } = useSolrOps();

const createDialog = ref(false);
const busy = ref('');
const results = ref([]);

function pushResult(operation, status, message, details) {
  results.value.unshift({ operation, status, message, details });
}

async function createCore() {
  createDialog.value = false;
  if (!core.name) return;
  busy.value = 'create';
  try {
    const data = await api().coreCreate(core.name);
    pushResult('Create core', 'success', `Core “${core.name}” created`, data.result);
    showToast(`Core ${core.name} created`);
    await refreshCore();
  } catch (e) {
    pushResult('Create core', 'error', e.message, e.cause?.response?.data);
    showToast(e.message, 'error');
  } finally {
    busy.value = '';
  }
}

async function reloadCore() {
  if (!core.name) return;
  busy.value = 'reload';
  try {
    const data = await api().coreReload(core.name);
    pushResult('Reload core', 'success', `Core “${core.name}” reloaded`, data.result);
    showToast(`Core ${core.name} reloaded`);
    await refreshCore();
  } catch (e) {
    pushResult('Reload core', 'error', e.message, e.cause?.response?.data);
    showToast(e.message, 'error');
  } finally {
    busy.value = '';
  }
}

onMounted(refreshCore);
</script>
