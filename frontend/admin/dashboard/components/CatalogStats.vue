<template>
  <div>
    <v-row v-if="catalog">
      <v-col cols="12" sm="4">
        <v-card variant="outlined" class="text-center py-4">
          <div class="text-h4 font-weight-bold text-primary">{{ catalog.total.toLocaleString() }}</div>
          <div class="text-caption text-uppercase text-medium-emphasis mt-1">Total Studies</div>
        </v-card>
      </v-col>
      <v-col cols="12" sm="4">
        <v-card variant="outlined" class="text-center py-4">
          <div class="text-h4 font-weight-bold text-success">{{ catalog.published.toLocaleString() }}</div>
          <div class="text-caption text-uppercase text-medium-emphasis mt-1">Published</div>
        </v-card>
      </v-col>
      <v-col cols="12" sm="4">
        <v-card variant="outlined" class="text-center py-4">
          <div class="text-h4 font-weight-bold text-warning">{{ catalog.unpublished.toLocaleString() }}</div>
          <div class="text-caption text-uppercase text-medium-emphasis mt-1">Unpublished</div>
        </v-card>
      </v-col>
    </v-row>

    <v-table v-if="byType.length" density="compact" class="mt-3">
      <thead>
        <tr>
          <th>Study Type</th>
          <th class="text-right">Total</th>
          <th class="text-right">Published</th>
          <th class="text-right">Unpublished</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in byType" :key="row.type">
          <td>
            <v-chip size="x-small" label variant="outlined" color="primary" class="text-capitalize">{{ row.type }}</v-chip>
          </td>
          <td class="text-right">{{ row.total.toLocaleString() }}</td>
          <td class="text-right text-success">{{ row.published.toLocaleString() }}</td>
          <td class="text-right text-warning">{{ row.unpublished.toLocaleString() }}</td>
        </tr>
      </tbody>
    </v-table>

    <div v-if="!catalog" class="text-center text-medium-emphasis py-6">
      <v-progress-circular indeterminate color="primary" size="24" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  catalog: { type: Object, default: null },
});

const byType = computed(() => props.catalog?.by_type ?? []);
</script>
