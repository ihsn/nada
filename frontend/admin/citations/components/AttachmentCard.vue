<template>
  <v-card class="pa-4" elevation="1">
    <h2 class="cit-section-title">{{ t('attach_file', 'Attach file') }}</h2>

    <div v-if="attachment" class="d-flex align-center flex-wrap ga-2 mb-3">
      <v-icon icon="mdi-paperclip" />
      <a v-if="attachment.exists" class="cit-title-link" :href="api.attachmentUrl(citationId)">{{ attachment.name }}</a>
      <span v-else>{{ attachment.name }}</span>
      <span v-if="attachment.size != null" class="cit-muted">({{ sizeText(attachment.size) }})</span>
      <v-chip v-if="!attachment.exists" size="x-small" color="warning" variant="flat">{{ t('cit_file_missing', 'File is missing') }}</v-chip>
      <v-spacer />
      <v-btn size="small" variant="text" color="error" prepend-icon="mdi-delete" :disabled="busy" @click="removeFile">
        {{ t('delete', 'Delete') }}
      </v-btn>
    </div>
    <div v-else class="cit-muted mb-3">{{ t('cit_no_attachment', 'No file attached.') }}</div>

    <v-file-input
      v-model="file"
      :label="attachment ? t('cit_replace_file', 'Replace the file') : t('cit_choose_file', 'Choose a file')"
      density="compact"
      variant="outlined"
      prepend-icon=""
      prepend-inner-icon="mdi-paperclip"
      :accept="accept"
      :disabled="busy"
      :error-messages="error ? [error] : []"
      :hint="hint"
      persistent-hint
    />
    <div class="mt-2">
      <v-btn size="small" color="primary" variant="flat" prepend-icon="mdi-upload" :disabled="!file || busy" :loading="busy" @click="upload">
        {{ t('cit_upload', 'Upload') }}
      </v-btn>
      <v-progress-linear v-if="busy && progress" :model-value="progress" class="mt-2" />
    </div>
  </v-card>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import $dialog from '@/shared/composables/dialog';
import { useCitationsApi, apiErrorMessage } from '../composables/useCitationsApi';

defineOptions({ name: 'AttachmentCard' });

const props = defineProps({
  citationId: { type: Number, required: true },
  attachment: { type: Object, default: null },
  types: { type: Array, default: () => [] },
  maxMb: { type: Number, default: 0 },
});
const emit = defineEmits(['changed']);

const { t } = useI18n();
const api = useCitationsApi();

const file = ref(null);
const busy = ref(false);
const progress = ref(0);
const error = ref('');

const accept = computed(() => props.types.map((x) => `.${x}`).join(','));
const hint = computed(() => {
  const types = props.types.join(', ');
  const max = props.maxMb ? ` · ${t('cit_max_size', 'Max %d MB', props.maxMb)}` : '';
  return `${t('allowed_file_types', 'Allowed file types')}: ${types}${max}`;
});

function sizeText(bytes) {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / 1048576).toFixed(1)} MB`;
}

function pickedFile() {
  return Array.isArray(file.value) ? file.value[0] : file.value;
}

async function upload() {
  const f = pickedFile();
  if (!f) return;
  busy.value = true;
  error.value = '';
  progress.value = 0;
  try {
    const attachment = await api.uploadAttachment(props.citationId, f, (p) => {
      progress.value = p;
    });
    file.value = null;
    emit('changed', attachment);
  } catch (e) {
    error.value = apiErrorMessage(e, t);
  } finally {
    busy.value = false;
  }
}

async function removeFile() {
  const ok = await $dialog.confirm({
    title: t('delete', 'Delete'),
    message: t('cit_confirm_delete_file', 'Delete the attached file "%s"?', props.attachment?.name || ''),
    confirmText: t('delete', 'Delete'),
    cancelText: t('cancel', 'Cancel'),
  });
  if (!ok) return;
  busy.value = true;
  error.value = '';
  try {
    await api.deleteAttachment(props.citationId);
    emit('changed', null);
  } catch (e) {
    error.value = apiErrorMessage(e, t);
  } finally {
    busy.value = false;
  }
}
</script>
