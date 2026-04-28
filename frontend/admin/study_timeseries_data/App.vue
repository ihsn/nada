<template>
  <v-app>
    <v-main>
      <v-container fluid>
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
