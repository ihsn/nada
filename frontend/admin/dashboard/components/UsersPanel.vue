<template>
  <div class="users-panel">
    <template v-if="users">
      <div class="users-panel__stats-wrap">
        <div class="users-panel__stats">
          <v-sheet
            v-for="tile in statTiles"
            :key="tile.key"
            rounded="lg"
            border
            class="users-panel__stat-tile"
            :class="`users-panel__stat-tile--${tile.tone}`"
          >
            <div class="users-panel__stat-tile-inner">
              <div class="users-panel__stat-value">{{ tile.value.toLocaleString() }}</div>
              <div class="users-panel__stat-label">{{ tile.label }}</div>
            </div>
          </v-sheet>
        </div>
      </div>

      <div class="users-panel__section-head d-flex align-center mb-2">
        <v-icon size="small" class="mr-2 text-medium-emphasis">mdi-login-variant</v-icon>
        <span class="users-panel__section-head-text">Recent logins</span>
      </div>

      <v-list
        v-if="recentLogins.length"
        density="compact"
        class="users-panel__list users-panel__list--logins bg-transparent pa-0"
        lines="two"
      >
        <v-list-item
          v-for="u in recentLogins"
          :key="u.id"
          :href="editUserHref(u.id)"
        >
          <template #title>
            <span class="users-panel__login-name">{{ u.username }}</span>
          </template>
          <template #prepend>
            <v-avatar color="primary" variant="tonal" size="32">
              <span class="users-panel__login-initials font-weight-medium">{{ initials(u.username) }}</span>
            </v-avatar>
          </template>
          <template #subtitle>
            <span class="users-panel__login-meta">Last login · {{ u.last_login_fmt }}</span>
          </template>
        </v-list-item>
      </v-list>

      <v-sheet
        v-else
        rounded="lg"
        border
        class="users-panel__empty-logins pa-4 text-center"
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

const statTiles = computed(() => {
  const u = props.users;
  if (!u) return [];
  return [
    {
      key: 'active',
      value: Number(u.active) || 0,
      label: 'Active',
      tone: 'success',
    },
    {
      key: 'disabled',
      value: Number(u.disabled) || 0,
      label: 'Disabled',
      tone: 'neutral',
    },
    {
      key: 'never_logged_in',
      value: Number(u.never_logged_in) || 0,
      label: 'Never logged in',
      tone: 'caution',
    },
  ];
});

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
  padding-bottom: 20px;
  margin-bottom: 4px;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08);
}

.users-panel__stats {
  display: grid;
  grid-template-columns: 1fr;
  gap: 10px;
}

@media (min-width: 600px) {
  .users-panel__stats {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
  }
}

.users-panel__stat-tile {
  overflow: hidden;
  border-color: rgba(var(--v-theme-on-surface), 0.1) !important;
  background: rgb(var(--v-theme-surface));
  transition: box-shadow 0.18s ease, border-color 0.18s ease;
}

@media (hover: hover) and (pointer: fine) {
  .users-panel__stat-tile:hover {
    border-color: rgba(var(--v-theme-on-surface), 0.16) !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  }
}

.users-panel__stat-tile-inner {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  gap: 4px;
  min-height: 80px;
  padding: 14px 12px 16px;
}

.users-panel__stat-tile--success {
  background: linear-gradient(
    165deg,
    rgba(var(--v-theme-success), 0.1) 0%,
    rgb(var(--v-theme-surface)) 48%
  );
}

.users-panel__stat-tile--neutral {
  background: linear-gradient(
    165deg,
    rgba(var(--v-theme-on-surface), 0.06) 0%,
    rgb(var(--v-theme-surface)) 48%
  );
}

.users-panel__stat-tile--caution {
  background: linear-gradient(
    165deg,
    rgba(var(--v-theme-warning), 0.12) 0%,
    rgb(var(--v-theme-surface)) 48%
  );
}

.users-panel__stat-value {
  font-size: 1.25rem;
  font-weight: 700;
  letter-spacing: -0.02em;
  line-height: 1.15;
  color: rgb(var(--v-theme-on-surface));
}

.users-panel__stat-label {
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.01em;
  line-height: 1.3;
  color: rgba(var(--v-theme-on-surface), 0.55);
  max-width: 12rem;
}

.users-panel__section-head {
  letter-spacing: 0.01em;
  margin-top: 1.25rem;
}

.users-panel__section-head-text {
  font-size: 0.8125rem;
  font-weight: 600;
  letter-spacing: 0.015em;
  line-height: 1.4;
  color: rgba(var(--v-theme-on-surface), 0.65);
}

.users-panel__empty-logins {
  border-color: rgba(var(--v-theme-on-surface), 0.1) !important;
  background: rgba(var(--v-theme-on-surface), 0.03);
  font-size: 0.75rem;
  font-weight: 500;
  line-height: 1.35;
  letter-spacing: 0.01em;
  color: rgba(var(--v-theme-on-surface), 0.55);
}

.users-panel__list--logins :deep(.v-list-item) {
  border-radius: 0;
  margin-bottom: 0;
  min-height: 48px;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08);
}

.users-panel__list--logins :deep(.v-list-item:last-child) {
  border-bottom: none;
}

.users-panel__login-name {
  font-size: 0.875rem;
  font-weight: 600;
  line-height: 1.35;
  letter-spacing: -0.01em;
  color: rgb(var(--v-theme-on-surface));
}

.users-panel__login-meta {
  font-size: 0.6875rem;
  font-weight: 500;
  line-height: 1.35;
  letter-spacing: 0.01em;
  color: rgba(var(--v-theme-on-surface), 0.55);
}

.users-panel__login-initials {
  font-size: 0.65rem;
}
</style>
