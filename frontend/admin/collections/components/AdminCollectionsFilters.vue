<template>
  <v-btn-toggle
    :model-value="published"
    density="compact"
    variant="outlined"
    divided
    class="mb-3"
    @update:model-value="$emit('filter-change', $event ?? '')"
  >
    <v-btn value="">All</v-btn>
    <v-btn value="1">Published</v-btn>
    <v-btn value="0">Unpublished</v-btn>
  </v-btn-toggle>
</template>

<script setup>
defineOptions({ name: 'AdminCollectionsFilters' });

defineProps({
  published: { type: String, default: '' },
});

defineEmits(['filter-change']);
</script>
