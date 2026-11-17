<template>
  <v-row dense>
    <v-col cols="6" class="sl-field">
      <div class="sl-label">{{ t('sh_bg_size', 'Fit') }}</div>
      <v-select :model-value="size" :items="sizes" density="compact" variant="outlined" hide-details @update:model-value="(v) => $emit('update:size', v)" />
    </v-col>
    <v-col cols="6" class="sl-field">
      <div class="sl-label">{{ t('sh_bg_position', 'Position') }}</div>
      <v-select :model-value="position" :items="positions" density="compact" variant="outlined" hide-details @update:model-value="(v) => $emit('update:position', v)" />
    </v-col>
  </v-row>
</template>

<script setup>
import { computed } from 'vue';
import { useSiteHeaderStore } from '../composables/useSiteHeaderStore';

defineOptions({ name: 'ShBgFit' });
defineProps({
  size: { type: String, default: 'cover' },
  position: { type: String, default: 'center' },
});
defineEmits(['update:size', 'update:position']);

const { t } = useSiteHeaderStore();
const sizes = computed(() => [
  { title: t('sh_bg_cover', 'Fill the area'), value: 'cover' },
  { title: t('sh_bg_contain', 'Show the whole image'), value: 'contain' },
  { title: t('sh_bg_auto', 'Original size'), value: 'auto' },
]);
const positions = computed(() => [
  { title: t('sh_pos_center', 'Center'), value: 'center' },
  { title: t('sh_pos_top', 'Top'), value: 'top' },
  { title: t('sh_pos_bottom', 'Bottom'), value: 'bottom' },
  { title: t('sh_pos_left', 'Left'), value: 'left' },
  { title: t('sh_pos_right', 'Right'), value: 'right' },
]);
</script>
