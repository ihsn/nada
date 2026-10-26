<template>
  <v-menu>
    <template #activator="{ props: menuProps }">
      <v-btn v-bind="menuProps" variant="outlined" prepend-icon="mdi-download" :disabled="disabled">
        Export
      </v-btn>
    </template>
    <v-list density="compact">
      <v-list-item title="CSV" prepend-icon="mdi-file-delimited" @click="emit('export', 'csv')" />
      <v-list-item title="JSON" prepend-icon="mdi-code-json" @click="emit('export', 'json')" />
      <v-divider v-if="exportMaxRows" class="my-1" />
      <v-list-item v-if="exportMaxRows" disabled class="text-caption text-medium-emphasis" style="min-height: auto">
        <v-list-item-title class="text-caption font-weight-regular">
          Limited to {{ exportMaxRows.toLocaleString() }} rows per file.
        </v-list-item-title>
      </v-list-item>
    </v-list>
  </v-menu>
</template>

<script setup>
defineProps({
  disabled: { type: Boolean, default: false },
  exportMaxRows: { type: Number, default: 0 },
});

const emit = defineEmits(['export']);

defineOptions({ name: 'ExportFormatMenu' });
</script>
