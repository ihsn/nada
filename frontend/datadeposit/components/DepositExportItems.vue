<script setup>
import { computed } from 'vue';
import { useDepositApi } from '../composables/useDepositApi';
import { useAppConfig } from '@/shared/composables/useAppConfig';

const props = defineProps({
  projectId: { type: [String, Number], required: true },
  dataType: { type: String, default: '' },
  canExportDdi: { type: Boolean, default: null },
  disabled: { type: Boolean, default: false },
});

const { config } = useAppConfig();
const { exportUrl } = useDepositApi();

const lbl = computed(() => {
  const labels = config.value?.labels || {};
  return {
    export: labels.export || 'Export',
    exportDdi: labels.exportDdi || 'DDI/XML',
    exportJson: labels.exportJson || 'Metadata (JSON)',
    exportProjectJson: labels.exportProjectJson || 'Project (JSON)',
    exportRdf: labels.exportRdf || 'External resources (RDF)',
    exportExternalResources: labels.exportExternalResources || 'External resources (JSON)',
  };
});

const showDdi = computed(() => {
  if (typeof props.canExportDdi === 'boolean') return props.canExportDdi;
  return String(props.dataType || '').toLowerCase() === 'survey';
});

const items = computed(() => {
  const id = props.projectId;
  const rows = [];
  if (showDdi.value) {
    rows.push({
      key: 'ddi',
      title: lbl.value.exportDdi,
      href: exportUrl(id, 'ddi'),
      icon: 'mdi-file-xml-box',
    });
  }
  rows.push(
    {
      key: 'json',
      title: lbl.value.exportJson,
      href: exportUrl(id, 'json'),
      icon: 'mdi-code-json',
    },
    {
      key: 'project',
      title: lbl.value.exportProjectJson,
      href: exportUrl(id, 'project'),
      icon: 'mdi-folder-zip-outline',
    },
    {
      key: 'rdf',
      title: lbl.value.exportRdf,
      href: exportUrl(id, 'rdf'),
      icon: 'mdi-file-code-outline',
    },
    {
      key: 'external_resources',
      title: lbl.value.exportExternalResources,
      href: exportUrl(id, 'external_resources'),
      icon: 'mdi-file-document-outline',
    }
  );
  return rows;
});
</script>

<template>
  <v-list-item
    v-for="item in items"
    :key="item.key"
    class="dd-menu-item"
    density="compact"
    slim
    :href="item.href"
    :disabled="disabled || !item.href"
  >
    <template #prepend>
      <v-icon size="18">{{ item.icon }}</v-icon>
    </template>
    <v-list-item-title>{{ item.title }}</v-list-item-title>
  </v-list-item>
</template>

<style>
.dd-menu-list {
  font-size: 0.8125rem;
}
.dd-menu-list .v-list-item {
  min-height: 32px;
  font-size: 0.8125rem;
}
.dd-menu-list .v-list-item-title {
  font-size: 0.8125rem;
  line-height: 1.3;
  font-weight: 400;
}
.dd-menu-list .v-icon {
  font-size: 1.125rem;
}
</style>
