<template>
  <div class="admin-analytics-kpi-row">
    <SevenDayChartCard :days="dailyDays" :loading="loading" :timezone="timezone" />
    <div class="admin-analytics-kpi-stack">
      <v-card class="admin-analytics-card admin-analytics-kpi-views" rounded="lg" elevation="1">
        <v-card-text class="py-3">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="admin-analytics-kpi-value">{{ fmtNum(today.pageviews) }}</div>
              <div class="text-caption text-medium-emphasis">Pageviews today</div>
            </div>
            <v-icon color="blue-lighten-3">mdi-eye</v-icon>
          </div>
        </v-card-text>
      </v-card>
      <v-card class="admin-analytics-card admin-analytics-kpi-downloads" rounded="lg" elevation="1">
        <v-card-text class="py-3">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="admin-analytics-kpi-value">{{ fmtNum(today.downloads) }}</div>
              <div class="text-caption text-medium-emphasis">Downloads today</div>
            </div>
            <v-icon color="orange-lighten-3">mdi-download</v-icon>
          </div>
        </v-card-text>
      </v-card>
      <v-card class="admin-analytics-card admin-analytics-kpi-views" rounded="lg" elevation="1">
        <v-card-text class="py-3">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="admin-analytics-kpi-value">
                <v-progress-circular v-if="loading" indeterminate size="20" width="2" />
                <span v-else>{{ fmtNum(monthStats.pageviews) }}</span>
              </div>
              <div class="text-caption text-medium-emphasis">Pageviews {{ monthLabel }}</div>
              <div v-if="!loading && prevMonth.pageviews > 0" class="text-caption mt-1">
                <span :class="monthStats.pageviews >= prevMonth.pageviews ? 'text-success' : 'text-error'">
                  vs {{ fmtNum(prevMonth.pageviews) }}
                </span>
              </div>
            </div>
            <v-icon color="blue-lighten-3">mdi-calendar-month</v-icon>
          </div>
        </v-card-text>
      </v-card>
      <v-card class="admin-analytics-card admin-analytics-kpi-downloads" rounded="lg" elevation="1">
        <v-card-text class="py-3">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="admin-analytics-kpi-value">
                <v-progress-circular v-if="loading" indeterminate size="20" width="2" color="deep-orange" />
                <span v-else>{{ fmtNum(monthStats.downloads) }}</span>
              </div>
              <div class="text-caption text-medium-emphasis">Downloads {{ monthLabel }}</div>
              <div v-if="!loading && prevMonth.downloads > 0" class="text-caption mt-1">
                <span :class="monthStats.downloads >= prevMonth.downloads ? 'text-success' : 'text-error'">
                  vs {{ fmtNum(prevMonth.downloads) }}
                </span>
              </div>
            </div>
            <v-icon color="orange-lighten-3">mdi-calendar-month</v-icon>
          </div>
        </v-card-text>
      </v-card>
    </div>
  </div>
</template>

<script setup>
import { fmtNum } from '../../utils/formatters';
import SevenDayChartCard from './SevenDayChartCard.vue';

defineProps({
  today: { type: Object, required: true },
  monthStats: { type: Object, required: true },
  prevMonth: { type: Object, required: true },
  monthLabel: { type: String, default: 'this month' },
  dailyDays: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  timezone: { type: String, default: '' },
});

defineOptions({ name: 'OverviewKpiRow' });
</script>
