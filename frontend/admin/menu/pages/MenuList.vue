<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="menu-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <v-row align="center" class="mb-4">
      <v-col cols="12" md="8">
        <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-0">Menu Management</h1>
      </v-col>
      <v-col cols="12" md="4" class="d-flex justify-end ga-2 flex-wrap">
        <v-btn variant="tonal" prepend-icon="mdi-link-variant" @click="router.push('/new-link')">
          Add Link
        </v-btn>
        <v-btn color="primary" prepend-icon="mdi-plus" @click="router.push('/new')">
          Add Page
        </v-btn>
      </v-col>
    </v-row>

    <v-snackbar v-model="toast.open" :color="toast.color" location="bottom right" :timeout="4000" :z-index="9999">
      {{ toast.message }}
      <template #actions>
        <v-btn variant="text" size="small" @click="toast.open = false">Dismiss</v-btn>
      </template>
    </v-snackbar>

    <v-card v-if="loading && !menus.length" elevation="1">
      <v-card-text class="text-center py-12">
        <v-progress-circular indeterminate color="primary" size="64" class="mb-4" />
        <p class="text-medium-emphasis">Loading…</p>
      </v-card-text>
    </v-card>

    <v-card v-else-if="!menus.length" elevation="1">
      <v-card-text class="text-center py-12">
        <v-icon size="64" color="grey" class="mb-4">mdi-menu</v-icon>
        <h2 class="text-h6 mb-2">No menu items found</h2>
        <p class="text-medium-emphasis mb-4">Create your first menu item to get started.</p>
        <v-btn color="primary" prepend-icon="mdi-plus" @click="router.push('/new')">Add Page</v-btn>
      </v-card-text>
    </v-card>

    <v-card v-else elevation="1">
      <div class="menu-table-header d-flex align-center px-4 py-2 text-caption text-medium-emphasis font-weight-medium">
        <div style="width: 40px" class="d-flex justify-center">
          <v-checkbox
            :model-value="allSelected"
            :indeterminate="someSelected && !allSelected"
            density="compact"
            hide-details
            @update:model-value="toggleSelectAll"
          />
        </div>
        <div style="width: 28px"></div>
        <div style="flex: 2" class="d-flex align-center ga-2 min-width-0">
          <v-menu v-if="selectedIds.length" location="bottom start">
            <template #activator="{ props: batchMenuProps }">
              <v-btn
                variant="text"
                size="small"
                class="text-none px-1 flex-shrink-0"
                v-bind="batchMenuProps"
                :loading="batchBusy"
                append-icon="mdi-dots-vertical"
              >
                Actions
              </v-btn>
            </template>
            <v-list density="compact" min-width="180">
              <v-list-item
                prepend-icon="mdi-publish"
                title="Publish"
                :disabled="batchBusy"
                @click="batchPublish(1)"
              />
              <v-list-item
                prepend-icon="mdi-eye-off-outline"
                title="Unpublish"
                :disabled="batchBusy"
                @click="batchPublish(0)"
              />
              <v-divider />
              <v-list-item
                prepend-icon="mdi-delete"
                title="Delete"
                base-color="error"
                :disabled="batchBusy"
                @click="openBatchDeleteDialog"
              />
              <v-divider />
              <v-list-item
                prepend-icon="mdi-close"
                title="Clear selection"
                :disabled="batchBusy"
                @click="clearSelection"
              />
            </v-list>
          </v-menu>
          <template v-else>
            <span>Title</span>
          </template>
          <span v-if="selectedIds.length" class="text-body-2 font-weight-regular">
            {{ selectedIds.length }} selected
          </span>
        </div>
        <div style="width: 60px" class="text-center">Link</div>
        <div style="flex: 1; min-width: 110px">Published</div>
        <div style="flex: 1; min-width: 120px">Modified</div>
        <div style="width: 60px"></div>
      </div>
      <v-divider />

      <div ref="parentList" class="parent-sortable">
        <div v-for="parent in menus" :key="parent.id" :data-id="parent.id" class="menu-group">
          <div class="parent-block">
            <div class="menu-row d-flex align-center px-4 py-2" :class="{ 'menu-row--selected': isSelected(parent.id) }">
              <div style="width: 40px" class="d-flex justify-center flex-shrink-0" @click.stop>
                <v-checkbox
                  :model-value="isSelected(parent.id)"
                  density="compact"
                  hide-details
                  @update:model-value="(v) => setSelected(parent.id, v)"
                />
              </div>
              <v-icon class="parent-drag-handle drag-handle mr-2 text-medium-emphasis flex-shrink-0" size="18">mdi-drag-vertical</v-icon>
              <div style="flex: 2" class="d-flex align-start ga-2">
                <v-icon size="20" :color="parent.linktype == 1 ? 'blue-darken-1' : 'grey-darken-1'" class="flex-shrink-0 mt-1">
                  {{ parent.linktype == 1 ? 'mdi-link-variant' : 'mdi-file-document-outline' }}
                </v-icon>
                <div class="min-width-0">
                  <a href="#" class="font-weight-medium text-decoration-none text-high-emphasis" @click.prevent="router.push(`/edit/${parent.id}`)">{{ parent.title }}</a>
                  <div v-if="parent.url" class="text-caption text-medium-emphasis text-truncate">{{ parent.url }}</div>
                </div>
              </div>
              <div style="width: 60px" class="d-flex justify-center">
                <v-tooltip v-if="parent.url" text="Preview" location="top">
                  <template #activator="{ props: tooltipProps }">
                    <v-btn
                      icon
                      variant="text"
                      size="small"
                      v-bind="tooltipProps"
                      @click.stop="openMenuLink(parent)"
                    >
                      <v-icon size="20">mdi-eye-outline</v-icon>
                    </v-btn>
                  </template>
                </v-tooltip>
              </div>
              <div style="flex: 1; min-width: 110px">
                <v-chip :color="parent.published == 1 ? 'success' : 'default'" size="small" variant="tonal" class="cursor-pointer" @click="togglePublish(parent)">
                  {{ parent.published == 1 ? 'Published' : 'Draft' }}
                </v-chip>
              </div>
              <div style="flex: 1; min-width: 120px" class="text-caption text-medium-emphasis">{{ formatDate(parent.changed) }}</div>
              <div style="width: 60px" class="d-flex justify-end">
                <v-menu location="bottom end">
                  <template #activator="{ props: menuProps }">
                    <v-btn icon variant="text" v-bind="menuProps" size="small"><v-icon>mdi-dots-vertical</v-icon></v-btn>
                  </template>
                  <v-list density="compact" min-width="150">
                    <v-list-item prepend-icon="mdi-pencil" title="Edit" @click="router.push(`/edit/${parent.id}`)" />
                    <v-divider />
                    <v-list-item prepend-icon="mdi-delete" title="Delete" base-color="error" @click="openDeleteDialog(parent)" />
                  </v-list>
                </v-menu>
              </div>
            </div>
          </div>

          <div
            :ref="el => registerChildList(el, parent.id)"
            :data-parent-id="parent.id"
            class="child-sortable"
          >
            <div
              v-for="child in parent.children"
              :key="child.id"
              :data-id="child.id"
              class="menu-row menu-row--child d-flex align-center px-4 py-2"
              :class="{ 'menu-row--selected': isSelected(child.id) }"
            >
              <div style="width: 40px" class="d-flex justify-center flex-shrink-0" @click.stop>
                <v-checkbox
                  :model-value="isSelected(child.id)"
                  density="compact"
                  hide-details
                  @update:model-value="(v) => setSelected(child.id, v)"
                />
              </div>
              <v-icon class="child-drag-handle drag-handle mr-2 text-medium-emphasis flex-shrink-0" size="18">mdi-drag-vertical</v-icon>
              <div style="flex: 2" class="d-flex align-start">
                <span style="width: 24px; flex-shrink: 0; padding-top: 2px"></span>
                <span class="text-medium-emphasis mr-2 mt-1">—</span>
                <v-icon size="20" :color="child.linktype == 1 ? 'blue-darken-1' : 'grey-darken-1'" class="flex-shrink-0 mr-2 mt-1">
                  {{ child.linktype == 1 ? 'mdi-link-variant' : 'mdi-file-document-outline' }}
                </v-icon>
                <div class="min-width-0">
                  <a href="#" class="text-medium-emphasis text-decoration-none" @click.prevent="router.push(`/edit/${child.id}`)">{{ child.title }}</a>
                  <div v-if="child.url" class="text-caption text-medium-emphasis text-truncate">{{ child.url }}</div>
                </div>
              </div>
              <div style="width: 60px" class="d-flex justify-center">
                <v-tooltip v-if="child.url" text="Preview" location="top">
                  <template #activator="{ props: tooltipProps }">
                    <v-btn
                      icon
                      variant="text"
                      size="small"
                      v-bind="tooltipProps"
                      @click.stop="openMenuLink(child)"
                    >
                      <v-icon size="20">mdi-eye-outline</v-icon>
                    </v-btn>
                  </template>
                </v-tooltip>
              </div>
              <div style="flex: 1; min-width: 110px">
                <v-chip :color="child.published == 1 ? 'success' : 'default'" size="small" variant="tonal" class="cursor-pointer" @click="togglePublish(child)">
                  {{ child.published == 1 ? 'Published' : 'Draft' }}
                </v-chip>
              </div>
              <div style="flex: 1; min-width: 120px" class="text-caption text-medium-emphasis">{{ formatDate(child.changed) }}</div>
              <div style="width: 60px" class="d-flex justify-end">
                <v-menu location="bottom end">
                  <template #activator="{ props: menuProps }">
                    <v-btn icon variant="text" v-bind="menuProps" size="small"><v-icon>mdi-dots-vertical</v-icon></v-btn>
                  </template>
                  <v-list density="compact" min-width="150">
                    <v-list-item prepend-icon="mdi-pencil" title="Edit" @click="router.push(`/edit/${child.id}`)" />
                    <v-divider />
                    <v-list-item prepend-icon="mdi-delete" title="Delete" base-color="error" @click="openDeleteDialog(child)" />
                  </v-list>
                </v-menu>
              </div>
            </div>
          </div>

          <v-divider />
        </div>
      </div>
    </v-card>

    <v-dialog v-model="deleteDialog.open" max-width="420">
      <v-card>
        <v-card-title class="text-h6 pa-4">Confirm delete</v-card-title>
        <v-divider />
        <v-card-text class="pa-4">
          <v-alert v-if="deleteDialog.error" type="error" class="mb-4" density="compact" closable @click:close="deleteDialog.error = null">
            {{ deleteDialog.error }}
          </v-alert>
          <p>Are you sure you want to delete?</p>
        </v-card-text>
        <v-divider />
        <v-card-actions class="pa-3">
          <v-spacer />
          <v-btn variant="text" @click="deleteDialog.open = false">Cancel</v-btn>
          <v-btn color="error" variant="flat" :loading="deleteDialog.loading" @click="doDelete">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="batchDeleteDialog.open" max-width="420">
      <v-card>
        <v-card-title class="text-h6 pa-4">Confirm delete</v-card-title>
        <v-divider />
        <v-card-text class="pa-4">
          <v-alert
            v-if="batchDeleteDialog.error"
            type="error"
            class="mb-4"
            density="compact"
            closable
            @click:close="batchDeleteDialog.error = null"
          >
            {{ batchDeleteDialog.error }}
          </v-alert>
          <p>Are you sure you want to delete?</p>
        </v-card-text>
        <v-divider />
        <v-card-actions class="pa-3">
          <v-spacer />
          <v-btn variant="text" @click="batchDeleteDialog.open = false">Cancel</v-btn>
          <v-btn color="error" variant="flat" :loading="batchDeleteDialog.loading" @click="doBatchDelete">
            Delete
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import Sortable from 'sortablejs';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useMenuApi } from '../composables/useMenuApi';

const router = useRouter();
const { siteUrl } = useAppConfig();
const {
  loading,
  listMenus,
  deleteMenu,
  publishMenu,
  reorderMenus,
  batchDeleteMenus,
  batchPublishMenus,
} = useMenuApi();

const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const breadcrumbItems = computed(() => [
  { title: 'Admin', href: `${siteBaseUrl.value}/admin` },
  { title: 'Menu', disabled: true },
]);

const menus             = ref([]);
const selectedIds       = ref([]);
const batchBusy         = ref(false);
const savingOrder       = ref(false);
const toast             = reactive({ open: false, message: '', color: 'success' });
const deleteDialog      = reactive({ open: false, item: null, error: null, loading: false });
const batchDeleteDialog = reactive({ open: false, error: null, loading: false });

const allMenuIds = computed(() => {
  const ids = [];
  menus.value.forEach((parent) => {
    ids.push(Number(parent.id));
    (parent.children || []).forEach((child) => ids.push(Number(child.id)));
  });
  return ids;
});

const allSelected = computed(
  () => allMenuIds.value.length > 0 && selectedIds.value.length === allMenuIds.value.length
);
const someSelected = computed(() => selectedIds.value.length > 0);

function isSelected(id) {
  return selectedIds.value.includes(Number(id));
}

function setSelected(id, checked) {
  const n = Number(id);
  if (checked) {
    if (!selectedIds.value.includes(n)) selectedIds.value = [...selectedIds.value, n];
  } else {
    selectedIds.value = selectedIds.value.filter((x) => x !== n);
  }
}

function toggleSelectAll(checked) {
  selectedIds.value = checked ? [...allMenuIds.value] : [];
}

function clearSelection() {
  selectedIds.value = [];
}

const parentList     = ref(null);
let   parentSortable = null;
const childSortables = {};

function normalizeMenus(raw) {
  return (raw || []).map(p => ({
    ...p,
    id: Number(p.id),
    children: (Array.isArray(p.children) ? p.children : []).map(c => ({
      ...c,
      id: Number(c.id),
      pid: Number(c.pid),
    })),
  }));
}

function showToast(message, color = 'success') {
  toast.message = message;
  toast.color   = color;
  toast.open    = true;
}

function formatDate(ts) {
  if (!ts) return '—';
  return new Date(parseInt(ts) * 1000).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

function menuItemHref(item) {
  const url = String(item?.url || '').trim();
  if (!url) return '#';
  if (/^https?:\/\//i.test(url)) return url;
  const base = siteBaseUrl.value.replace(/\/$/, '');
  return `${base}/${url.replace(/^\//, '')}`;
}

function openMenuLink(item) {
  const href = menuItemHref(item);
  if (href && href !== '#') {
    window.open(href, '_blank', 'noopener,noreferrer');
  }
}

function buildOrderPayload() {
  const items = [];
  menus.value.forEach((parent, pi) => {
    items.push({ id: parent.id, pid: 0, weight: pi });
    (parent.children || []).forEach((child, ci) => {
      items.push({ id: child.id, pid: parent.id, weight: ci });
    });
  });
  return items;
}

function findParentById(id) {
  const n = Number(id);
  return menus.value.find(p => Number(p.id) === n);
}

async function saveOrder() {
  if (savingOrder.value) return false;
  savingOrder.value = true;
  try {
    await reorderMenus(buildOrderPayload());
    showToast('Menu order saved.');
    return true;
  } catch (e) {
    showToast(e?.response?.data?.message || e.message || 'Failed to save order.', 'error');
    return false;
  } finally {
    savingOrder.value = false;
  }
}

function destroyAllSortables() {
  if (parentSortable) { parentSortable.destroy(); parentSortable = null; }
  Object.values(childSortables).forEach(s => s.destroy());
  for (const k of Object.keys(childSortables)) delete childSortables[k];
}

function syncChildOrderFromDom(container, parent) {
  const ids = Array.from(container.querySelectorAll(':scope > .menu-row--child'))
    .map(el => Number(el.dataset.id))
    .filter(Boolean);
  const byId = Object.fromEntries(parent.children.map(c => [Number(c.id), c]));
  parent.children = ids.map(id => byId[id]).filter(Boolean);
}

function registerChildList(el, parentId) {
  if (!el) {
    if (childSortables[parentId]) {
      childSortables[parentId].destroy();
      delete childSortables[parentId];
    }
    return;
  }
  if (childSortables[parentId]) childSortables[parentId].destroy();

  childSortables[parentId] = Sortable.create(el, {
    handle: '.child-drag-handle',
    draggable: '.menu-row--child',
    animation: 150,
    ghostClass: 'sortable-ghost',
    chosenClass: 'sortable-chosen',

    onEnd(evt) {
      if (evt.oldIndex === evt.newIndex) return;
      const pid    = Number(evt.from.dataset.parentId);
      const parent = findParentById(pid);
      if (!parent || !Array.isArray(parent.children)) return;
      syncChildOrderFromDom(evt.from, parent);
      saveOrder();
    },
  });
}

function initParentSortable() {
  if (!parentList.value) return;
  if (parentSortable) parentSortable.destroy();

  parentSortable = Sortable.create(parentList.value, {
    handle: '.parent-drag-handle',
    animation: 150,
    ghostClass: 'sortable-ghost',
    chosenClass: 'sortable-chosen',
    draggable: '.menu-group',
    filter: '.child-sortable, .child-sortable *',
    preventOnFilter: false,

    onEnd() {
      const groups   = Array.from(parentList.value.querySelectorAll(':scope > .menu-group'));
      const newOrder = groups.map(el => Number(el.dataset.id));
      const current  = menus.value.map(m => Number(m.id));
      if (newOrder.length === current.length && newOrder.every((id, i) => id === current[i])) {
        return;
      }
      const byId  = Object.fromEntries(menus.value.map(m => [Number(m.id), m]));
      menus.value = newOrder.map(id => byId[id]).filter(Boolean);
      saveOrder().finally(() => nextTick(initParentSortable));
    },
  });
}

onBeforeUnmount(destroyAllSortables);

async function loadMenus() {
  try {
    menus.value = normalizeMenus(await listMenus());
    selectedIds.value = selectedIds.value.filter((id) => allMenuIds.value.includes(id));
    await nextTick();
    initParentSortable();
  } catch (e) {
    showToast(e?.response?.data?.message || e.message, 'error');
  }
}

onMounted(loadMenus);

function openDeleteDialog(item) {
  deleteDialog.item  = item;
  deleteDialog.error = null;
  deleteDialog.open  = true;
}

async function doDelete() {
  deleteDialog.loading = true;
  deleteDialog.error   = null;
  try {
    await deleteMenu(deleteDialog.item.id);
    deleteDialog.open = false;
    showToast('Menu item deleted.');
    await loadMenus();
  } catch (e) {
    deleteDialog.error = e?.response?.data?.message || e.message;
  } finally {
    deleteDialog.loading = false;
  }
}

async function togglePublish(item) {
  const newVal = item.published == 1 ? 0 : 1;
  try {
    await publishMenu(item.id, newVal);
    item.published = newVal;
    showToast(newVal ? 'Published.' : 'Unpublished.');
  } catch (e) {
    showToast(e?.response?.data?.message || e.message, 'error');
  }
}

async function batchPublish(published) {
  if (!selectedIds.value.length || batchBusy.value) return;
  batchBusy.value = true;
  try {
    await batchPublishMenus(selectedIds.value, published);
    showToast(published ? 'Published selected items.' : 'Unpublished selected items.');
    clearSelection();
    await loadMenus();
  } catch (e) {
    showToast(e?.response?.data?.message || e.message, 'error');
  } finally {
    batchBusy.value = false;
  }
}

function openBatchDeleteDialog() {
  if (!selectedIds.value.length) return;
  batchDeleteDialog.error = null;
  batchDeleteDialog.open = true;
}

async function doBatchDelete() {
  if (!selectedIds.value.length) return;
  batchDeleteDialog.loading = true;
  batchDeleteDialog.error = null;
  try {
    await batchDeleteMenus(selectedIds.value);
    batchDeleteDialog.open = false;
    showToast('Selected menu items deleted.');
    clearSelection();
    await loadMenus();
  } catch (e) {
    batchDeleteDialog.error = e?.response?.data?.message || e.message;
  } finally {
    batchDeleteDialog.loading = false;
  }
}
</script>

<style scoped>
.menu-breadcrumbs {
  font-size: 0.8125rem;
  margin-bottom: 0.5rem;
}
.menu-breadcrumbs :deep(.v-breadcrumbs-item),
.menu-breadcrumbs :deep(.v-breadcrumbs-divider) {
  font-size: 0.8125rem;
}

.menu-table-header {
  background-color: rgba(0, 0, 0, 0.02);
}

.menu-row {
  transition: background-color 0.15s;
}
.menu-row:hover {
  background-color: rgba(0, 0, 0, 0.02);
}

.menu-row--selected {
  background-color: rgba(var(--v-theme-primary), 0.06);
}

.menu-row--child {
  background-color: rgba(0, 0, 0, 0.01);
  border-left: 3px solid rgba(var(--v-theme-primary), 0.2);
}

.child-sortable {
  min-height: 0;
}

.drag-handle {
  cursor: grab;
  opacity: 0.4;
  transition: opacity 0.15s;
}
.menu-row:hover .drag-handle {
  opacity: 1;
}
.drag-handle:active {
  cursor: grabbing;
}

.sortable-ghost {
  opacity: 0.4;
  background-color: rgba(var(--v-theme-primary), 0.05);
}
.sortable-chosen {
  background-color: rgba(0, 0, 0, 0.02);
}
</style>
