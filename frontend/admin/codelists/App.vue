<template>
  <v-app class="admin-codelists-app">
    <v-main class="admin-codelists-page">
      <v-container fluid class="px-4 pt-2 pb-6">
        <v-alert
          v-if="message.text"
          :type="message.type"
          closable
          class="mb-4"
          @click:close="message.text = ''"
        >
          {{ message.text }}
        </v-alert>
        <router-view />
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { reactive, provide } from 'vue';

defineOptions({ name: 'AdminCodelistsApp' });

const message = reactive({ text: '', type: 'info' });

function setMessage(text, type = 'info') {
  message.text = text;
  message.type = type;
}

provide('setMessage', setMessage);
</script>

<style>
.admin-codelists-app,
.admin-codelists-page {
  background-color: #f0f2f5;
}
</style>
