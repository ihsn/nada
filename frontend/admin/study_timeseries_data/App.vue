<template>
  <v-app class="study-timeseries-vapp">
    <v-main class="study-timeseries-main">
      <v-container fluid class="study-timeseries-container">
        <v-alert
          v-if="message.text"
          :type="message.type"
          closable
          class="mb-4"
          @click:close="message.text = ''"
        >
          {{ message.text }}
        </v-alert>

        <StudyTimeseriesDataPage />
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { reactive, provide } from 'vue';
import StudyTimeseriesDataPage from './pages/StudyTimeseriesDataPage.vue';

defineOptions({ name: 'AdminStudyTimeseriesDataApp' });

const message = reactive({ text: '', type: 'info' });

function setMessage(text, type = 'info') {
  message.text = text;
  message.type = type;
}

provide('setMessage', setMessage);
</script>

<style scoped>
/* Full tab canvas white (flat cards leave v-card-item transparent on gray shell) */
.study-timeseries-vapp {
  background: #fff !important;
}

.study-timeseries-vapp :deep(.v-application__wrap) {
  background: #fff !important;
}

.study-timeseries-main {
  background: #fff !important;
}

.study-timeseries-container {
  background: #fff !important;
}
</style>
