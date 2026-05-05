<template>
  <v-app :class="{ 'catalog-indicator-app--embed': indicatorEmbed }">
    <v-main class="catalog-indicator-main" :class="{ 'catalog-indicator-main--embed pa-0': indicatorEmbed }">
      <v-container fluid class="catalog-indicator-container" :class="{ 'catalog-indicator-container--embed pa-0': indicatorEmbed }">
        <v-alert
          v-if="message.text"
          :type="message.type"
          closable
          :class="indicatorEmbed ? 'mb-2 mx-2 mt-2 rounded-lg' : 'mb-4'"
          @click:close="message.text = ''"
        >
          {{ message.text }}
        </v-alert>

        <CatalogIndicatorDataPage />
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { reactive, provide, computed } from 'vue';
import CatalogIndicatorDataPage from './pages/CatalogIndicatorDataPage.vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';

defineOptions({ name: 'CatalogStudyIndicatorDataApp' });

const { config } = useAppConfig();
const indicatorEmbed = computed(() => !!config.value?.indicatorEmbed);

const message = reactive({ text: '', type: 'info' });

function setMessage(text, type = 'info') {
  message.text = text;
  message.type = type;
}

provide('setMessage', setMessage);
</script>
