<template>
  <v-card class="admin-catalog-surface pa-4" rounded="lg" elevation="1">
    <div class="text-body-2 font-weight-bold mb-3">
      {{ t('request_status', 'Status') }}:
      <em>{{ statusLabel(savedStatus) }}</em>
    </div>

    <div class="dd-process-field">
      <div class="dd-process-label">{{ t('select_action', 'Select action') }}</div>
      <v-select
        v-model="form.status"
        :items="statusItems"
        item-title="title"
        item-value="value"
        variant="outlined"
        density="comfortable"
        hide-details
      />
    </div>

    <div class="dd-process-field">
      <div class="dd-process-label">
        {{ t('dd_assign_study_id', 'Assign study ID') }}
      </div>
      <v-text-field
        v-model="form.catalog_study_id"
        variant="outlined"
        density="comfortable"
        hide-details
      />
    </div>

    <div class="dd-process-field">
      <div class="dd-process-label">{{ t('comments', 'Comments') }}</div>
      <v-textarea v-model="form.admin_comments" rows="4" variant="outlined" hide-details="auto" />
      <div class="text-caption text-medium-emphasis mt-1">
        {{ t('comments_visible_to_users', 'Comments are visible to users') }}
      </div>
    </div>

    <div class="dd-process-field">
      <v-switch
        v-model="form.notify"
        color="primary"
        density="compact"
        hide-details
        inset
      >
        <template #label>
          <span>
            {{ t('notify_user_by_email', 'Notify user by email') }}
            <span v-if="recipientsLabel" class="text-medium-emphasis"> ({{ recipientsLabel }})</span>
          </span>
        </template>
      </v-switch>
    </div>

    <v-alert v-if="saveMsg" :type="saveOk ? 'success' : 'error'" variant="tonal" density="compact" class="mb-4">
      {{ saveMsg }}
    </v-alert>

    <v-btn color="primary" :loading="saving" :disabled="!canEdit" @click="save">
      {{ t('update', 'Update') }}
    </v-btn>
  </v-card>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { useAdminDepositApi } from '../composables/useAdminDepositApi';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useI18n } from '@/shared/composables/useI18n';

const props = defineProps({
  project: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['saved']);

defineOptions({ name: 'AdminDepositProcessForm' });

const { t } = useI18n();
const { canEdit } = useAppConfig();
const { processProject } = useAdminDepositApi();

const saving = ref(false);
const saveMsg = ref('');
const saveOk = ref(false);

const form = reactive({
  status: 'draft',
  catalog_study_id: '',
  admin_comments: '',
  notify: false,
});

const savedStatus = computed(() => String(props.project?.status || 'draft').toLowerCase());

const recipientsLabel = computed(() => {
  const rows = Array.isArray(props.project?.notify_recipients) ? props.project.notify_recipients : [];
  return rows.filter(Boolean).join(', ');
});

const statusItems = computed(() => {
  const current = savedStatus.value;
  const items = [];
  if (current === 'submitted') {
    items.push({ value: 'submitted', title: t('dd_submitted', 'Submitted') });
  }
  if (current !== 'processed') {
    items.push({ value: 'draft', title: t('draft', 'Draft') });
  }
  items.push({ value: 'accepted', title: t('dd_accepted', 'Accepted') });
  if (current !== 'draft') {
    items.push({ value: 'processed', title: t('dd_processed', 'Processed') });
  }
  items.push({ value: 'closed', title: t('dd_closed', 'Closed') });
  if (current !== 'draft') {
    items.push({ value: 'reopen', title: t('request_reopen', 'Reopen') });
  }
  return items;
});

function statusLabel(status) {
  const key = String(status || '').toLowerCase();
  const labels = {
    draft: t('draft', 'Draft'),
    submitted: t('dd_submitted', 'Submitted'),
    processed: t('dd_processed', 'Processed'),
    accepted: t('dd_accepted', 'Accepted'),
    closed: t('dd_closed', 'Closed'),
  };
  return labels[key] || (status ? String(status) : '');
}

function syncForm() {
  const current = savedStatus.value;
  const allowed = statusItems.value.map((item) => item.value);
  form.status = allowed.includes(current) ? current : current || 'draft';
  form.catalog_study_id = props.project?.catalog_study_id != null ? String(props.project.catalog_study_id) : '';
  form.admin_comments = props.project?.admin_comments != null ? String(props.project.admin_comments) : '';
  form.notify = false;
  saveMsg.value = '';
}

watch(
  () => props.project,
  () => {
    syncForm();
  },
  { immediate: true, deep: true }
);

async function save() {
  if (!props.project?.id) return;
  saving.value = true;
  saveMsg.value = '';
  try {
    const data = await processProject(props.project.id, {
      status: form.status,
      catalog_study_id: form.catalog_study_id,
      admin_comments: form.admin_comments,
      notify: !!form.notify,
    });
    saveOk.value = true;
    saveMsg.value = data.message || t('dd_process_updated', 'Project status updated successfully!');
    if (data.notified === false && form.notify) {
      saveMsg.value += ` ${t('dd_email_notify_failed', 'Notification email was not sent.')}`;
    }
    emit('saved', data.result || null);
  } catch (e) {
    saveOk.value = false;
    saveMsg.value = e?.response?.data?.message || e?.message || t('dd_request_failed', 'Request failed');
  } finally {
    saving.value = false;
  }
}
</script>

<style scoped>
.dd-process-field + .dd-process-field {
  margin-top: 16px;
}
.dd-process-label {
  font-size: 0.875rem;
  font-weight: 600;
  margin-bottom: 6px;
}
.dd-process-field {
  margin-bottom: 16px;
}
</style>
