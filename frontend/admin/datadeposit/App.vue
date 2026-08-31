<template>
  <v-app :class="['admin-datadeposit-app', { 'admin-datadeposit-app--print': isPrintPage }]">
    <GlobalDialog />
    <v-main class="admin-datadeposit-page">
      <v-container fluid :class="isPrintPage ? 'pa-4' : 'px-4 pt-2 pb-6'">
        <router-view />
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import GlobalDialog from '@/admin/catalog/components/GlobalDialog.vue';

defineOptions({ name: 'AdminDatadepositApp' });

const route = useRoute();
const isPrintPage = computed(() => route.name === 'admin-deposit-summary');
</script>

<style>
.admin-datadeposit-app,
.admin-datadeposit-page {
  background-color: #f0f2f5;
}
.admin-datadeposit-app--print,
.admin-datadeposit-app--print .admin-datadeposit-page {
  background-color: #fff;
}

@media print {
  .admin-datadeposit-app,
  .admin-datadeposit-app--print,
  .admin-datadeposit-app--print .admin-datadeposit-page {
    background: #fff !important;
  }
}
</style>
