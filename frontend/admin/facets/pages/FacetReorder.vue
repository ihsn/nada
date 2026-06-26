<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="facets-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <v-row align="center" class="mb-4">
      <v-col cols="12" md="8">
        <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-0">Configure Facets</h1>
      </v-col>
      <v-col cols="12" md="4" class="d-flex justify-end ga-2">
        <v-btn variant="text" @click="router.push('/')">Cancel</v-btn>
        <v-btn color="primary" variant="flat" prepend-icon="mdi-content-save" :loading="saving" @click="saveOrder">
          Save
        </v-btn>
      </v-col>
    </v-row>

    <v-snackbar v-model="toast.open" :color="toast.color" location="bottom right" :timeout="4000">
      {{ toast.message }}
      <template #actions>
        <v-btn variant="text" size="small" @click="toast.open = false">Dismiss</v-btn>
      </template>
    </v-snackbar>

    <v-alert v-if="error" type="error" class="mb-4" density="compact" closable @click:close="error = null">
      {{ error }}
    </v-alert>

    <v-card v-if="loading" elevation="1">
      <v-card-text class="text-center py-12">
        <v-progress-circular indeterminate color="primary" size="64" class="mb-4" />
        <p class="text-medium-emphasis">Loading…</p>
      </v-card-text>
    </v-card>

    <v-card v-else elevation="1">
      <v-card-text class="pa-4 pb-2">
        <p class="text-body-2 text-medium-emphasis mb-0">
          Drag items to reorder. Toggle the switch to enable or disable a facet for the selected data type.
        </p>
      </v-card-text>

      <v-tabs v-model="activeTab" color="primary" class="border-b">
        <v-tab v-for="dtype in dataTypes" :key="dtype" :value="dtype" class="text-capitalize">
          {{ dtype }}
        </v-tab>
      </v-tabs>

      <v-window v-model="activeTab">
        <v-window-item v-for="dtype in dataTypes" :key="dtype" :value="dtype">
          <div :ref="el => registerList(el, dtype)" class="sortable-list py-1">
            <div
              v-for="item in lists[dtype]"
              :key="item.name"
              :data-name="item.name"
              class="sortable-item d-flex align-center px-3 py-1"
              style="max-width:400px"
            >
              <!-- Drag handle -->
              <v-icon class="drag-handle mr-2 text-medium-emphasis flex-shrink-0" size="20">mdi-drag-vertical</v-icon>

              <!-- Title + name badge -->
              <span class="facet-title flex-grow-1">{{ item.title }}
                <span class="text-caption text-medium-emphasis ml-1">[{{ item.name }}]</span>
              </span>

              <!-- Enable/disable toggle aligned right -->
              <v-switch
                v-model="item.enabled"
                true-value="enabled"
                false-value="disabled"
                color="primary"
                density="compact"
                hide-details
                class="flex-shrink-0 ml-2"
              />
            </div>
          </div>
        </v-window-item>
      </v-window>
    </v-card>
  </div>
</template>

<script setup>
import { ref, reactive, computed, inject, onMounted, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import Sortable from 'sortablejs';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { APP_CONFIG_KEY } from '@/shared/composables/useAppConfig';
import { useFacetsApi } from '../composables/useFacetsApi';

const router = useRouter();
const { siteUrl } = useAppConfig();
const { loading, getOrdering, saveOrdering } = useFacetsApi();

const config    = inject(APP_CONFIG_KEY, window.APP_CONFIG || {});
const dataTypes = config.reorderDataTypes || ['all', 'microdata', 'geospatial', 'document', 'table', 'image', 'video', 'timeseries', 'script'];

const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const breadcrumbItems = computed(() => [
  { title: 'Admin',     href: `${siteBaseUrl.value}/admin` },
  { title: 'Facets',    href: `${siteBaseUrl.value}/admin/facets` },
  { title: 'Configure', disabled: true },
]);

const activeTab  = ref(dataTypes[0]);
const lists      = reactive({});
const saving     = ref(false);
const error      = ref(null);
const toast      = reactive({ open: false, message: '', color: 'success' });

// Track Sortable instances for cleanup
const sortableInstances = {};

function registerList(el, dtype) {
  if (!el) return;
  // Destroy previous instance if re-mounted
  if (sortableInstances[dtype]) {
    sortableInstances[dtype].destroy();
  }
  sortableInstances[dtype] = Sortable.create(el, {
    handle: '.drag-handle',
    animation: 150,
    ghostClass: 'sortable-ghost',
    chosenClass: 'sortable-chosen',
    onEnd(evt) {
      const arr  = lists[dtype];
      const item = arr.splice(evt.oldIndex, 1)[0];
      arr.splice(evt.newIndex, 0, item);
    },
  });
}

onBeforeUnmount(() => {
  for (const inst of Object.values(sortableInstances)) {
    inst.destroy();
  }
});

onMounted(async () => {
  try {
    const { ordering, facets } = await getOrdering();
    for (const dtype of dataTypes) {
      const order   = ordering[dtype] || [];
      const ordered = order.filter(name => facets[name]).map(name => ({
        name, title: facets[name].title, enabled: 'enabled',
      }));
      const rest = Object.keys(facets)
        .filter(name => !order.includes(name))
        .map(name => ({ name, title: facets[name].title, enabled: 'disabled' }));
      lists[dtype] = [...ordered, ...rest];
    }
  } catch (e) {
    error.value = e?.response?.data?.message || e.message;
  }
});

async function saveOrder() {
  saving.value = true;
  error.value  = null;
  try {
    const params = new URLSearchParams();
    for (const dtype of dataTypes) {
      for (const item of lists[dtype]) {
        if (item.enabled === 'enabled') {
          params.append(`${dtype}[${item.name}]`, '1');
        }
      }
    }
    await saveOrdering(params.toString());
    toast.message = 'Order saved.';
    toast.color   = 'success';
    toast.open    = true;
  } catch (e) {
    error.value = e?.response?.data?.message || e.message;
  } finally {
    saving.value = false;
  }
}
</script>

<style scoped>
.facets-breadcrumbs {
  font-size: 0.8125rem;
  margin-bottom: 0.5rem;
}
.facets-breadcrumbs :deep(.v-breadcrumbs-item),
.facets-breadcrumbs :deep(.v-breadcrumbs-divider) {
  font-size: 0.8125rem;
}

.border-b {
  border-bottom: 1px solid rgba(0, 0, 0, 0.12);
}

.sortable-item {
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
  transition: background-color 0.15s;
}
.sortable-item:last-child {
  border-bottom: none;
}

.drag-handle {
  cursor: grab;
  flex-shrink: 0;
}
.drag-handle:active {
  cursor: grabbing;
}

/* SortableJS feedback classes */
.sortable-ghost {
  opacity: 0.4;
  background-color: rgb(var(--v-theme-primary), 0.08);
}
.sortable-chosen {
  background-color: rgba(0, 0, 0, 0.03);
}

.flex-0 {
  flex: 0 0 auto;
}

.facet-title {
  font-size: 0.9rem;
}
</style>
