<template>
  <v-card class="admin-analytics-card" rounded="lg" elevation="1">
    <v-card-title>
      <v-icon start color="deep-orange" size="small">mdi-file-download</v-icon>
      Top downloaded files
      <v-spacer />
      <v-progress-circular v-if="loading" indeterminate size="18" width="2" color="deep-orange" />
    </v-card-title>
    <div class="text-caption text-medium-emphasis px-4 pb-1">{{ periodLabel }}</div>
    <v-card-text class="pt-0">
      <div v-if="!loading && items.length === 0" class="text-center text-medium-emphasis py-4">
        No file downloads this month yet
      </div>
      <v-list v-else density="compact">
        <v-list-item v-for="(item, index) in items" :key="`${item.study_id}-${item.file_name}`">
          <template #prepend>
            <v-avatar size="24" color="deep-orange" class="text-caption">{{ index + 1 }}</v-avatar>
          </template>
          <v-list-item-title class="text-body-2 text-truncate" :title="item.file_name">
            {{ item.file_name }}
          </v-list-item-title>
          <v-list-item-subtitle>
            {{ item.downloads }} downloads ·
            <a :href="catalogStudyUrl(siteUrl, item)" target="_blank" rel="noopener">
              {{ item.title || `Study ${item.study_id}` }}
            </a>
          </v-list-item-subtitle>
        </v-list-item>
      </v-list>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { catalogStudyUrl } from '../../utils/formatters';

defineProps({
  items: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  periodLabel: { type: String, default: '' },
});

defineOptions({ name: 'TopFilesCard' });

const { siteUrl } = useAppConfig();
</script>
