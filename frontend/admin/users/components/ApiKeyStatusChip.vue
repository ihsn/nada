<template>
  <span class="d-inline-flex ga-1">
    <v-chip size="x-small" :color="color" variant="flat">{{ label }}</v-chip>
    <v-chip v-if="row.is_legacy" size="x-small" variant="outlined">{{ t('legacy', 'Legacy') }}</v-chip>
  </span>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';

defineOptions({ name: 'ApiKeyStatusChip' });

const props = defineProps({
  row: { type: Object, required: true },
});
const { t } = useI18n();

const label = computed(() => {
  if (props.row.status === 'revoked') return t('revoked', 'Revoked');
  if (props.row.status === 'expired') return t('expired', 'Expired');
  return t('active', 'Active');
});
const color = computed(() => {
  if (props.row.status === 'revoked') return 'grey';
  if (props.row.status === 'expired') return 'warning';
  return 'success';
});
</script>
