<template>
  <v-app>
    <v-main class="ui-kit-page">
      <v-container fluid class="py-6 px-4 px-sm-6">
        <v-breadcrumbs :items="breadcrumbs" class="px-0 pt-0">
          <template #divider>
            <v-icon size="16">mdi-chevron-right</v-icon>
          </template>
        </v-breadcrumbs>

        <section class="mb-6">
          <div class="d-flex flex-wrap align-center justify-space-between ga-3">
            <div>
              <h1 class="text-h4 font-weight-bold mb-1">Admin UI Kit</h1>
              <p class="text-body-2 text-medium-emphasis mb-0">
                Reference page for admin patterns: headers, navigation, forms, tables, buttons, and alerts.
              </p>
            </div>
            <div class="d-flex ga-2">
              <v-btn variant="tonal" prepend-icon="mdi-content-save-outline">Secondary</v-btn>
              <v-btn color="primary" prepend-icon="mdi-content-save">Primary action</v-btn>
            </div>
          </div>
        </section>

        <v-row class="mb-2" dense>
          <v-col cols="12" md="6" lg="3">
            <v-card rounded="lg" variant="outlined">
              <v-card-title class="text-subtitle-1 font-weight-semibold">Heading Levels</v-card-title>
              <v-card-text>
                <div class="text-h4">H1 Display</div>
                <div class="text-h5">H2 Section</div>
                <div class="text-h6">H3 Subsection</div>
                <div class="text-subtitle-1">Subtitle</div>
                <div class="text-body-2 text-medium-emphasis">Helper text / metadata</div>
              </v-card-text>
            </v-card>
          </v-col>

          <v-col cols="12" md="6" lg="3">
            <v-card rounded="lg" variant="outlined">
              <v-card-title class="text-subtitle-1 font-weight-semibold">Buttons</v-card-title>
              <v-card-text class="d-flex flex-wrap ga-2">
                <v-btn color="primary">Primary</v-btn>
                <v-btn variant="tonal" color="primary">Tonal</v-btn>
                <v-btn variant="outlined">Outlined</v-btn>
                <v-btn variant="text">Text</v-btn>
                <v-btn color="error" variant="flat">Destructive</v-btn>
                <v-btn icon="mdi-refresh" aria-label="Refresh" />
              </v-card-text>
            </v-card>
          </v-col>

          <v-col cols="12" md="6" lg="3">
            <v-card rounded="lg" variant="outlined">
              <v-card-title class="text-subtitle-1 font-weight-semibold">Navigation Buttons</v-card-title>
              <v-card-text class="d-flex flex-wrap ga-2">
                <v-btn prepend-icon="mdi-arrow-left" variant="text">Back</v-btn>
                <v-btn append-icon="mdi-arrow-right" color="primary">Next</v-btn>
                <v-btn prepend-icon="mdi-home-outline" variant="outlined">Go to dashboard</v-btn>
              </v-card-text>
            </v-card>
          </v-col>

          <v-col cols="12" md="6" lg="3">
            <v-card rounded="lg" variant="outlined">
              <v-card-title class="text-subtitle-1 font-weight-semibold">Color Tokens</v-card-title>
              <v-card-text class="d-flex flex-column ga-2">
                <div v-for="token in colorTokens" :key="token.name" class="color-row">
                  <span class="color-chip" :style="{ backgroundColor: token.value }" />
                  <span class="text-caption">{{ token.name }} - {{ token.value }}</span>
                </div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>

        <v-card rounded="lg" class="mb-6" variant="outlined">
          <v-tabs v-model="activeTab" color="primary">
            <v-tab value="forms">Forms</v-tab>
            <v-tab value="tables">Tables</v-tab>
            <v-tab value="alerts">Alerts</v-tab>
          </v-tabs>
          <v-divider />
          <v-window v-model="activeTab">
            <v-window-item value="forms">
              <v-card-text>
                <v-row>
                  <v-col cols="12" md="6">
                    <div class="text-caption text-medium-emphasis mb-1">Title</div>
                    <v-text-field model-value="Household Survey 2025" variant="outlined" density="compact" hide-details />
                  </v-col>
                  <v-col cols="12" md="6">
                    <div class="text-caption text-medium-emphasis mb-1">Status</div>
                    <v-select :items="statusItems" model-value="Draft" variant="outlined" density="compact" hide-details />
                  </v-col>
                  <v-col cols="12">
                    <div class="text-caption text-medium-emphasis mb-1">Notes</div>
                    <v-textarea
                      model-value="Form fields use outlined + compact, with labels above controls."
                      variant="outlined"
                      density="compact"
                      rows="2"
                      auto-grow
                    />
                  </v-col>
                </v-row>
              </v-card-text>
            </v-window-item>

            <v-window-item value="tables">
              <v-card-text>
                <v-table density="comfortable" class="ui-kit-table">
                  <thead>
                    <tr>
                      <th class="text-left">IDNO</th>
                      <th class="text-left">Title</th>
                      <th class="text-left">Status</th>
                      <th class="text-left">Updated</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="row in tableRows" :key="row.idno">
                      <td>{{ row.idno }}</td>
                      <td>{{ row.title }}</td>
                      <td>
                        <v-chip size="small" :color="row.statusColor" variant="tonal">{{ row.status }}</v-chip>
                      </td>
                      <td>{{ row.updated }}</td>
                    </tr>
                  </tbody>
                </v-table>
              </v-card-text>
            </v-window-item>

            <v-window-item value="alerts">
              <v-card-text class="d-flex flex-column ga-3">
                <v-alert type="success" variant="tonal" density="comfortable">Settings saved successfully.</v-alert>
                <v-alert type="info" variant="tonal" density="comfortable">
                  Draft changes are only visible to admins.
                </v-alert>
                <v-alert type="warning" variant="tonal" density="comfortable">
                  Some records are missing required metadata.
                </v-alert>
                <v-alert type="error" variant="tonal" density="comfortable">
                  Save failed. Please retry or check server logs.
                </v-alert>
              </v-card-text>
            </v-window-item>
          </v-window>
        </v-card>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref } from 'vue';

defineOptions({ name: 'AdminUiKitApp' });

const breadcrumbs = [
  { title: 'Admin', href: '/admin' },
  { title: 'UI Kit', disabled: true },
];

const activeTab = ref('forms');

const statusItems = ['Draft', 'Review', 'Published', 'Archived'];

const tableRows = [
  { idno: 'NADA-001', title: 'Household Budget Survey', status: 'Draft', statusColor: 'info', updated: '2026-05-01' },
  { idno: 'NADA-002', title: 'Labor Force Survey', status: 'Published', statusColor: 'success', updated: '2026-04-22' },
  { idno: 'NADA-003', title: 'Agriculture Census', status: 'Archived', statusColor: 'warning', updated: '2026-03-17' },
];

const colorTokens = [
  { name: 'Primary', value: '#1976d2' },
  { name: 'Secondary', value: '#424242' },
  { name: 'Success', value: '#4CAF50' },
  { name: 'Warning', value: '#FFC107' },
  { name: 'Error', value: '#FF5252' },
];
</script>

<style scoped>
.ui-kit-page {
  background-color: #f0f2f5;
}

.color-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.color-chip {
  width: 16px;
  height: 16px;
  border-radius: 4px;
  border: 1px solid rgba(0, 0, 0, 0.14);
}

.ui-kit-table tbody tr:nth-child(even) {
  background-color: rgba(0, 0, 0, 0.02);
}
</style>
