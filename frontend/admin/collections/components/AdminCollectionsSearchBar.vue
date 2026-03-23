<template>
  <v-row align="center" class="mb-2">
    <v-col cols="12" sm="6" md="5">
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
      />
    </v-col>
    <v-col cols="auto" class="ml-auto d-flex gap-2">
      <v-btn
        variant="text"
        prepend-icon="mdi-shape-outline"
        :href="siteUrl + 'index.php/admin/repository_sections'"
      >
        Manage Sections
      </v-btn>
      <v-btn
        color="primary"
        prepend-icon="mdi-plus"
        :loading="loading"
        @click="$emit('new-collection')"
      >
        New Collection
      </v-btn>
    </v-col>
  </v-row>
</template>

<script setup>
import { ref } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';

defineOptions({ name: 'AdminCollectionsSearchBar' });

const { siteUrl } = useAppConfig();

defineProps({
  loading: { type: Boolean, default: false },
});

defineEmits(['search', 'new-collection']);

const query = ref('');
</script>
