<template>
  <div>
    <v-divider />
    <div class="pa-3 d-flex align-center justify-space-between flex-wrap gap-2">
      <div class="text-caption text-medium-emphasis">
        Showing {{ start }}–{{ end }} of {{ total }} tables
      </div>
      <div class="d-flex align-center gap-3">
        <v-select
          :model-value="itemsPerPage"
          hide-details
          density="compact"
          variant="outlined"
          style="max-width: 88px"
          :items="[15, 30, 50]"
          @update:model-value="$emit('update:items-per-page', $event)"
        />
        <v-pagination
          :model-value="page"
          :length="totalPages"
          :total-visible="7"
          size="small"
          @update:model-value="$emit('update:page', $event)"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  start: { type: Number, required: true },
  end: { type: Number, required: true },
  total: { type: Number, required: true },
  page: { type: Number, required: true },
  totalPages: { type: Number, required: true },
  itemsPerPage: { type: Number, required: true },
});

defineEmits(['update:page', 'update:items-per-page']);
</script>
