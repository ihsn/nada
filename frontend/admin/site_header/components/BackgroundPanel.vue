<template>
  <div class="sl-stack">
    <div v-if="editorView === 'builder'" class="sl-field">
      <v-radio-group v-model="form.background_scope" density="compact" hide-details>
        <v-radio value="header" :label="t('sh_scope_header', 'One background for the whole header')" color="primary" />
        <v-radio value="rows" :label="t('sh_scope_rows', 'A separate background and text colour for each row')" color="primary" />
      </v-radio-group>
      <div v-if="form.background_scope === 'rows'" class="sl-help">
        {{ t('sh_scope_rows_help', 'Set each row’s background and colours in its own section: Top bar, Main row, Bottom bar.') }}
      </div>
    </div>

    <template v-if="form.background_scope === 'header' || editorView === 'custom'">
      <v-row dense>
        <v-col cols="6" class="sl-field">
          <div class="sl-label">{{ t('sh_bg_color', 'Background colour') }}</div>
          <sh-color-field v-model="style.bg_color" :placeholder="t('sh_theme_default', 'Default')" :error="fieldMsg('bg_color')" />
        </v-col>
        <v-col cols="6" class="sl-field">
          <div class="sl-label">{{ t('sh_text_color', 'Text and link colour') }}</div>
          <sh-color-field v-model="style.text_color" :placeholder="t('sh_theme_default', 'Default')" :error="fieldMsg('text_color')" />
        </v-col>
      </v-row>
      <div class="sl-field">
        <div class="sl-label">{{ t('sh_bg_image', 'Background image') }}</div>
        <sh-image-upload
          kind="background"
          :url="style.bg_image_url || ''"
          :empty-text="t('sh_no_bg_image', 'Upload a background image')"
          :hint="t('sh_bg_hint', 'PNG, JPG, GIF or WebP · up to 5 MB. Wide images (2000px+) look best.')"
          @uploaded="(r) => { style.bg_image = r.path; style.bg_image_url = r.url; }"
          @clear="style.bg_image = ''; style.bg_image_url = ''"
        />
      </div>
      <sh-bg-fit v-if="style.bg_image" v-model:size="style.bg_size" v-model:position="style.bg_position" />
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useSiteHeaderStore } from '../composables/useSiteHeaderStore';
import ShColorField from './ColorField.vue';
import ShImageUpload from './ImageUpload.vue';
import ShBgFit from './BgFit.vue';

defineOptions({ name: 'ShBackgroundPanel' });

const { form, editorView, fieldErrors, message, t } = useSiteHeaderStore();
const style = computed(() => form.value.header_style);

function fieldMsg(key) {
  const code = fieldErrors.value?.[`header_style.${key}`];
  return code ? message(code) : '';
}
</script>
