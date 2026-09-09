<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { extractApiError } from '../composables/apiErrors';
import { useDepositApi } from '../composables/useDepositApi';
import DepositBreadcrumb from '../components/DepositBreadcrumb.vue';
import DepositStatusChip from '../components/DepositStatusChip.vue';

const MAX_EMAILS = 5;
const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

const props = defineProps({
  id: { type: [String, Number], default: '' },
});

const { config } = useAppConfig();
const route = useRoute();
const { fetchProject, emailSummary } = useDepositApi();

const loading = ref(true);
const saving = ref(false);
const loadError = ref('');
const sendError = ref('');
const sendErrors = ref([]);
const sent = ref(false);
const sentTo = ref([]);
const project = ref({});
const emails = ref(['']);

const lbl = computed(() => {
  const labels = config.value?.labels || {};
  return {
    myProjects: labels.myProjects || 'My projects',
    email: labels.email || 'Email',
    emailProject: labels.emailProject || 'Email project summary',
    emailTo: labels.emailTo || 'Email addresses',
    emailToHelp: labels.emailToHelp || 'Enter up to 5 email addresses.',
    emailWillSend: labels.emailWillSend || 'The current project summary will be included in the message.',
    emailSend: labels.emailSend || 'Send',
    emailSent: labels.emailSent || 'Email was sent successfully!',
    emailMax: labels.emailMax || 'You can send to at most 5 email addresses.',
    emailInvalid: labels.emailInvalid || 'Invalid email address!',
    provideEmail: labels.provideEmail || 'Please provide a valid email address to send the current project',
    add: labels.add || 'Add',
    cancel: labels.cancel || 'Cancel',
    viewSummary: labels.viewSummary || 'Summary',
    loadFailed: labels.loadFailed || 'Failed to load',
    saveFailed: labels.saveFailed || 'Save failed',
  };
});

const status = computed(() => String(project.value.status || config.value?.projectStatus || '').toLowerCase());

const fromSummary = computed(() => String(route.query.from || '') === 'summary');

const cancelTo = computed(() => {
  if (fromSummary.value && props.id) {
    return { name: 'summary', params: { id: String(props.id) } };
  }
  return { name: 'projects' };
});

const breadcrumbItems = computed(() => [
  { title: lbl.value.myProjects, to: { name: 'projects' } },
  {
    title: project.value.title || config.value?.projectTitle || lbl.value.emailProject,
    to: props.id ? { name: 'summary', params: { id: String(props.id) } } : null,
  },
  { title: lbl.value.email },
]);

const canAdd = computed(() => emails.value.length < MAX_EMAILS);

function addEmail() {
  if (!canAdd.value) return;
  emails.value.push('');
}

function removeEmail(index) {
  emails.value.splice(index, 1);
  if (!emails.value.length) {
    emails.value.push('');
  }
}

function collectedEmails() {
  const seen = new Set();
  const out = [];
  emails.value.forEach((row) => {
    String(row || '')
      .split(/[,;\s]+/)
      .map((part) => part.trim().toLowerCase())
      .filter(Boolean)
      .forEach((email) => {
        if (!seen.has(email)) {
          seen.add(email);
          out.push(email);
        }
      });
  });
  return out;
}

function validateEmails(list) {
  if (!list.length) {
    return lbl.value.provideEmail;
  }
  if (list.length > MAX_EMAILS) {
    return lbl.value.emailMax;
  }
  const bad = list.filter((email) => !EMAIL_RE.test(email));
  if (bad.length) {
    return `${lbl.value.emailInvalid} ${bad.join(', ')}`;
  }
  return '';
}

async function sendEmail() {
  sendError.value = '';
  sendErrors.value = [];
  const list = collectedEmails();
  const localError = validateEmails(list);
  if (localError) {
    sendError.value = localError;
    return;
  }
  saving.value = true;
  try {
    const data = await emailSummary(props.id, list);
    sent.value = true;
    sentTo.value = Array.isArray(data.emails) ? data.emails : list;
  } catch (e) {
    const extracted = extractApiError(e, lbl.value);
    sendError.value = extracted.message || lbl.value.saveFailed;
    sendErrors.value = extracted.errors || [];
  } finally {
    saving.value = false;
  }
}

async function load() {
  loading.value = true;
  loadError.value = '';
  try {
    const projectData = await fetchProject(props.id);
    project.value = projectData && typeof projectData === 'object' ? projectData : {};
  } catch (e) {
    const extracted = extractApiError(e, lbl.value);
    loadError.value = extracted.message || lbl.value.loadFailed;
  } finally {
    loading.value = false;
  }
}

onMounted(load);
</script>

<template>
  <v-app class="dd-email-app">
    <v-main class="dd-email-main">
      <div class="dd-email">
        <DepositBreadcrumb :items="breadcrumbItems" />

        <div v-if="loading" class="d-flex justify-center py-12">
          <v-progress-circular indeterminate color="primary" />
        </div>

        <v-alert v-else-if="loadError" type="error" variant="tonal" class="mb-4" density="compact">
          {{ loadError }}
        </v-alert>

        <template v-else>
          <div class="dd-email-title mb-6">
            <h1 class="text-h5 font-weight-medium mb-0">{{ lbl.emailProject }}</h1>
            <p class="text-body-1 mt-2 mb-1">{{ project.title || config.projectTitle }}</p>
            <DepositStatusChip :status="status" />
          </div>

          <v-alert v-if="sent" type="success" variant="tonal" class="mb-4" density="compact">
            <div>{{ lbl.emailSent }}</div>
            <div v-if="sentTo.length" class="text-body-2 mt-1">{{ sentTo.join(', ') }}</div>
          </v-alert>

          <v-alert v-else-if="sendError" type="error" variant="tonal" class="mb-4" density="compact" closable>
            <div>{{ sendError }}</div>
            <div v-for="(err, i) in sendErrors" :key="i" class="text-body-2 mt-1">
              {{ err.message }}
            </div>
          </v-alert>

          <template v-if="!sent">
            <p class="text-body-2 text-medium-emphasis mb-4">{{ lbl.emailWillSend }}</p>

            <div class="dd-info-field">
              <div class="d-flex align-start justify-space-between ga-2 mb-1">
                <div>
                  <div class="dd-info-label">{{ lbl.emailTo }} <span class="text-error">*</span></div>
                  <p class="text-caption text-medium-emphasis mb-0">{{ lbl.emailToHelp }}</p>
                </div>
                <v-btn
                  size="small"
                  variant="text"
                  class="text-none"
                  prepend-icon="mdi-plus"
                  :disabled="!canAdd"
                  @click="addEmail"
                >
                  {{ lbl.add }}
                </v-btn>
              </div>
              <div v-for="(_, i) in emails" :key="i" class="d-flex align-center ga-2 mb-2">
                <v-text-field
                  v-model="emails[i]"
                  variant="outlined"
                  density="comfortable"
                  hide-details
                  class="flex-grow-1"
                  autocomplete="email"
                />
                <v-btn
                  icon="mdi-close"
                  variant="text"
                  size="small"
                  :disabled="emails.length < 2"
                  @click="removeEmail(i)"
                />
              </div>
            </div>
          </template>

          <div class="dd-email-actions">
            <v-btn :to="cancelTo" variant="text" class="text-none" :disabled="saving">
              {{ lbl.cancel }}
            </v-btn>
            <v-btn
              v-if="id"
              :to="{ name: 'summary', params: { id: String(id) } }"
              variant="tonal"
              class="text-none"
              :disabled="saving"
            >
              {{ lbl.viewSummary }}
            </v-btn>
            <v-btn
              v-if="!sent"
              color="primary"
              class="text-none"
              :loading="saving"
              @click="sendEmail"
            >
              {{ lbl.emailSend }}
            </v-btn>
          </div>
        </template>
      </div>
    </v-main>
  </v-app>
</template>

<style scoped>
.dd-email-app.v-application {
  background: transparent;
  min-height: 0;
}
.dd-email-app :deep(.v-application__wrap) {
  min-height: 0 !important;
}
.dd-email-main {
  padding: 0;
  --v-layout-top: 0px;
}
.dd-email {
  width: 100%;
  max-width: 760px;
  margin: 0 auto 24px;
  padding: 8px 0 24px;
}
.dd-email-title {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}
.dd-info-field {
  margin-bottom: 20px;
}
.dd-info-label {
  display: block;
  margin-bottom: 4px;
  font-size: 0.875rem;
  font-weight: 500;
}
.dd-email-actions {
  display: flex;
  justify-content: flex-end;
  flex-wrap: wrap;
  gap: 8px;
  padding-top: 8px;
}
</style>
