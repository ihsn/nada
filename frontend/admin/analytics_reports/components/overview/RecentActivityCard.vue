<template>
  <v-card class="admin-analytics-card" rounded="lg" elevation="1">
    <v-card-title>Recent activity today</v-card-title>
    <v-card-text>
      <v-row>
        <v-col cols="12" md="6">
          <div class="text-overline mb-1">Pageviews</div>
          <v-table v-if="pageviews.length" density="compact" class="admin-analytics-recent-table">
            <tbody>
              <tr v-for="item in pageviews.slice(0, 5)" :key="item.id">
                <td class="admin-analytics-recent-time">{{ formatTimeOnly(item.ts, timezone) }}</td>
                <td class="admin-analytics-recent-detail">
                  <a
                    :href="catalogStudyUrl(siteUrl, item)"
                    target="_blank"
                    rel="noopener"
                    class="admin-analytics-recent-detail-link"
                  >
                    {{ item.study_title }}
                  </a>
                </td>
              </tr>
            </tbody>
          </v-table>
          <div v-else class="text-caption text-medium-emphasis">No pageviews today</div>
        </v-col>
        <v-col cols="12" md="6">
          <div class="text-overline mb-1">Downloads</div>
          <v-table v-if="downloads.length" density="compact" class="admin-analytics-recent-table">
            <tbody>
              <tr v-for="item in downloads.slice(0, 5)" :key="item.id">
                <td class="admin-analytics-recent-time">{{ formatTimeOnly(item.ts, timezone) }}</td>
                <td class="admin-analytics-recent-detail">
                  <div class="text-body-2">{{ item.file_name }}</div>
                  <a
                    :href="catalogStudyUrl(siteUrl, item)"
                    target="_blank"
                    rel="noopener"
                    class="text-caption text-medium-emphasis"
                  >
                    {{ item.study_title }}
                  </a>
                </td>
              </tr>
            </tbody>
          </v-table>
          <div v-else class="text-caption text-medium-emphasis">No downloads today</div>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { catalogStudyUrl, formatTimeOnly } from '../../utils/formatters';

defineProps({
  pageviews: { type: Array, default: () => [] },
  downloads: { type: Array, default: () => [] },
  timezone: { type: String, default: '' },
});

defineOptions({ name: 'RecentActivityCard' });

const { siteUrl } = useAppConfig();
</script>
