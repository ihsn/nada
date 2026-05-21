<template>
  <v-app class="admin-tables-app">
    <v-main class="admin-tables-page">
      <v-container fluid class="px-4 pt-2 pb-6">
        <v-expand-transition>
          <v-alert
            v-if="message.text"
            :type="message.type"
            closable
            variant="tonal"
            density="comfortable"
            class="mb-4"
            @click:close="message.text = ''"
          >
            {{ message.text }}
          </v-alert>
        </v-expand-transition>
        <router-view />
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { reactive, provide } from 'vue';

defineOptions({ name: 'AdminTablesApp' });

const message = reactive({ text: '', type: 'info' });

function setMessage(text, type = 'info') {
  message.text = text;
  message.type = type;
}

provide('setMessage', setMessage);
</script>

<style>
.admin-tables-app,
.admin-tables-page {
  background-color: #f0f2f5;
}
</style>
