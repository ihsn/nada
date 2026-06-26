<template>
  <v-text-field
    v-model="query"
    prepend-inner-icon="mdi-magnify"
    placeholder="Search collections..."
    density="compact"
    variant="outlined"
    hide-details
    clearable
    @update:model-value="$emit('search', $event || '')"
    @click:clear="$emit('search', '')"
    @keyup.enter="$emit('submit')"
  />
</template>

<script setup>
import { ref } from 'vue';

defineOptions({ name: 'AdminCollectionsSearchBar' });

defineEmits(['search', 'submit']);

const query = ref('');
</script>
