<template>
  <div class="sl-stack">
    <div class="sl-field">
      <div class="sl-label">{{ t('sh_brand_show', 'Show') }}</div>
      <v-btn-toggle v-model="form.brand" mandatory density="compact" variant="outlined" divided color="primary">
        <v-btn value="text" size="small">{{ t('sh_brand_text', 'Title') }}</v-btn>
        <v-btn value="logo" size="small">{{ t('sh_brand_logo', 'Logo') }}</v-btn>
        <v-btn value="logo_text" size="small">{{ t('sh_brand_logo_text', 'Logo and title') }}</v-btn>
      </v-btn-toggle>
      <div class="sl-help">
        {{ t('sh_title_from_settings', 'The site title comes from Settings > General.') }}
        <a :href="settingsUrl">{{ t('sh_edit_title', 'Edit the title') }}</a>
      </div>
    </div>

    <template v-if="form.brand !== 'text'">
      <div class="sl-field">
        <div class="sl-label">{{ t('sh_logo', 'Logo') }}</div>
        <sh-image-upload
          kind="logo"
          :url="form.logo_url || ''"
          :alt="form.logo_alt"
          accept="image/png,image/jpeg,image/gif,image/webp,image/svg+xml"
          :empty-text="t('sh_no_logo', 'Upload a logo')"
          :hint="t('sh_logo_hint', 'PNG, JPG, GIF, WebP or SVG · up to 2 MB. Uses the theme logo until you upload one.')"
          @uploaded="(r) => { form.logo = r.path; form.logo_url = r.url; }"
          @clear="form.logo = ''; form.logo_url = ''"
        />
      </div>
      <div class="sl-field">
        <div class="sl-label">{{ t('sh_logo_height', 'Logo height') }}</div>
        <div class="sh-size-stack">
          <sh-size-field v-model="form.logo_height" :min="16" :max="200" icon="mdi-monitor" :icon-title="t('sh_desktop', 'Desktop and tablet')" />
          <sh-size-field v-model="form.logo_height_mobile" :min="16" :max="200" icon="mdi-cellphone" :icon-title="t('sh_phone', 'Phone')" />
        </div>
      </div>
      <div class="sl-field">
        <div class="sl-label">{{ t('sh_logo_max_width', 'Maximum logo width') }}</div>
        <sh-size-field v-model="form.logo_max_width" :min="40" :max="800" icon="mdi-arrow-left-right" />
        <div class="sl-help">
          {{ t('sh_logo_size_help', 'The logo keeps its shape: it is shown at this height, and scaled down if it would be wider than the maximum.') }}
        </div>
      </div>
      <div class="sl-field">
        <div class="sl-label">{{ t('sh_logo_alt', 'Logo text for screen readers') }}</div>
        <v-text-field
          v-model="form.logo_alt"
          :placeholder="t('sh_logo_alt_placeholder', 'Defaults to the site title')"
          density="compact"
          variant="outlined"
          hide-details
          maxlength="200"
        />
      </div>
    </template>

    <div v-if="form.brand !== 'logo'" class="sl-switches">
      <v-switch
        v-if="form.brand === 'logo_text'"
        v-model="form.show_separator"
        :label="t('sh_show_separator', 'Line between logo and title')"
        color="primary"
        density="compact"
        hide-details
      />
      <v-switch
        v-model="form.show_subtitle"
        :label="t('sh_show_subtitle', 'Show a subtitle under the title')"
        color="primary"
        density="compact"
        hide-details
      />
    </div>
    <v-text-field
      v-if="form.brand !== 'logo' && form.show_subtitle"
      v-model="form.subtitle"
      :placeholder="t('sh_subtitle_placeholder', 'Data Catalog')"
      :hint="t('sh_subtitle_hint', 'Leave empty to use “Data Catalog”, translated into each site language.')"
      persistent-hint
      density="compact"
      variant="outlined"
      maxlength="200"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useSiteHeaderStore } from '../composables/useSiteHeaderStore';
import ShImageUpload from './ImageUpload.vue';
import ShSizeField from './SizeField.vue';

defineOptions({ name: 'ShBrandPanel' });

const { form, t } = useSiteHeaderStore();
const { siteUrl } = useAppConfig();
const settingsUrl = computed(() => `${String(siteUrl.value).replace(/\/$/, '')}/admin/configurations#/general`);
</script>
