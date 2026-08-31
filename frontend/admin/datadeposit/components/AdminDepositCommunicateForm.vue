<template>
  <v-card class="admin-catalog-surface pa-4" rounded="lg" elevation="1">
    <div class="text-subtitle-2 font-weight-bold text-high-emphasis mb-4">
      {{ t('compose_email', 'Compose email') }}
    </div>

    <div class="dd-comm-field">
      <div class="text-body-2 font-weight-bold text-high-emphasis mb-1">{{ t('to', 'To') }}</div>
      <v-text-field v-model="form.to" variant="outlined" density="comfortable" hide-details="auto" />
    </div>
    <div class="dd-comm-field">
      <div class="text-body-2 font-weight-bold text-high-emphasis mb-1">
        {{ t('cc', 'CC') }}
        <span class="text-medium-emphasis font-weight-regular">
          {{ t('use_comma_to_seperate_email', '(comma-separated)') }}
        </span>
      </div>
      <v-text-field v-model="form.cc" variant="outlined" density="comfortable" hide-details="auto" />
    </div>
    <div class="dd-comm-field">
      <div class="text-body-2 font-weight-bold text-high-emphasis mb-1">{{ t('subject', 'Subject') }}</div>
      <v-text-field v-model="form.subject" variant="outlined" density="comfortable" hide-details="auto" />
    </div>
    <div class="dd-comm-field">
      <div class="text-body-2 font-weight-bold text-high-emphasis mb-1">{{ t('body', 'Body') }}</div>
      <v-textarea
        v-model="form.body"
        rows="5"
        variant="outlined"
        hide-details="auto"
        :placeholder="t('dd_email_compose_placeholder', 'Your email message to the user…')"
      />
    </div>

    <v-alert v-if="saveMsg" :type="saveOk ? 'success' : 'error'" variant="tonal" density="compact" class="mb-4">
      {{ saveMsg }}
    </v-alert>

    <v-btn color="primary" :loading="saving" :disabled="!canEdit || !project?.id" @click="send">
      {{ t('send', 'Send') }}
    </v-btn>
  </v-card>
</template>

<script setup>
import { reactive, ref, watch } from 'vue';
import { useAdminDepositApi } from '../composables/useAdminDepositApi';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useI18n } from '@/shared/composables/useI18n';

const props = defineProps({
  project: { type: Object, default: () => ({}) },
});

defineOptions({ name: 'AdminDepositCommunicateForm' });

const { t } = useI18n();
const { canEdit } = useAppConfig();
const { sendProjectEmail } = useAdminDepositApi();

const saving = ref(false);
const saveMsg = ref('');
const saveOk = ref(false);

const form = reactive({
  to: '',
  cc: '',
  subject: '',
  body: '',
});

function joinEmails(value) {
  if (Array.isArray(value)) {
    return value.filter(Boolean).join(', ');
  }
  return String(value || '').trim();
}

function syncForm() {
  const title = props.project?.title || '';
  form.to = joinEmails(props.project?.owners);
  form.cc = joinEmails(props.project?.collaborators);
  form.subject = title ? `RE: [#${title}]` : '';
  form.body = '';
  saveMsg.value = '';
}

watch(
  () => props.project?.id,
  () => {
    syncForm();
  },
  { immediate: true }
);

async function send() {
  if (!props.project?.id) return;
  saving.value = true;
  saveMsg.value = '';
  try {
    const data = await sendProjectEmail(props.project.id, {
      to: form.to,
      cc: form.cc,
      subject: form.subject,
      body: form.body,
    });
    saveOk.value = true;
    saveMsg.value = data.message || t('email_sent', 'Email was sent!');
    form.body = '';
  } catch (e) {
    saveOk.value = false;
    saveMsg.value = e?.response?.data?.message || e?.message || t('dd_request_failed', 'Request failed');
  } finally {
    saving.value = false;
  }
}
</script>

<style scoped>
.dd-comm-field {
  margin-bottom: 16px;
}
</style>
