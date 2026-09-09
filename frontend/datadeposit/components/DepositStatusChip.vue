<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: { type: String, default: '' },
});

const normalized = computed(() => String(props.status || '').trim().toLowerCase());

const color = computed(() => {
  const map = {
    draft: 'default',
    submitted: 'warning',
    accepted: 'success',
    processed: 'info',
    closed: 'secondary',
  };
  return map[normalized.value] || 'default';
});
</script>

<template>
  <v-chip
    v-if="normalized"
    size="small"
    variant="tonal"
    :color="color === 'default' ? undefined : color"
    class="dd-status-chip text-capitalize"
  >
    {{ normalized }}
  </v-chip>
</template>
