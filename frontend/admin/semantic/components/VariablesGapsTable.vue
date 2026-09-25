<template>
  <div>
    <v-data-table :headers="headers" :items="gaps" :items-per-page="10" density="comfortable">
      <template #item.idno="{ item }"><code>{{ item.idno }}</code></template>
      <template #item.title="{ item }">
        <span class="d-inline-block text-truncate" style="max-width: 360px" :title="item.title">{{ item.title }}</span>
      </template>
      <template #item.database="{ item }">{{ formatCount(item.database) }}</template>
      <template #item.indexed="{ item }">
        <span :class="item.indexed === 0 ? 'text-warning' : ''">{{ formatCount(item.indexed) }}</span>
      </template>
      <template #item.actions="{ item }">
        <v-btn
          size="small"
          variant="text"
          :disabled="!canEdit"
          :loading="starting"
          @click="$emit('sync', [item.idno])"
        >
          Index
        </v-btn>
      </template>
    </v-data-table>
    <div class="d-flex align-center flex-wrap ga-3 mt-2">
      <v-btn
        size="small"
        variant="tonal"
        color="primary"
        prepend-icon="mdi-database-sync-outline"
        :loading="starting"
        :disabled="!canEdit"
        @click="$emit('sync', gaps.map((g) => g.idno))"
      >
        Index variables of {{ gaps.length === total ? 'these' : 'the first' }} {{ formatCount(gaps.length) }} studies
      </v-btn>
      <span v-if="total > gaps.length" class="text-caption text-medium-emphasis">
        {{ formatCount(total - gaps.length) }} more not listed; run this again afterwards, or use Index variables for
        everything.
      </span>
    </div>
  </div>
</template>

<script setup>
import { formatCount } from '../typeLabels.js';

defineOptions({ name: 'SemanticVariablesGapsTable' });
defineProps({
  /** @type {import('vue').PropType<{sid:number, idno:string, title:string, database:number, indexed:number}[]>} */
  gaps: { type: Array, required: true },
  total: { type: Number, required: true },
  canEdit: { type: Boolean, default: false },
  starting: { type: Boolean, default: false },
});
defineEmits(['sync']);

const headers = [
  { title: 'Study', key: 'idno' },
  { title: 'Title', key: 'title', sortable: false },
  { title: 'Database', key: 'database', align: 'end' },
  { title: 'Indexed', key: 'indexed', align: 'end' },
  { title: '', key: 'actions', sortable: false, align: 'end' },
];
</script>
