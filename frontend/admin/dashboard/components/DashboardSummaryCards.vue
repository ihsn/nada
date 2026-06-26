<template>
  <div class="dashboard-summary-grid mb-2">
    <a
      v-for="card in cards"
      :key="card.key"
      :href="card.href"
      class="dashboard-summary-card-link d-block h-100 min-w-0 text-decoration-none"
    >
        <v-sheet
          elevation="0"
          class="dashboard-summary-card h-100 d-flex flex-column"
          :class="{ 'dashboard-summary-card--attention': card.attention }"
        >
          <div class="dashboard-summary-card__body d-flex align-start ga-3">
            <div
              class="dashboard-summary-card__icon-bubble"
              :class="`dashboard-summary-card__icon-bubble--${card.bubbleTone}`"
            >
              <v-icon :icon="card.icon" size="26" />
            </div>
            <div class="dashboard-summary-card__main flex-grow-1 min-w-0">
              <div class="dashboard-summary-card__label">
                {{ card.label }}
              </div>
              <div class="dashboard-summary-card__value">
                {{ card.value }}
              </div>
              <div v-if="card.caption" class="dashboard-summary-card__caption">
                {{ card.caption }}
              </div>
            </div>
            <v-icon
              icon="mdi-arrow-top-right"
              size="18"
              class="dashboard-summary-card__corner-arrow flex-shrink-0"
              aria-hidden="true"
            />
          </div>
        </v-sheet>
      </a>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  siteUrl: { type: String, required: true },
  stats: { type: Object, required: true },
  translations: { type: Object, default: () => ({}) },
});

function tr(key, fallback) {
  const v = props.translations[key];
  return v != null && v !== '' ? v : fallback;
}

const cards = computed(() => {
  const base = String(props.siteUrl || '').replace(/\/$/, '');
  const catalog = props.stats.catalog || {};
  const collections = Array.isArray(props.stats.collections) ? props.stats.collections : [];
  const lr = props.stats.license_requests || {};
  const users = props.stats.users || {};

  const totalStudies = Number(catalog.total) || 0;
  const published = Number(catalog.published) || 0;
  const unpublished = Number(catalog.unpublished) || 0;

  const nonCentralCollections = collections.filter((row) => row && row.repo_key !== 'central');
  const collectionCount = nonCentralCollections.length;
  const studiesInCollections = nonCentralCollections.reduce(
    (sum, row) => sum + (Number(row.total) || 0),
    0,
  );

  const pending = Number(lr.pending) || 0;
  const usersTotal = Number(users.total) || 0;
  const usersActive = Number(users.active) || 0;

  const licenseBubble = pending > 0 ? 'warning' : 'success';

  return [
    {
      key: 'catalog',
      href: `${base}/admin/catalog`,
      icon: 'mdi-book-open-variant',
      bubbleTone: 'primary',
      attention: false,
      label: tr('dashboard_summary_catalog', 'Catalog'),
      value: totalStudies.toLocaleString(),
      caption: tr('dashboard_summary_catalog_caption', '{published} published · {draft} draft')
        .replace('{published}', published.toLocaleString())
        .replace('{draft}', unpublished.toLocaleString()),
    },
    {
      key: 'collections',
      href: `${base}/admin/collections`,
      icon: 'mdi-folder-multiple',
      bubbleTone: 'indigo',
      attention: false,
      label: tr('dashboard_summary_collections', 'Collections'),
      value: collectionCount.toLocaleString(),
      caption:
        collectionCount === 0
          ? tr('dashboard_summary_collections_caption_none', 'No additional collections')
          : tr('dashboard_summary_collections_caption', '{studies} studies').replace(
              '{studies}',
              studiesInCollections.toLocaleString(),
            ),
    },
    {
      key: 'licenses',
      href: `${base}/admin/licensed_requests`,
      icon: 'mdi-file-document-outline',
      bubbleTone: licenseBubble,
      attention: pending > 0,
      label: tr('dashboard_summary_licenses', 'Licensed requests'),
      value: pending.toLocaleString(),
      caption:
        pending > 0
          ? tr('dashboard_summary_licenses_pending', 'Pending approval')
          : tr('dashboard_summary_licenses_none', 'No pending requests'),
    },
    {
      key: 'users',
      href: `${base}/admin/users`,
      icon: 'mdi-account-group',
      bubbleTone: 'slate',
      attention: false,
      label: tr('dashboard_summary_users', 'Users'),
      value: usersTotal.toLocaleString(),
      caption: tr('dashboard_summary_users_caption', '{active} active').replace(
        '{active}',
        usersActive.toLocaleString(),
      ),
    },
  ];
});
</script>

<style scoped>
/* Subtle corner radius on stat cards only (keep very small). */
.dashboard-summary-card-link {
  color: inherit;
  border-radius: 4px;
}

.dashboard-summary-card-link:focus-visible {
  outline: 2px solid rgb(var(--v-theme-primary));
  outline-offset: 3px;
  border-radius: 4px;
}

.dashboard-summary-card {
  position: relative;
  overflow: hidden;
  border-radius: 4px;
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: linear-gradient(165deg, rgb(255, 255, 255) 0%, rgb(248, 250, 252) 100%);
  box-shadow:
    0 1px 2px rgba(15, 23, 42, 0.04),
    0 4px 16px rgba(15, 23, 42, 0.04);
  transition:
    box-shadow 0.22s ease,
    border-color 0.22s ease,
    transform 0.22s ease;
}

.dashboard-summary-card__body {
  padding: 18px;
  min-height: 132px;
}

.dashboard-summary-card__icon-bubble {
  flex-shrink: 0;
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.2s ease;
}

.dashboard-summary-card__icon-bubble--primary {
  background: rgba(var(--v-theme-primary), 0.12);
  color: rgb(var(--v-theme-primary));
}

.dashboard-summary-card__icon-bubble--indigo {
  background: rgba(57, 73, 171, 0.12);
  color: #3949ab;
}

.dashboard-summary-card__icon-bubble--warning {
  background: rgba(var(--v-theme-warning), 0.16);
  color: rgb(var(--v-theme-warning));
}

.dashboard-summary-card__icon-bubble--success {
  background: rgba(var(--v-theme-success), 0.14);
  color: rgb(var(--v-theme-success));
}

.dashboard-summary-card__icon-bubble--slate {
  background: rgba(84, 110, 122, 0.14);
  color: #455a64;
}

.dashboard-summary-card__label {
  font-size: 0.6875rem;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(var(--v-theme-on-surface), 0.55);
  line-height: 1.25;
  margin-bottom: 6px;
}

.dashboard-summary-card__value {
  font-size: 1.75rem;
  font-weight: 700;
  line-height: 1.1;
  letter-spacing: -0.03em;
  font-variant-numeric: tabular-nums;
  color: rgb(var(--v-theme-on-surface));
}

.dashboard-summary-card__caption {
  margin-top: 8px;
  font-size: 0.8125rem;
  line-height: 1.4;
  color: rgba(var(--v-theme-on-surface), 0.58);
  overflow-wrap: anywhere;
  word-break: break-word;
}

.dashboard-summary-card__corner-arrow {
  margin-top: 2px;
  color: rgba(var(--v-theme-on-surface), 0.28);
  transition: color 0.2s ease, transform 0.2s ease;
}

.dashboard-summary-card--attention {
  border-color: rgba(var(--v-theme-warning), 0.42);
  background: linear-gradient(
    165deg,
    rgba(var(--v-theme-warning), 0.07) 0%,
    rgb(255, 252, 247) 42%,
    rgb(248, 250, 252) 100%
  );
  box-shadow:
    0 1px 2px rgba(245, 124, 0, 0.06),
    0 6px 20px rgba(245, 124, 0, 0.08);
}

@media (hover: hover) and (pointer: fine) {
  .dashboard-summary-card-link:hover .dashboard-summary-card {
    transform: translateY(-4px);
    border-color: rgba(var(--v-theme-primary), 0.22);
    box-shadow:
      0 4px 8px rgba(15, 23, 42, 0.06),
      0 16px 40px rgba(15, 23, 42, 0.1);
  }

  .dashboard-summary-card-link:hover .dashboard-summary-card__icon-bubble {
    transform: scale(1.04);
  }

  .dashboard-summary-card-link:hover .dashboard-summary-card__corner-arrow {
    color: rgb(var(--v-theme-primary));
    transform: translate(2px, -2px);
  }

  .dashboard-summary-card-link:hover .dashboard-summary-card--attention {
    border-color: rgba(var(--v-theme-warning), 0.55);
  }
}

/* Narrow screens: single column (grid in App.vue); ease density & wrapping */
@media (max-width: 519px) {
  .dashboard-summary-card__body {
    padding: 12px 14px;
    min-height: 0;
    gap: 10px !important;
  }

  .dashboard-summary-card__label {
    font-size: 0.625rem;
    letter-spacing: 0.06em;
    margin-bottom: 4px;
  }

  .dashboard-summary-card__value {
    font-size: 1.35rem;
  }

  .dashboard-summary-card__caption {
    font-size: 0.75rem;
    margin-top: 6px;
    line-height: 1.45;
  }

  .dashboard-summary-card__icon-bubble {
    width: 40px;
    height: 40px;
    border-radius: 10px;
  }

  .dashboard-summary-card__icon-bubble :deep(.v-icon) {
    font-size: 22px !important;
  }

  .dashboard-summary-card__corner-arrow {
    display: none;
  }
}

@media (min-width: 520px) and (max-width: 1179px) {
  .dashboard-summary-card__body {
    min-height: 0;
    padding: 14px 16px;
  }

  .dashboard-summary-card__value {
    font-size: 1.5rem;
  }

  .dashboard-summary-card__caption {
    font-size: 0.75rem;
    line-height: 1.45;
  }
}
</style>
