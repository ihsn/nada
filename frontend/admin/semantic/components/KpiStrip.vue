<template>
  <div class="semantic-kpi-grid">
    <template v-if="loading">
      <v-skeleton-loader
        v-for="n in 4"
        :key="'sk-' + n"
        type="image"
        height="120"
        class="rounded"
      />
    </template>
    <template v-else>
      <component
        :is="card.to ? 'router-link' : 'div'"
        v-for="card in tiles"
        :key="card.key"
        :to="card.to || undefined"
        class="semantic-kpi-item"
        :class="{ 'semantic-kpi-item--link': !!card.to }"
      >
      <div
        class="semantic-kpi-card"
        :class="{ 'semantic-kpi-card--attention': card.attention }"
      >
        <div
          class="semantic-kpi-card__icon"
          :class="`semantic-kpi-card__icon--${card.tone}`"
        >
          <v-icon :icon="card.icon" size="22" />
        </div>
        <div class="semantic-kpi-card__main">
          <div class="semantic-kpi-card__label">{{ card.label }}</div>
          <div class="semantic-kpi-card__value">{{ formatCount(card.value) }}</div>
          <div v-if="card.caption" class="semantic-kpi-card__caption">{{ card.caption }}</div>
        </div>
        <v-icon
          v-if="card.to"
          icon="mdi-arrow-top-right"
          size="16"
          class="semantic-kpi-card__arrow"
          aria-hidden="true"
        />
      </div>
    </component>
    </template>
  </div>
</template>

<script setup>
import { formatCount } from '../typeLabels.js';

defineOptions({ name: 'SemanticKpiStrip' });

defineProps({
  tiles: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});
</script>

<style scoped>
.semantic-kpi-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
  width: 100%;
}

@media (min-width: 960px) {
  .semantic-kpi-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

.semantic-kpi-item {
  min-width: 0;
  color: inherit;
  text-decoration: none;
}

.semantic-kpi-item--link:focus-visible {
  outline: 2px solid rgb(var(--v-theme-primary));
  outline-offset: 3px;
  border-radius: 4px;
}

.semantic-kpi-card {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  height: 100%;
  padding: 16px;
  border-radius: 4px;
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: rgb(var(--v-theme-surface));
  box-shadow:
    0 1px 2px rgba(15, 23, 42, 0.04),
    0 4px 16px rgba(15, 23, 42, 0.04);
  transition: box-shadow 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
}

.semantic-kpi-card--attention {
  border-color: rgba(var(--v-theme-warning), 0.42);
  background: rgb(var(--v-theme-surface));
}

.semantic-kpi-card__icon {
  flex-shrink: 0;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.semantic-kpi-card__icon--success {
  background: rgba(var(--v-theme-success), 0.14);
  color: rgb(var(--v-theme-success));
}

.semantic-kpi-card__icon--warning {
  background: rgba(var(--v-theme-warning), 0.16);
  color: rgb(var(--v-theme-warning));
}

.semantic-kpi-card__icon--error {
  background: rgba(var(--v-theme-error), 0.14);
  color: rgb(var(--v-theme-error));
}

.semantic-kpi-card__icon--stale {
  background: rgba(255, 87, 34, 0.14);
  color: #e64a19;
}

.semantic-kpi-card__main {
  flex: 1 1 auto;
  min-width: 0;
}

.semantic-kpi-card__label {
  font-size: 0.6875rem;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(var(--v-theme-on-surface), 0.55);
  line-height: 1.25;
  margin-bottom: 4px;
}

.semantic-kpi-card__value {
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1.1;
  letter-spacing: -0.03em;
  font-variant-numeric: tabular-nums;
  color: rgb(var(--v-theme-on-surface));
}

.semantic-kpi-card__caption {
  margin-top: 6px;
  font-size: 0.75rem;
  line-height: 1.4;
  color: rgba(var(--v-theme-on-surface), 0.58);
}

.semantic-kpi-card__arrow {
  color: rgba(var(--v-theme-on-surface), 0.28);
  margin-top: 2px;
}

@media (hover: hover) and (pointer: fine) {
  .semantic-kpi-item--link:hover .semantic-kpi-card {
    transform: translateY(-2px);
    border-color: rgba(var(--v-theme-primary), 0.22);
    box-shadow:
      0 4px 8px rgba(15, 23, 42, 0.06),
      0 12px 28px rgba(15, 23, 42, 0.08);
  }

  .semantic-kpi-item--link:hover .semantic-kpi-card__arrow {
    color: rgb(var(--v-theme-primary));
  }
}
</style>
