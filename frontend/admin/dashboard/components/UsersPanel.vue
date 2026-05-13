<template>
  <div class="users-panel">
    <template v-if="users">
      <div class="users-panel__stats-wrap">
        <v-row dense class="users-panel__stats ga-2">
        <v-col cols="12" sm="4">
          <v-sheet
            rounded="lg"
            border
            class="users-panel__stat-tile text-center bg-grey-lighten-4"
          >
            <div class="text-body-2 font-weight-bold">{{ users.active.toLocaleString() }}</div>
            <div class="text-caption text-medium-emphasis">Active</div>
          </v-sheet>
        </v-col>
        <v-col cols="12" sm="4">
          <v-sheet
            rounded="lg"
            border
            class="users-panel__stat-tile text-center bg-grey-lighten-4"
          >
            <div class="text-body-2 font-weight-bold">{{ users.disabled.toLocaleString() }}</div>
            <div class="text-caption text-medium-emphasis">Disabled</div>
          </v-sheet>
        </v-col>
        <v-col cols="12" sm="4">
          <v-sheet
            rounded="lg"
            border
            class="users-panel__stat-tile text-center bg-grey-lighten-4"
          >
            <div class="text-body-2 font-weight-bold">
              {{ users.never_logged_in.toLocaleString() }}
            </div>
            <div class="text-caption text-medium-emphasis">Never logged in</div>
          </v-sheet>
        </v-col>
        </v-row>
      </div>

      <div class="users-panel__section-head d-flex align-center mb-2">
        <v-icon size="small" class="mr-2 text-medium-emphasis">mdi-login-variant</v-icon>
        <span class="text-body-2 font-weight-medium">Recent logins</span>
      </div>

      <v-list
        v-if="recentLogins.length"
        density="compact"
        class="users-panel__list bg-transparent pa-0"
        lines="two"
      >
        <v-list-item
          v-for="u in recentLogins"
          :key="u.id"
          :href="editUserHref(u.id)"
        >
          <template #title>
            <span class="text-body-2">{{ u.username }}</span>
          </template>
          <template #prepend>
            <v-avatar color="primary" variant="tonal" size="36">
              <span class="text-caption font-weight-medium">{{ initials(u.username) }}</span>
            </v-avatar>
          </template>
          <template #subtitle>
            <span class="text-caption text-medium-emphasis">Last login · {{ u.last_login_fmt }}</span>
          </template>
        </v-list-item>
      </v-list>

      <v-sheet
        v-else
        rounded="lg"
        border
        class="pa-4 text-center text-body-2 text-medium-emphasis"
      >
        No recent logins to show.
      </v-sheet>
    </template>

    <div v-else class="text-center text-medium-emphasis py-6">
      <v-progress-circular indeterminate color="primary" size="24" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  users: { type: Object, default: null },
  siteUrl: { type: String, default: '' },
});

const recentLogins = computed(() => props.users?.recent_logins ?? []);

function editUserHref(id) {
  return `${props.siteUrl}/admin/users/edit/${id}`;
}

function initials(username) {
  if (!username || typeof username !== 'string') return '?';
  const parts = username.trim().split(/\s+/);
  if (parts.length >= 2) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase().slice(0, 2);
  }
  return username.slice(0, 2).toUpperCase();
}
</script>

<style scoped>
.users-panel__stats-wrap {
  padding-bottom: 28px;
  margin-bottom: 4px;
}
.users-panel__stat-tile {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 2px;
  min-height: 56px;
  padding: 10px 8px;
}
.users-panel__list :deep(.v-list-item) {
  border-radius: 8px;
  margin-bottom: 4px;
}
.users-panel__list :deep(.v-list-item:last-child) {
  margin-bottom: 0;
}
.users-panel__section-head {
  letter-spacing: 0.01em;
}
</style>
