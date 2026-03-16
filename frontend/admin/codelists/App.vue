<template>
  <v-app>
    <v-main>
      <v-container fluid>
        <h1 class="text-h5 mb-4">Codelists</h1>

        <v-alert v-if="message.text" :type="message.type" closable class="mb-4" @click:close="message.text = ''">
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
