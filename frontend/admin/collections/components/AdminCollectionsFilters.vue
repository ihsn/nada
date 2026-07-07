<template>
  <div class="published-filter">
    <v-btn-toggle
      :model-value="published"
      color="primary"
      density="compact"
      variant="outlined"
      divided
      mandatory
      class="published-filter-toggle"
      @update:model-value="$emit('filter-change', $event ?? '')"
    >
      <v-btn value="" size="small">All</v-btn>
      <v-btn value="1" size="small">Published</v-btn>
      <v-btn value="0" size="small">Draft</v-btn>
    </v-btn-toggle>
  </div>
</template>

<script setup>
defineOptions({ name: 'AdminCollectionsFilters' });

defineProps({
  published: { type: String, default: '' },
});

defineEmits(['filter-change']);
</script>

<style scoped>
.published-filter-toggle {
  flex-wrap: wrap;
}

.published-filter-toggle :deep(.v-btn) {
  text-transform: none;
  letter-spacing: normal;
}
</style>
