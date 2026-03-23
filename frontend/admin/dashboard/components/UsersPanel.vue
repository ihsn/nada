<template>
  <div>
    <v-row v-if="users" dense class="mb-3">
      <v-col cols="4" class="text-center border rounded">
        <div class="text-h5 font-weight-bold text-success">{{ users.active.toLocaleString() }}</div>
        <div class="text-caption text-medium-emphasis">Active</div>
      </v-col>
      <v-col cols="4" class="text-center border rounded">
        <div class="text-h5 font-weight-bold text-error">{{ users.disabled.toLocaleString() }}</div>
        <div class="text-caption text-medium-emphasis">Disabled</div>
      </v-col>
      <v-col cols="4" class="text-center border rounded">
        <div class="text-h5 font-weight-bold text-medium-emphasis">{{ users.never_logged_in.toLocaleString() }}</div>
        <div class="text-caption text-medium-emphasis">Never login</div>
      </v-col>
    </v-row>

    <div class="text-subtitle-2 mb-2">Recent Logins</div>

    <v-table v-if="recentLogins.length" density="compact">
      <thead>
        <tr>
          <th>User</th>
          <th class="text-right">Last Login</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="u in recentLogins" :key="u.id">
          <td>
            <a :href="siteUrl + '/admin/users/edit/' + u.id" class="text-decoration-none">{{ u.username }}</a>
          </td>
          <td class="text-right text-caption">{{ u.last_login_fmt }}</td>
        </tr>
      </tbody>
    </v-table>

    <div v-if="!users" class="text-center text-medium-emphasis py-4">
      <v-progress-circular indeterminate color="primary" size="20" />
    </div>

    <div class="mt-3 text-right">
      <a :href="siteUrl + '/admin/users'" class="text-caption text-decoration-none">Manage users →</a>
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
</script>
