<template>
  <div class="sh-row-panel sl-stack">
    <v-switch v-model="row.enabled" :label="t('sh_row_show', 'Show this row')" color="primary" density="compact" hide-details />

    <template v-if="row.enabled">
      <div class="sl-section-label">{{ t('sh_row_items', 'Items') }}</div>
      <div>
        <sh-slot-editor v-for="s in SLOTS" :key="s" :row-id="rowId" :slot-name="s" />
      </div>
      <div v-if="isEmpty" class="sl-help">
        {{ t('sh_row_empty', 'This row is empty, so it is not shown. Add items with the + buttons.') }}
      </div>
      <v-alert v-if="row.style.hide_mobile && phoneLoss.length" type="warning" variant="tonal" density="compact">
        {{ t('sh_row_hidden_phone', 'This row is hidden on phones, so phone visitors will not see: %s.', phoneLoss.join(', ')) }}
      </v-alert>

      <div class="sl-section-label">{{ t('sh_row_style', 'Background and colours') }}</div>
      <template v-if="form.background_scope === 'rows'">
        <v-row dense>
          <v-col cols="6" class="sl-field">
            <div class="sl-label">{{ t('sh_bg_color', 'Background colour') }}</div>
            <sh-color-field v-model="row.style.bg_color" :placeholder="t('sh_none', 'None')" :error="fieldMsg('bg_color')" />
          </v-col>
          <v-col cols="6" class="sl-field">
            <div class="sl-label">{{ t('sh_text_color', 'Text and link colour') }}</div>
            <sh-color-field v-model="row.style.text_color" :placeholder="t('sh_theme_default', 'Default')" :error="fieldMsg('text_color')" />
          </v-col>
        </v-row>
        <div class="sl-field">
          <div class="sl-label">{{ t('sh_bg_image', 'Background image') }}</div>
          <sh-image-upload
            kind="background"
            :url="row.style.bg_image_url || ''"
            :empty-text="t('sh_no_bg_image', 'Upload a background image')"
            :hint="t('sh_bg_hint', 'PNG, JPG, GIF or WebP · up to 5 MB. Wide images (2000px+) look best.')"
            @uploaded="(r) => { row.style.bg_image = r.path; row.style.bg_image_url = r.url; }"
            @clear="row.style.bg_image = ''; row.style.bg_image_url = ''"
          />
        </div>
        <sh-bg-fit v-if="row.style.bg_image" v-model:size="row.style.bg_size" v-model:position="row.style.bg_position" />
      </template>
      <div v-else class="sl-help">
        {{ t('sh_row_uses_header_bg', 'This row uses the header background.') }}
        <a href="#" @click.prevent="form.background_scope = 'rows'">{{ t('sh_use_row_bg', 'Give each row its own background') }}</a>
      </div>

      <div class="sl-section-label">{{ t('sh_row_size', 'Size') }}</div>
      <div class="sl-field">
        <div class="sl-label">{{ t('sh_min_height', 'Minimum height') }}</div>
        <div class="sh-size-stack">
          <sh-size-field v-model="row.style.min_height" :max="400" icon="mdi-monitor" :icon-title="t('sh_desktop', 'Desktop and tablet')" :zero-label="t('sh_auto', 'auto')" />
          <sh-size-field v-model="row.style.min_height_mobile" :max="400" icon="mdi-cellphone" :icon-title="t('sh_phone', 'Phone')" :zero-label="t('sh_auto', 'auto')" />
        </div>
      </div>
      <div class="sl-field">
        <div class="sl-label">{{ t('sh_padding', 'Space above and below') }}</div>
        <div class="sh-size-stack">
          <sh-size-field v-model="row.style.padding_y" :max="80" icon="mdi-monitor" :icon-title="t('sh_desktop', 'Desktop and tablet')" />
          <sh-size-field v-model="row.style.padding_y_mobile" :max="80" icon="mdi-cellphone" :icon-title="t('sh_phone', 'Phone')" />
        </div>
        <div class="sl-help">
          {{ t('sh_size_help', 'The row grows to fit its content, so a long menu or translation never gets cut off.') }}
        </div>
      </div>

      <div class="sl-section-label">{{ t('sh_options', 'Options') }}</div>
      <div class="sl-switches">
        <v-switch v-model="row.style.full_width" :label="t('sh_full_width', 'Content uses the full page width')" color="primary" density="compact" hide-details />
        <v-switch v-model="row.style.border_bottom" :label="t('sh_border', 'Line under the row')" color="primary" density="compact" hide-details />
      </div>
      <template v-if="row.style.border_bottom">
        <div class="sl-field">
          <div class="sl-label">{{ t('sh_border_width', 'Line thickness') }}</div>
          <sh-size-field
            :model-value="row.style.border_bottom_width || 1"
            :min="1"
            :max="8"
            @update:model-value="(v) => (row.style.border_bottom_width = v)"
          />
        </div>
        <div class="sl-field">
          <div class="sl-label">{{ t('sh_border_color', 'Line colour') }}</div>
          <sh-color-field
            v-model="row.style.border_bottom_color"
            :placeholder="t('sh_theme_default', 'Default')"
            :error="fieldMsg('border_bottom_color')"
          />
        </div>
      </template>
      <div class="sl-switches">
        <v-switch
          :model-value="row.style.text_size === 'small'"
          :label="t('sh_small_text', 'Smaller text')"
          color="primary"
          density="compact"
          hide-details
          @update:model-value="(v) => (row.style.text_size = v ? 'small' : 'normal')"
        />
        <v-switch v-model="row.style.hide_mobile" :label="t('sh_hide_phone', 'Hide on phones')" color="primary" density="compact" hide-details />
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { SLOTS, useSiteHeaderStore } from '../composables/useSiteHeaderStore';
import ShSlotEditor from './SlotEditor.vue';
import ShColorField from './ColorField.vue';
import ShImageUpload from './ImageUpload.vue';
import ShSizeField from './SizeField.vue';
import ShBgFit from './BgFit.vue';

defineOptions({ name: 'ShRowPanel' });

const props = defineProps({
  rowId: { type: String, required: true },
});

const { form, fieldErrors, message, t } = useSiteHeaderStore();

const row = computed(() => form.value.rows[props.rowId]);
const isEmpty = computed(() => SLOTS.every((s) => !row.value[s].length));

const phoneLoss = computed(() => {
  const names = { menu: t('sh_item_menu', 'Menu'), login: t('sh_item_login', 'Login'), language: t('sh_item_language', 'Languages') };
  const out = [];
  for (const s of SLOTS) {
    for (const it of row.value[s]) {
      if (names[it.type]) out.push(names[it.type]);
      if (it.type === 'menu' && it.login) out.push(names.login);
    }
  }
  return [...new Set(out)];
});

function fieldMsg(key) {
  const code = fieldErrors.value?.[`rows.${props.rowId}.style.${key}`];
  return code ? message(code) : '';
}
</script>
