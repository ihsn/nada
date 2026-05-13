<template>
  <div>
    <div v-if="catalog" class="catalog-stats__tiles-wrap">
    <v-row dense class="catalog-stats__tiles ga-2">
      <v-col cols="12" sm="4">
        <v-sheet
          rounded="lg"
          border
          class="catalog-stats__tile text-center bg-grey-lighten-4"
        >
          <div class="text-body-2 font-weight-bold">{{ catalog.total.toLocaleString() }}</div>
          <div class="text-caption text-medium-emphasis">Total studies</div>
        </v-sheet>
      </v-col>
      <v-col cols="12" sm="4">
        <v-sheet
          rounded="lg"
          border
          class="catalog-stats__tile text-center bg-grey-lighten-4"
        >
          <div class="text-body-2 font-weight-bold">{{ catalog.published.toLocaleString() }}</div>
          <div class="text-caption text-medium-emphasis">Published</div>
        </v-sheet>
      </v-col>
      <v-col cols="12" sm="4">
        <v-sheet
          rounded="lg"
          border
          class="catalog-stats__tile text-center bg-grey-lighten-4"
        >
          <div class="text-body-2 font-weight-bold">{{ catalog.unpublished.toLocaleString() }}</div>
          <div class="text-caption text-medium-emphasis">Unpublished</div>
        </v-sheet>
      </v-col>
    </v-row>
    </div>

    <v-table v-if="byType.length" density="compact">
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

<style scoped>
.catalog-stats__tiles-wrap {
  padding-bottom: 28px;
  margin-bottom: 4px;
}
.catalog-stats__tile {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 2px;
  min-height: 56px;
  padding: 10px 8px;
}
</style>
