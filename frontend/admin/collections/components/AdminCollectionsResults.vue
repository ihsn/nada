<template>
  <div class="admin-collections-results">
    <div v-if="loading" class="pa-6 text-center">
      <v-progress-circular indeterminate color="primary" />
    </div>

    <template v-else>
      <div
        v-if="collections.length"
        class="admin-collections-batch-toolbar d-flex align-center justify-space-between flex-wrap ga-2"
      >
        <div class="d-flex align-center ga-2 flex-wrap">
          <v-checkbox
            v-model="selectAll"
            :indeterminate="isIndeterminate"
            hide-details
            density="compact"
            class="ma-0 pa-0 collection-select-all"
          />
          <v-menu location="bottom start">
            <template #activator="{ props }">
              <v-btn
                color="primary"
                variant="text"
                icon="mdi-dots-vertical"
                v-bind="props"
                :disabled="batchProcessing"
                title="Batch actions"
              />
            </template>
            <v-list density="compact">
              <v-list-item prepend-icon="mdi-check-circle" title="Publish" @click="batchAction('publish')" />
              <v-list-item prepend-icon="mdi-cancel" title="Unpublish" @click="batchAction('unpublish')" />
              <v-list-item prepend-icon="mdi-delete" title="Delete" base-color="error" @click="batchAction('delete')" />
            </v-list>
          </v-menu>
          <span v-if="selected.length" class="text-body-2 text-medium-emphasis">
            {{ selected.length }} selected
          </span>
        </div>
        <div class="admin-collections-toolbar-pagination-wrap d-flex align-center ga-3 flex-wrap ms-auto">
          <span class="text-body-2 text-medium-emphasis text-no-wrap">
            Showing {{ firstItem }} – {{ lastItem }} of {{ totalCollections }} collection{{ totalCollections === 1 ? '' : 's' }}
          </span>
          <v-pagination
            v-model="page"
            :length="totalPages"
            :total-visible="7"
            density="compact"
            color="primary"
            class="admin-collections-toolbar-pagination"
          />
        </div>
      </div>

      <div v-if="!collections.length" class="pa-8 text-center text-medium-emphasis">
        No collections found.
      </div>

      <v-table
        v-else
        class="admin-collections-table"
        hover
        density="compact"
      >
        <thead>
          <tr>
            <th class="collection-col-select" scope="col" />
            <th class="collection-col-thumb" scope="col" />
            <th class="collection-col-main" scope="col">Collection</th>
            <th class="collection-col-section" scope="col">Section</th>
            <th class="collection-col-studies text-center" scope="col">Studies</th>
            <th class="collection-col-weight text-center" scope="col">Weight</th>
            <th v-if="showUserAccessColumn" class="collection-col-access" scope="col">User access</th>
            <th class="collection-col-published text-center" scope="col">Published</th>
            <th class="collection-col-actions text-end" scope="col" />
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in pagedCollections"
            :key="item.id"
            class="collection-row"
            @click="$emit('edit', item)"
          >
            <td class="collection-col-select align-middle" @click.stop>
              <v-checkbox
                v-model="selected"
                :value="item.repositoryid"
                hide-details
                density="compact"
                class="ma-0 pa-0"
              />
            </td>

            <td class="collection-col-thumb align-middle" @click.stop>
              <img
                :src="item.thumbnail ? baseUrl + item.thumbnail : defaultThumb"
                class="collection-thumb"
                alt=""
                @error="e => e.target.src = defaultThumb"
              />
            </td>

            <td class="collection-col-main align-middle">
              <div class="collection-row-detail">
                <div class="collection-row-detail__title-line">
                  <a href="#" @click.prevent.stop="$emit('edit', item)">{{ item.title || item.repositoryid }}</a>
                  <v-chip size="x-small" variant="tonal" color="primary">
                    {{ item.repositoryid }}
                  </v-chip>
                  <v-chip
                    v-if="item.ispublished != 1"
                    size="x-small"
                    variant="tonal"
                    color="warning"
                  >
                    Draft
                  </v-chip>
                </div>
              </div>
            </td>

            <td class="collection-col-section align-middle text-body-2">
              {{ item.section_title || '—' }}
            </td>

            <td class="collection-col-studies align-middle text-center" @click.stop>
              <a
                class="studies-link text-body-2 font-weight-medium"
                :href="siteUrl + '/admin/collections/active/' + item.id"
              >
                {{ item.study_count ?? 0 }}
              </a>
            </td>

            <td class="collection-col-weight align-middle text-center text-body-2">
              {{ item.weight ?? 0 }}
            </td>

            <td
              v-if="showUserAccessColumn"
              class="collection-col-access align-middle"
              @click.stop
            >
              <div class="collection-access-cell">
                <div class="collection-access-chips">
                  <template v-if="item.acl_user_count > 0">
                    <v-chip
                      v-for="user in item.acl_users || []"
                      :key="user.user_id"
                      size="x-small"
                      variant="tonal"
                      class="collection-user-chip"
                    >
                      {{ userDisplayName(user) }}
                    </v-chip>
                    <v-chip
                      v-if="extraUserCount(item) > 0"
                      size="x-small"
                      variant="tonal"
                      color="primary"
                      class="collection-user-chip"
                    >
                      +{{ extraUserCount(item) }}
                    </v-chip>
                  </template>
                  <span v-else class="text-medium-emphasis text-caption">—</span>
                </div>
                <v-tooltip v-if="canManageAccess(item)" text="Manage user access" location="top">
                  <template #activator="{ props }">
                    <v-btn
                      icon="mdi-account-key-outline"
                      size="x-small"
                      variant="text"
                      color="primary"
                      class="collection-access-manage-btn"
                      v-bind="props"
                      @click="goPermissions(item)"
                    />
                  </template>
                </v-tooltip>
              </div>
            </td>

            <td class="collection-col-published align-middle text-center" @click.stop>
              <v-switch
                :model-value="item.ispublished == 1"
                density="compact"
                color="success"
                hide-details
                :title="item.ispublished == 1 ? 'Published' : 'Draft'"
                @update:model-value="val => $emit('publish-change', { collection: item, published: val ? 1 : 0 })"
              />
            </td>

            <td class="collection-col-actions align-middle text-end" @click.stop>
              <v-menu location="bottom end">
                <template #activator="{ props }">
                  <v-btn icon="mdi-dots-vertical" size="small" variant="text" v-bind="props" />
                </template>
                <v-list density="compact">
                  <v-list-item prepend-icon="mdi-pencil" title="Edit" @click="$emit('edit', item)" />
                  <v-list-item prepend-icon="mdi-history" title="History" @click="$emit('history', item)" />
                  <v-list-item
                    prepend-icon="mdi-eye-outline"
                    title="Preview"
                    :href="siteUrl + '/collections/' + item.repositoryid"
                    target="_blank"
                  />
                  <v-list-item
                    prepend-icon="mdi-folder-cog-outline"
                    title="Manage studies"
                    :href="siteUrl + '/admin/collections/active/' + item.id"
                  />
                  <v-list-item
                    v-if="canManageAccess(item)"
                    prepend-icon="mdi-account-key"
                    title="User access"
                    @click="goPermissions(item)"
                  />
                  <v-divider />
                  <v-list-item
                    prepend-icon="mdi-delete"
                    title="Delete"
                    base-color="error"
                    @click="$emit('delete', item)"
                  />
                </v-list>
              </v-menu>
            </td>
          </tr>
        </tbody>
      </v-table>

      <div v-if="collections.length" class="admin-collections-results-footer">
        <v-row class="align-center">
          <v-col cols="12" sm="auto" class="d-flex align-center justify-center justify-sm-start ga-2 pb-2 pb-sm-0">
            <span class="text-body-2 text-medium-emphasis text-no-wrap">Per page</span>
            <v-select
              v-model="itemsPerPage"
              :items="pageSizeOptions"
              density="compact"
              variant="outlined"
              hide-details
              class="admin-collections-page-size-select"
            />
          </v-col>
          <v-col cols="12" sm class="d-flex align-center justify-center justify-sm-end ga-3 flex-wrap">
            <span class="text-body-2 text-medium-emphasis text-no-wrap">
              Showing {{ firstItem }} – {{ lastItem }} of {{ totalCollections }} collection{{ totalCollections === 1 ? '' : 's' }}
            </span>
            <v-pagination
              v-model="page"
              :length="totalPages"
              :total-visible="7"
              density="compact"
              color="primary"
            />
          </v-col>
        </v-row>
      </div>
    </template>

    <Teleport to="body">
      <v-dialog v-model="batchProcessing" max-width="360" persistent>
        <v-card class="pa-8 text-center" rounded="lg">
          <v-progress-circular indeterminate color="primary" size="56" width="4" class="mb-4" />
          <div class="text-body-1 text-medium-emphasis">Processing…</div>
        </v-card>
      </v-dialog>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useCollectionsApi } from '../composables/useCollectionsApi';
import $dialog from '@/shared/composables/dialog';

defineOptions({ name: 'AdminCollectionsResults' });

const props = defineProps({
  collections: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});

const emit = defineEmits(['edit', 'delete', 'history', 'publish-change', 'refresh']);

const router = useRouter();
const { baseUrl, siteUrl, config } = useAppConfig();
const { updateCollection, deleteCollection } = useCollectionsApi();
const defaultThumb = computed(() => `${baseUrl.value}files/thumbnails/thumbnail-default.png`);
const canManageCollectionAccess = computed(() => !!config.value?.canManageCollectionAccess);

const selected = ref([]);
const batchProcessing = ref(false);

const showUserAccessColumn = computed(() =>
  canManageCollectionAccess.value
  || props.collections.some(c => c.can_manage_access)
  || props.collections.some(c => (c.acl_user_count || 0) > 0)
);

const page = ref(1);
const itemsPerPage = ref(15);
const pageSizeOptions = [15, 50, 100];

const totalPages = computed(() =>
  Math.max(1, Math.ceil(props.collections.length / itemsPerPage.value))
);

const totalCollections = computed(() => props.collections.length);

const firstItem = computed(() => {
  if (!totalCollections.value) return 0;
  return (page.value - 1) * itemsPerPage.value + 1;
});

const lastItem = computed(() => {
  if (!totalCollections.value) return 0;
  const last = page.value * itemsPerPage.value;
  return Math.min(last, totalCollections.value);
});

const pagedCollections = computed(() => {
  const start = (page.value - 1) * itemsPerPage.value;
  return props.collections.slice(start, start + itemsPerPage.value);
});

const pageRepositoryIds = computed(() =>
  pagedCollections.value.map(c => c.repositoryid).filter(Boolean)
);

const selectAll = computed({
  get: () =>
    pageRepositoryIds.value.length > 0
    && pageRepositoryIds.value.every(id => selected.value.includes(id)),
  set: (val) => {
    if (val) {
      const merged = new Set([...selected.value, ...pageRepositoryIds.value]);
      selected.value = [...merged];
    } else {
      const pageSet = new Set(pageRepositoryIds.value);
      selected.value = selected.value.filter(id => !pageSet.has(id));
    }
  },
});

const isIndeterminate = computed(() => {
  const onPage = pageRepositoryIds.value.filter(id => selected.value.includes(id)).length;
  return onPage > 0 && onPage < pageRepositoryIds.value.length;
});

const selectedCollections = computed(() =>
  props.collections.filter(c => selected.value.includes(c.repositoryid))
);

watch(
  () => props.collections.length,
  () => {
    page.value = 1;
    selected.value = [];
  }
);

watch(itemsPerPage, () => {
  page.value = 1;
});

watch(
  () => props.collections,
  () => {
    if (page.value > totalPages.value) {
      page.value = totalPages.value;
    }
    const valid = new Set(props.collections.map(c => c.repositoryid));
    selected.value = selected.value.filter(id => valid.has(id));
  },
  { deep: true }
);

function canManageAccess(item) {
  return canManageCollectionAccess.value || !!item.can_manage_access;
}

function extraUserCount(item) {
  const total = item.acl_user_count || 0;
  const shown = Array.isArray(item.acl_users) ? item.acl_users.length : 0;
  return Math.max(0, total - shown);
}

function userDisplayName(user) {
  if (user?.display_name) {
    return user.display_name;
  }
  const first = (user?.first_name || '').trim();
  const last = (user?.last_name || '').trim();
  return [first, last].filter(Boolean).join(' ') || '—';
}

function goPermissions(item) {
  router.push({ name: 'collection-permissions', params: { repositoryId: String(item.id) } });
}

async function batchAction(action) {
  const items = selectedCollections.value;
  const n = items.length;
  if (n === 0) {
    await $dialog.alert({ title: 'No selection', message: 'Select at least one collection.' });
    return;
  }

  const actionLabel = action === 'delete' ? 'delete' : action;
  const confirmed = await $dialog.confirm({
    title: 'Confirm batch action',
    message: `${actionLabel.charAt(0).toUpperCase() + actionLabel.slice(1)} ${n} collection${n === 1 ? '' : 's'}?`,
    confirmText: action === 'delete' ? 'Delete' : 'Confirm',
  });
  if (!confirmed) return;

  let failed = 0;
  batchProcessing.value = true;
  try {
    for (const item of items) {
      try {
        if (action === 'publish') {
          await updateCollection({ repositoryid: item.repositoryid, ispublished: 1 }, { quiet: true });
        } else if (action === 'unpublish') {
          await updateCollection({ repositoryid: item.repositoryid, ispublished: 0 }, { quiet: true });
        } else if (action === 'delete') {
          await deleteCollection(item.repositoryid, { quiet: true });
        }
      } catch (e) {
        console.error(`Batch ${action} failed for`, item.repositoryid, e);
        failed++;
      }
    }
  } finally {
    batchProcessing.value = false;
  }

  selected.value = [];
  emit('refresh');

  if (failed > 0) {
    await $dialog.alert({
      title: 'Partially completed',
      message: `${failed} of ${n} failed. Check the console for details.`,
    });
  }
}
</script>
