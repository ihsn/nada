<template>
  <v-card class="admin-analytics-card" rounded="lg" elevation="1">
    <v-card-title>{{ t('analytics_recent_activity', 'Recent activity today') }}</v-card-title>
    <v-card-text>
      <v-row>
        <v-col cols="12" md="6">
          <div class="text-overline mb-1">{{ t('analytics_pageviews', 'Pageviews') }}</div>
          <v-table v-if="pageviews.length" density="compact" class="admin-analytics-recent-table">
            <tbody>
              <tr v-for="item in pageviews.slice(0, 5)" :key="item.id">
                <td class="admin-analytics-recent-time">{{ formatTimeOnly(item.ts, timezone) }}</td>
                <td class="admin-analytics-recent-detail">
                  <a
                    :href="adminStudyEditUrl(siteUrl, item)"
                    class="admin-analytics-recent-detail-link"
                  >
                    {{ item.study_title }}
                  </a>
                </td>
              </tr>
            </tbody>
          </v-table>
          <div v-else class="text-caption text-medium-emphasis">{{ t('analytics_no_views_today', 'No pageviews today') }}</div>
        </v-col>
        <v-col cols="12" md="6">
          <div class="text-overline mb-1">{{ t('analytics_downloads', 'Downloads') }}</div>
          <v-table v-if="downloads.length" density="compact" class="admin-analytics-recent-table">
            <tbody>
              <tr v-for="item in downloads.slice(0, 5)" :key="item.id">
                <td class="admin-analytics-recent-time">{{ formatTimeOnly(item.ts, timezone) }}</td>
                <td class="admin-analytics-recent-detail">
                  <div class="text-body-2">{{ item.file_name }}</div>
                  <a
                    :href="adminStudyEditUrl(siteUrl, item)"
                    class="text-caption text-medium-emphasis"
                  >
                    {{ item.study_title }}
                  </a>
                </td>
              </tr>
            </tbody>
          </v-table>
          <div v-else class="text-caption text-medium-emphasis">{{ t('analytics_no_downloads_today', 'No downloads today') }}</div>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useI18n } from '@/shared/composables/useI18n';
import { adminStudyEditUrl, formatTimeOnly } from '../../utils/formatters';

defineProps({
  pageviews: { type: Array, default: () => [] },
  downloads: { type: Array, default: () => [] },
  timezone: { type: String, default: '' },
});

defineOptions({ name: 'RecentActivityCard' });

const { siteUrl } = useAppConfig();
const { t } = useI18n();
</script>
