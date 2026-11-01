<template>
  <div class="text-center mt-3">
    <v-pagination v-model="pageModel" :length="pageCount" :total-visible="7" density="comfortable" />
    <div class="text-caption text-medium-emphasis mt-2">{{ rangeLabel }}</div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';

const props = defineProps({
  page: { type: Number, required: true },
  limit: { type: Number, required: true },
  total: { type: Number, required: true },
});

const emit = defineEmits(['update:page']);

defineOptions({ name: 'PaginatedTableFooter' });

const { t } = useI18n();
const pageCount = computed(() => Math.max(1, Math.ceil(props.total / props.limit)));

const pageModel = computed({
  get: () => props.page,
  set: (v) => emit('update:page', v),
});

const rangeLabel = computed(() => {
  if (props.total === 0) return t('analytics_no_items', 'No items');
  const start = (props.page - 1) * props.limit + 1;
  const end = Math.min(props.page * props.limit, props.total);
  return t('analytics_showing_items', 'Showing %s–%s of %s', start, end, props.total);
});
</script>
