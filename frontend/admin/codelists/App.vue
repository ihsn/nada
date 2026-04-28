<template>
  <v-app>
    <v-main>
      <v-container fluid>
        <header class="cl-page-header d-flex align-center justify-space-between flex-wrap gap-3 flex-shrink-0 mb-5">
          <h1 class="text-h4 font-weight-semibold text-high-emphasis mb-0">Codelists</h1>
          <v-btn
            v-if="route.name === 'codelists'"
            color="primary"
            size="small"
            prepend-icon="mdi-plus"
            class="flex-shrink-0"
            @click="openAddCodelist"
          >
            Add codelist
          </v-btn>
        </header>

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
import { useRoute, useRouter } from 'vue-router';

defineOptions({ name: 'AdminCodelistsApp' });

const route = useRoute();
const router = useRouter();

const message = reactive({ text: '', type: 'info' });

function openAddCodelist() {
  router.push({
    name: 'codelists',
    query: { ...route.query, openCreate: String(Date.now()) },
  });
}

function setMessage(text, type = 'info') {
  message.text = text;
  message.type = type;
}

provide('setMessage', setMessage);
</script>
