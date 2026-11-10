<template>
  <v-app class="admin-dbmig-app">
    <v-main class="admin-dbmig-page">
      <v-container fluid class="px-4 pt-2 pb-6">
        <v-breadcrumbs :items="breadcrumbItems" class="dbmig-breadcrumbs px-0 pt-0">
          <template #divider>
            <v-icon icon="mdi-chevron-right" size="16" />
          </template>
        </v-breadcrumbs>

        <div class="d-flex flex-wrap align-start justify-space-between ga-3 mb-5">
          <div>
            <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-2">
              Database Migrations
            </h1>
            <p class="text-body-2 text-medium-emphasis mb-0">
              The stored version is a watermark: all migrations up to and including it are applied.
              Web UI runs one migration per request.
            </p>
          </div>
          <v-btn
            color="primary"
            prepend-icon="mdi-play"
            :disabled="!canRunNext"
            :loading="busy"
            @click="confirmRun(status.next_pending_version || 'latest')"
          >
            Run Next Migration
          </v-btn>
        </div>

        <div class="dbmig-alerts">
          <v-alert v-if="loadError" type="error" closable @click:close="loadError = ''">
            {{ loadError }}
          </v-alert>

          <v-alert
            v-if="status && !status.migration_enabled"
            type="warning"
            variant="tonal"
          >
            <strong>Migrations are disabled.</strong>
            Enable <code>$config['migration_enabled'] = TRUE</code> in
            <code>application/config/migration.php</code>, then re-disable after upgrading.
          </v-alert>

          <v-alert
            v-if="status?.db_debug_enabled"
            type="warning"
            variant="tonal"
          >
            <strong>Security Warning: Database debug mode is ENABLED</strong>
            It will be automatically disabled during migration execution, but for production
            set <code>$db['default']['db_debug'] = FALSE</code>.
          </v-alert>

          <v-alert type="info" variant="tonal" class="dbmig-backup-note">
            Migrations are one-way. Back up the database first. Mark as applied / Unmark only change
            the tracked watermark — they do not run SQL. Prefer
            <code>php index.php cli/migrate latest</code> for a full catch-up.
          </v-alert>
        </div>

        <v-row class="dbmig-metrics" dense>
          <v-col cols="12" md="4">
            <v-card elevation="1">
              <v-card-text class="pa-4">
                <div class="text-caption text-medium-emphasis mb-2">Current version</div>
                <div class="text-h6 font-weight-medium font-mono">
                  {{ status?.current_version || '—' }}
                </div>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12" md="4">
            <v-card elevation="1">
              <v-card-text class="pa-4">
                <div class="text-caption text-medium-emphasis mb-2">Pending</div>
                <div class="text-h6 font-weight-medium">
                  {{ status?.pending_count ?? '—' }}
                  <span v-if="status?.next_pending_version" class="text-body-2 text-medium-emphasis">
                    (next {{ status.next_pending_version }})
                  </span>
                </div>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12" md="4">
            <v-card elevation="1">
              <v-card-text class="pa-4">
                <div class="text-caption text-medium-emphasis mb-2">Migrations enabled</div>
                <div class="text-h6 font-weight-medium">
                  {{ status?.migration_enabled ? 'Yes' : 'No' }}
                </div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>

        <v-card elevation="1" class="mb-5">
          <div class="d-flex align-center ga-3 px-4 py-4 flex-wrap">
            <v-text-field
              v-model="search"
              prepend-inner-icon="mdi-magnify"
              placeholder="Search version or name"
              variant="outlined"
              density="compact"
              hide-details
              clearable
              style="max-width: 320px"
            />
            <v-spacer />
            <v-btn variant="text" prepend-icon="mdi-refresh" :loading="loading" @click="load">
              Refresh
            </v-btn>
          </div>
          <v-divider />
          <v-data-table
            :headers="headers"
            :items="migrations"
            :search="search"
            :loading="loading"
            :items-per-page="50"
            :items-per-page-options="[25, 50, 100, -1]"
            density="comfortable"
            class="dbmig-table text-body-2"
            item-value="version"
          >
            <template #item.version="{ item }">
              <code>{{ item.version }}</code>
            </template>
            <template #item.file="{ item }">
              <span class="text-caption text-medium-emphasis">{{ item.file }}</span>
            </template>
            <template #item.status="{ item }">
              <v-chip size="small" :color="statusColor(item.status)" variant="tonal">
                {{ statusLabel(item.status) }}
              </v-chip>
            </template>
            <template #item.actions="{ item }">
              <div class="dbmig-actions">
                <template v-if="item.status === 'pending'">
                  <v-btn
                    v-if="item.version === status?.next_pending_version && canRunNext"
                    size="small"
                    color="primary"
                    variant="flat"
                    :loading="busy"
                    @click="confirmRun(item.version)"
                  >
                    Run
                  </v-btn>
                  <v-btn v-else size="small" variant="flat" disabled>Run</v-btn>
                  <v-btn
                    size="small"
                    variant="outlined"
                    :disabled="busy"
                    @click="confirmMark(item.version)"
                  >
                    Mark as applied
                  </v-btn>
                </template>
                <v-btn
                  v-else
                  size="small"
                  color="warning"
                  variant="outlined"
                  :disabled="busy"
                  @click="confirmUnmark(item.version)"
                >
                  Unmark
                </v-btn>
              </div>
            </template>
            <template #no-data>
              <div class="text-medium-emphasis py-6">No migration files found</div>
            </template>
          </v-data-table>
        </v-card>

        <v-card elevation="1">
          <v-card-title class="text-subtitle-1 px-4 pt-4 pb-2">Command line</v-card-title>
          <v-card-text class="px-4 pb-4">
            <pre class="dbmig-cli mb-3">php index.php cli/migrate current
php index.php cli/migrate list_migrations
php index.php cli/migrate latest
php index.php cli/migrate version {{ exampleVersion }}
php index.php cli/migrate set_version {{ exampleVersion }}</pre>
            <p class="text-caption text-medium-emphasis mb-0">
              CLI <code>latest</code> runs all pending migrations in one session. The web UI advances
              one version per click.
            </p>
          </v-card-text>
        </v-card>

        <v-dialog v-model="confirm.open" max-width="520">
          <v-card>
            <v-card-title class="text-h6">{{ confirm.title }}</v-card-title>
            <v-card-text style="white-space: pre-wrap">{{ confirm.body }}</v-card-text>
            <v-divider />
            <v-card-actions class="pa-3">
              <v-spacer />
              <v-btn variant="text" @click="confirm.open = false">Cancel</v-btn>
              <v-btn
                :color="confirm.color"
                variant="flat"
                :loading="busy"
                @click="confirm.action"
              >
                {{ confirm.okLabel }}
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-dialog>

        <v-dialog v-model="result.open" max-width="900" scrollable>
          <v-card>
            <v-card-title class="text-h6 px-5 pt-5 pb-2">Migration output</v-card-title>
            <v-card-subtitle class="px-5 pb-4">
              <template v-if="result.version === 'latest' && result.version_run">
                Requested latest (ran {{ result.version_run }})
              </template>
              <template v-else>Version {{ result.version_run || result.version }}</template>
              <span v-if="result.before_version || result.after_version">
                · {{ result.before_version }} → {{ result.after_version }}
              </span>
            </v-card-subtitle>
            <v-card-text class="px-5 pb-5">
              <div class="dbmig-result-sections">
                <v-alert
                  :type="result.success ? 'success' : 'error'"
                  variant="tonal"
                  density="comfortable"
                >
                  {{ result.message }}
                  <div v-if="result.success && result.next_pending_version" class="mt-2">
                    Next pending: <code>{{ result.next_pending_version }}</code>
                  </div>
                </v-alert>
                <pre v-if="result.output" class="dbmig-output">{{ result.output }}</pre>
                <v-alert v-else type="info" variant="tonal" density="comfortable">
                  No output was captured from the migration.
                </v-alert>
              </div>
            </v-card-text>
            <v-divider />
            <v-card-actions class="pa-4 ga-2">
              <v-spacer />
              <v-btn
                v-if="result.success && result.next_pending_version && canRunNext"
                color="success"
                variant="flat"
                :loading="busy"
                @click="confirmRun(result.next_pending_version)"
              >
                Run Next ({{ result.next_pending_version }})
              </v-btn>
              <v-btn variant="text" @click="result.open = false">Close</v-btn>
            </v-card-actions>
          </v-card>
        </v-dialog>

        <v-snackbar v-model="toast.open" :color="toast.color" location="bottom right" :timeout="4500">
          {{ toast.message }}
        </v-snackbar>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useDatabaseMigrationApi } from './composables/useDatabaseMigrationApi';

defineOptions({ name: 'AdminDatabaseMigrationApp' });

const { config } = useAppConfig();
const api = useDatabaseMigrationApi();

const loading = ref(false);
const busy = ref(false);
const loadError = ref('');
const search = ref('');
const status = ref(null);

const toast = reactive({ open: false, message: '', color: 'success' });
const confirm = reactive({
  open: false,
  title: '',
  body: '',
  color: 'primary',
  okLabel: 'Continue',
  action: () => {},
});
const result = reactive({
  open: false,
  success: false,
  message: '',
  version: '',
  version_run: '',
  before_version: '',
  after_version: '',
  next_pending_version: null,
  output: '',
});

const breadcrumbItems = computed(() => [
  { title: 'Home', href: config.value.siteUrl || '/' },
  { title: 'Database Migrations', disabled: true },
]);

const headers = [
  { title: 'Version', key: 'version' },
  { title: 'Name', key: 'name' },
  { title: 'File', key: 'file', sortable: false },
  { title: 'Status', key: 'status' },
  { title: 'Actions', key: 'actions', sortable: false, width: '220px' },
];

const migrations = computed(() => status.value?.migrations || []);
const canRunNext = computed(
  () => !!status.value?.migration_enabled && !!status.value?.next_pending_version
);
const exampleVersion = computed(() => {
  const list = migrations.value;
  return list.length ? list[list.length - 1].version : 'YYYYMMDDHHIISS';
});

function statusColor(s) {
  if (s === 'current') return 'success';
  if (s === 'pending') return 'warning';
  return 'default';
}

function statusLabel(s) {
  if (s === 'current') return 'Current';
  if (s === 'pending') return 'Pending';
  return 'Applied';
}

function showToast(message, color = 'success') {
  toast.message = message;
  toast.color = color;
  toast.open = true;
}

function applyStatus(data) {
  status.value = {
    migration_enabled: !!data.migration_enabled,
    db_debug_enabled: !!data.db_debug_enabled,
    current_version: data.current_version || '0',
    next_pending_version: data.next_pending_version || null,
    pending_count: data.pending_count || 0,
    migrations: data.migrations || [],
  };
}

async function load() {
  loading.value = true;
  loadError.value = '';
  try {
    applyStatus(await api.getStatus());
  } catch (e) {
    loadError.value = e.message;
  } finally {
    loading.value = false;
  }
}

function openConfirm({ title, body, color, okLabel, action }) {
  confirm.title = title;
  confirm.body = body;
  confirm.color = color || 'primary';
  confirm.okLabel = okLabel || 'Continue';
  confirm.action = action;
  confirm.open = true;
}

function confirmRun(version) {
  result.open = false;
  openConfirm({
    title: 'Run migration',
    body:
      `Run migration ${version}?\n\n` +
      'This executes SQL/PHP and cannot be undone.\n' +
      'Only one migration runs per request. Make sure you have a database backup.',
    color: 'primary',
    okLabel: 'Run',
    action: () => doRun(version),
  });
}

function confirmMark(version) {
  openConfirm({
    title: 'Mark as applied',
    body:
      `Mark migrations as applied through version ${version}?\n\n` +
      'This does NOT run any SQL or PHP.\n' +
      'Only use this if you have already applied these migrations manually.',
    color: 'secondary',
    okLabel: 'Mark as applied',
    action: () => doMark(version),
  });
}

function confirmUnmark(version) {
  openConfirm({
    title: 'Unmark migration',
    body:
      `Unmark from version ${version}?\n\n` +
      'This does NOT change schema or data.\n' +
      'It only lowers the tracked version so this migration and later ones become pending again.\n' +
      'Re-running may fail or duplicate data unless migrations are idempotent.',
    color: 'warning',
    okLabel: 'Unmark',
    action: () => doUnmark(version),
  });
}

function showResult(data, fallbackMessage) {
  const r = data.result || {};
  result.success = !!r.success || data.status === 'success';
  result.message = data.message || r.msg || fallbackMessage;
  result.version = r.version || '';
  result.version_run = r.version_run || '';
  result.before_version = r.before_version || '';
  result.after_version = r.after_version || '';
  result.next_pending_version = r.next_pending_version || data.next_pending_version || null;
  result.output = r.output || '';
  result.open = true;
  applyStatus(data);
}

async function doRun(version) {
  confirm.open = false;
  busy.value = true;
  loadError.value = '';
  try {
    const data = await api.run(version);
    showResult(data, 'Migration completed successfully.');
    showToast(data.message || 'Migration completed');
  } catch (e) {
    if (e.payload) {
      showResult(e.payload, e.message);
    } else {
      loadError.value = e.message;
    }
    showToast(e.message, 'error');
  } finally {
    busy.value = false;
  }
}

async function doMark(version) {
  confirm.open = false;
  busy.value = true;
  try {
    const data = await api.markApplied(version);
    applyStatus(data);
    showToast(data.message || 'Marked as applied');
  } catch (e) {
    if (e.payload) applyStatus(e.payload);
    showToast(e.message, 'error');
  } finally {
    busy.value = false;
  }
}

async function doUnmark(version) {
  confirm.open = false;
  busy.value = true;
  try {
    const data = await api.unmark(version);
    applyStatus(data);
    showToast(data.message || 'Unmarked');
  } catch (e) {
    if (e.payload) applyStatus(e.payload);
    showToast(e.message, 'error');
  } finally {
    busy.value = false;
  }
}

onMounted(load);
</script>

<style>
.admin-dbmig-app,
.admin-dbmig-page {
  background-color: #f0f2f5;
}

.dbmig-breadcrumbs {
  font-size: 0.8125rem;
  margin-bottom: 0.75rem;
}

.dbmig-breadcrumbs .v-breadcrumbs-item,
.dbmig-breadcrumbs .v-breadcrumbs-divider {
  opacity: 0.7;
}

.dbmig-alerts {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.dbmig-backup-note {
  margin-bottom: 1.5rem !important;
}

.dbmig-metrics {
  margin-bottom: 1.75rem !important;
}

.dbmig-actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
}

.dbmig-result-sections {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.dbmig-table th {
  font-size: 0.75rem !important;
  font-weight: 600 !important;
  letter-spacing: 0.02em;
  color: rgba(var(--v-theme-on-surface), 0.6) !important;
  white-space: nowrap;
}

.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.95rem;
}

.dbmig-cli,
.dbmig-output {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.8125rem;
  line-height: 1.5;
  background: rgba(var(--v-theme-on-surface), 0.04);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  border-radius: 6px;
  padding: 12px;
  white-space: pre-wrap;
  word-break: break-word;
  margin: 0;
}

.dbmig-output {
  max-height: 420px;
  overflow: auto;
}
</style>
