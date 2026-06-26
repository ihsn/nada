<template>
  <v-app class="admin-data-structures-app">
    <v-main class="admin-data-structures-page">
      <v-container fluid class="px-4 pt-2 pb-6">
        <v-expand-transition>
          <v-alert
            v-if="message.text"
            :type="message.type"
            closable
            variant="tonal"
            density="comfortable"
            class="mb-4"
            @click:close="clearMessage"
          >
            <div>{{ message.text }}</div>
            <ul v-if="message.errors.length" class="pl-4 mb-0 mt-2">
              <li v-for="(err, i) in message.errors" :key="i">
                <span v-if="err.path" class="text-caption">{{ err.path }}:</span>
                {{ err.message }}
              </li>
            </ul>
          </v-alert>
        </v-expand-transition>
        <router-view />
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { reactive, provide } from 'vue';

defineOptions({ name: 'AdminDataStructuresApp' });

const message = reactive({ text: '', type: 'info', errors: [] });

function setMessage(text, type = 'info', options = {}) {
  message.text = text;
  message.type = type;
  message.errors = Array.isArray(options.errors) ? options.errors : [];
}

function clearMessage() {
  message.text = '';
  message.errors = [];
}

provide('setMessage', setMessage);
</script>

<style>
.admin-data-structures-app,
.admin-data-structures-page {
  background-color: #f0f2f5;
}
</style>
