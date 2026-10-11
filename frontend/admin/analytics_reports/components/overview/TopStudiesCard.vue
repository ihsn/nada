<template>
  <v-card class="admin-analytics-card" rounded="lg" elevation="1">
    <v-card-title>
      <v-icon start color="primary" size="small">mdi-podium</v-icon>
      Top studies
      <v-spacer />
      <v-progress-circular v-if="loading" indeterminate size="18" width="2" />
    </v-card-title>
    <div class="text-caption text-medium-emphasis px-4 pb-1">{{ periodLabel }}</div>
    <v-card-text class="pt-0">
      <div v-if="!loading && items.length === 0" class="text-center text-medium-emphasis py-4">
        No data for this month yet
      </div>
      <v-list v-else density="compact" class="admin-analytics-overview-list">
        <v-list-item v-for="(item, index) in items" :key="item.study_id">
          <template #prepend>
            <v-avatar size="24" color="primary" class="text-caption">{{ index + 1 }}</v-avatar>
          </template>
          <v-list-item-title>
            <a
              :href="adminStudyEditUrl(siteUrl, item)"
              class="admin-analytics-overview-list-link"
            >
              {{ item.title || `Study ${item.study_id}` }}
            </a>
          </v-list-item-title>
          <v-list-item-subtitle class="admin-analytics-overview-list-meta">
            {{ item.pageviews }} views · {{ item.downloads }} downloads
          </v-list-item-subtitle>
        </v-list-item>
      </v-list>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { adminStudyEditUrl } from '../../utils/formatters';

defineProps({
  items: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  periodLabel: { type: String, default: '' },
});

defineOptions({ name: 'TopStudiesCard' });

const { siteUrl } = useAppConfig();
</script>
