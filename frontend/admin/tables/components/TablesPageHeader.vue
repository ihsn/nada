<template>
  <div class="tables-page-header mb-4">
    <v-avatar :color="iconColor" variant="tonal" :size="avatarSize" rounded="lg" class="tables-page-header__icon">
      <v-icon :icon="icon" :size="iconSize" />
    </v-avatar>
    <div class="tables-page-header__body min-width-0">
      <div class="tables-page-header__title-row">
        <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-0 tables-page-header__title">
          {{ title }}
        </h1>
        <div v-if="$slots.meta" class="tables-page-header__meta d-flex flex-wrap align-center gap-2">
          <slot name="meta" />
        </div>
      </div>
      <p v-if="subtitle" class="text-body-2 text-medium-emphasis mb-0 mt-1 tables-page-header__subtitle">
        {{ subtitle }}
      </p>
      <slot name="subtitle" />
    </div>
    <div v-if="$slots.actions" class="tables-page-header__actions">
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup>
defineOptions({ name: 'TablesPageHeader' });

defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  icon: { type: String, default: 'mdi-table' },
  iconColor: { type: String, default: 'primary' },
  avatarSize: { type: [Number, String], default: 48 },
  iconSize: { type: [Number, String], default: 26 },
});
</script>
