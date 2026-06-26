<template>
  <v-tabs
    :model-value="published"
    color="primary"
    class="mb-4"
    @update:model-value="$emit('filter-change', $event ?? '')"
  >
    <v-tab value="">All</v-tab>
    <v-tab value="1">Published</v-tab>
    <v-tab value="0">Unpublished</v-tab>
  </v-tabs>
</template>

<script setup>
defineOptions({ name: 'AdminCollectionsFilters' });

defineProps({
  published: { type: String, default: '' },
});

defineEmits(['filter-change']);
</script>
