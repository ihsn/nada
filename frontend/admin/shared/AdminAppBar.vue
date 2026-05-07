<template>
  <div>
    <v-app-bar
      :style="{ backgroundColor: adminHeaderBackground }"
      density="comfortable"
      elevation="1"
      class="admin-app-bar"
    >
      <template v-if="!mdAndUp" #prepend>
        <v-app-bar-nav-icon
          variant="text"
          color="white"
          aria-label="Open menu"
          @click="drawer = true"
        />
      </template>

      <v-toolbar-title class="text-truncate pr-3 pl-2 pl-md-4 admin-app-bar-brand">
        <a class="text-white text-decoration-none font-weight-semibold text-h6" :href="urls.adminHome">
          NADA <span class="text-subtitle-1 font-weight-regular text-medium-emphasis">{{ appVersion }}</span>
        </a>
      </v-toolbar-title>

      <!-- Desktop / tablet primary nav -->
      <div v-if="mdAndUp" class="d-flex align-center flex-grow-1 overflow-x-auto nav-scroll mx-1 hide-scrollbar">
        <template v-for="(item, i) in navItems" :key="'nav-' + i">
          <v-btn
            v-if="item.kind === 'link'"
            class="text-none text-white admin-nav-btn text-body-1"
            variant="text"
            :href="item.href"
          >
            {{ item.title }}
          </v-btn>

          <v-menu v-else-if="item.kind === 'menu'" location="bottom">
            <template #activator="{ props }">
              <v-btn
                v-bind="props"
                class="text-none text-white admin-nav-btn text-body-1"
                variant="text"
                append-icon="mdi-menu-down"
              >
                {{ item.title }}
              </v-btn>
            </template>
            <v-list density="comfortable" class="admin-nav-menu-list py-2 text-body-1" max-height="480">
              <template v-for="(child, j) in item.children" :key="'c-' + i + '-' + j">
                <v-divider v-if="child.kind === 'divider'" class="my-1" />
                <v-list-item v-else :href="child.href" :title="child.title" link />
              </template>
            </v-list>
          </v-menu>
        </template>
      </div>

      <v-spacer />

      <v-menu v-if="userName" location="bottom end">
        <template #activator="{ props }">
          <v-btn
            v-bind="props"
            variant="text"
            class="text-none text-white admin-nav-btn text-body-1"
            append-icon="mdi-menu-down"
          >
            {{ userName }}
          </v-btn>
        </template>
        <v-list density="comfortable" min-width="220" class="text-body-1 py-1">
          <v-list-item
            v-if="user.impersonating"
            :href="urls.exitImpersonate"
            :title="labels.exitImpersonate"
            prepend-icon="mdi-account-switch"
            link
          />
          <v-list-item
            :href="urls.changePassword"
            :title="labels.changePassword"
            prepend-icon="mdi-key-variant"
            link
          />
          <v-list-item :href="urls.logout" :title="labels.logout" prepend-icon="mdi-logout" link />
          <v-divider class="my-1" />
          <v-list-item :href="urls.home" :title="labels.home" target="_blank" prepend-icon="mdi-home" link />
          <v-list-item
            :href="urls.dataCatalog"
            :title="labels.dataCatalog"
            target="_blank"
            prepend-icon="mdi-book-open-variant"
            link
          />
          <v-list-item
            :href="urls.citations"
            :title="labels.citations"
            target="_blank"
            prepend-icon="mdi-format-quote-close"
            link
          />
        </v-list>
      </v-menu>
    </v-app-bar>

    <!-- Mobile navigation -->
    <v-navigation-drawer v-model="drawer" temporary location="left" width="320" class="admin-nav-drawer">
      <v-list density="comfortable" nav class="py-2 text-body-1">
        <template v-for="(item, i) in navItems" :key="'m-' + i">
          <v-list-item
            v-if="item.kind === 'link'"
            :href="item.href"
            :title="item.title"
            link
            @click="drawer = false"
          />

          <v-list-group v-else-if="item.kind === 'menu'">
            <template #activator="{ props: actProps }">
              <v-list-item v-bind="actProps" :title="item.title" :href="item.href" link />
            </template>
            <template v-for="(child, j) in item.children" :key="'mc-' + j">
              <v-divider v-if="child.kind === 'divider'" class="my-1" />
              <v-list-item
                v-else
                :href="child.href"
                :title="child.title"
                link
                @click="drawer = false"
              />
            </template>
          </v-list-group>
        </template>
      </v-list>
    </v-navigation-drawer>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useDisplay } from 'vuetify';

defineOptions({ name: 'AdminAppBar' });

const { mdAndUp } = useDisplay();

const raw = typeof window !== 'undefined' && window.ADMIN_HEADER_CONFIG ? window.ADMIN_HEADER_CONFIG : {};

const navItems = computed(() => (Array.isArray(raw.nav) ? raw.nav : []));
const appVersion = computed(() => raw.appVersion || '');
const user = computed(() => raw.user || {});
const urls = computed(() => raw.urls || {});
const labels = computed(() =>
  Object.assign(
    {
      changePassword: 'Change password',
      logout: 'Logout',
      home: 'Home',
      dataCatalog: 'Data catalog',
      citations: 'Citations',
      exitImpersonate: 'Exit impersonate',
    },
    raw.labels || {},
  ),
);
const adminHeaderBackground = computed(() => {
  const value = (raw.headerBackground || '').trim();
  return /^#[0-9a-fA-F]{6}$/.test(value) ? value : '#212121';
});

const userName = computed(() => (user.value.name || '').trim());

const drawer = ref(false);
</script>

<style scoped>
.nav-scroll {
  max-width: 100%;
}
.hide-scrollbar {
  scrollbar-width: none;
}
.hide-scrollbar::-webkit-scrollbar {
  display: none;
}
.admin-nav-menu-list {
  min-width: 240px;
}

/* Slightly larger tap targets and type on the bar */
.admin-app-bar :deep(.admin-nav-btn) {
  min-height: 44px;
  padding-inline: 14px;
  letter-spacing: 0.01em;
}

.admin-app-bar-brand :deep(.text-medium-emphasis) {
  color: rgba(255, 255, 255, 0.75) !important;
}
</style>
