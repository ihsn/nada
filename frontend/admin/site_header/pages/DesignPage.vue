<template>
  <div v-if="form" class="sh-design">
    <v-alert v-if="form.mode === 'custom'" type="info" variant="tonal" density="compact" class="mb-4">
      {{ t('sh_custom_in_use', 'Your own HTML is used for the header, so the layout below is not used. Brand, menu, colour and background settings still apply to the tokens in your HTML.') }}
      <template #append>
        <v-btn size="small" variant="text" @click="form.mode = 'preset'">{{ t('sh_use_layout', 'Use a layout instead') }}</v-btn>
      </template>
    </v-alert>

    <!-- Layout -->
    <v-card border flat class="mb-4">
      <v-card-title class="text-subtitle-1">{{ t('sh_layout', 'Layout') }}</v-card-title>
      <v-card-subtitle class="pb-2">
        {{ t('sh_layout_help', 'Pick a starting layout. You can change brand, menu and colours below, or write your own HTML.') }}
      </v-card-subtitle>
      <v-card-text>
        <div class="sh-presets">
          <button
            v-for="p in server.presets"
            :key="p.id"
            type="button"
            class="sh-preset"
            :class="{ 'sh-preset--active': form.mode === 'preset' && form.preset === p.id }"
            :aria-pressed="form.mode === 'preset' && form.preset === p.id"
            @click="choosePreset(p.id)"
          >
            <!-- thumbnails are built-in SVG from the server's preset files -->
            <span class="sh-preset__thumb" v-html="p.thumbnail" />
            <span class="sh-preset__title">
              {{ p.title }}
              <v-icon v-if="form.mode === 'preset' && form.preset === p.id" icon="mdi-check-circle" size="16" color="primary" />
            </span>
            <span class="sh-preset__desc">{{ p.description }}</span>
          </button>
          <router-link
            :to="{ name: 'html' }"
            class="sh-preset sh-preset--custom"
            :class="{ 'sh-preset--active': form.mode === 'custom' }"
          >
            <span class="sh-preset__thumb sh-preset__thumb--icon"><v-icon icon="mdi-code-tags" size="32" /></span>
            <span class="sh-preset__title">
              {{ t('sh_custom_html', 'Custom HTML') }}
              <v-icon v-if="form.mode === 'custom'" icon="mdi-check-circle" size="16" color="primary" />
            </span>
            <span class="sh-preset__desc">{{ t('sh_custom_html_desc', 'Write your own HTML and place the logo, menu, login and language with tokens.') }}</span>
          </router-link>
        </div>
      </v-card-text>
    </v-card>

    <v-row>
      <!-- Brand -->
      <v-col cols="12" lg="6">
        <v-card border flat class="h-100">
          <v-card-title class="text-subtitle-1">{{ t('sh_brand', 'Brand') }}</v-card-title>
          <v-card-text>
            <div class="text-caption text-medium-emphasis mb-1">{{ t('sh_brand_show', 'Show') }}</div>
            <v-btn-toggle v-model="form.brand" mandatory density="compact" variant="outlined" divided color="primary">
              <v-btn value="text">{{ t('sh_brand_text', 'Title') }}</v-btn>
              <v-btn value="logo">{{ t('sh_brand_logo', 'Logo') }}</v-btn>
              <v-btn value="logo_text">{{ t('sh_brand_logo_text', 'Logo and title') }}</v-btn>
            </v-btn-toggle>
            <div class="text-caption text-medium-emphasis mt-2">
              {{ t('sh_title_from_settings', 'The site title comes from Settings > General.') }}
              <a :href="settingsUrl">{{ t('sh_edit_title', 'Edit the title') }}</a>
            </div>

            <template v-if="form.brand !== 'text'">
              <div class="text-caption text-medium-emphasis mt-4 mb-1">{{ t('sh_logo', 'Logo') }}</div>
              <sh-image-upload
                kind="logo"
                :url="form.logo_url"
                :fallback-url="server.themeLogoUrl"
                :alt="form.logo_alt"
                accept="image/png,image/jpeg,image/gif,image/webp,image/svg+xml"
                :empty-text="t('sh_logo_theme', 'Using the theme logo')"
                :hint="t('sh_logo_hint', 'PNG, JPG, GIF, WebP or SVG, up to 2 MB.')"
                @uploaded="(r) => { form.logo = r.path; form.logo_url = r.url; }"
                @clear="form.logo = ''; form.logo_url = ''"
              />
              <div class="text-caption text-medium-emphasis mt-4 mb-1">
                {{ t('sh_logo_height', 'Logo height') }}: {{ form.logo_height }}px
              </div>
              <v-slider v-model="form.logo_height" :min="16" :max="200" :step="1" density="compact" hide-details color="primary" />
              <div class="text-caption text-medium-emphasis mt-3 mb-1">{{ t('sh_logo_alt', 'Logo text for screen readers') }}</div>
              <v-text-field
                v-model="form.logo_alt"
                :placeholder="t('sh_logo_alt_placeholder', 'Defaults to the site title')"
                density="compact"
                variant="outlined"
                hide-details
                maxlength="200"
              />
              <v-switch
                v-if="form.brand === 'logo_text'"
                v-model="form.show_separator"
                :label="t('sh_show_separator', 'Line between logo and title')"
                color="primary"
                density="compact"
                hide-details
                class="mt-2"
              />
            </template>

            <template v-if="form.brand !== 'logo'">
              <v-switch
                v-model="form.show_subtitle"
                :label="t('sh_show_subtitle', 'Show a subtitle under the title')"
                color="primary"
                density="compact"
                hide-details
                class="mt-2"
              />
              <template v-if="form.show_subtitle">
                <div class="text-caption text-medium-emphasis mb-1">{{ t('sh_subtitle', 'Subtitle') }}</div>
                <v-text-field
                  v-model="form.subtitle"
                  :placeholder="t('sh_subtitle_placeholder', 'Data Catalog')"
                  :hint="t('sh_subtitle_hint', 'Leave empty to use “Data Catalog”, translated into each site language.')"
                  persistent-hint
                  density="compact"
                  variant="outlined"
                  maxlength="200"
                />
              </template>
            </template>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Menu and login -->
      <v-col cols="12" lg="6">
        <v-card border flat class="mb-4">
          <v-card-title class="text-subtitle-1">{{ t('sh_menu_login', 'Menu, login and language') }}</v-card-title>
          <v-card-text>
            <div class="text-body-2 mb-2">
              {{ t('sh_menu_items_help', 'Menu items come from the site menu.') }}
              <a :href="menuUrl">{{ t('sh_edit_menu', 'Edit the menu') }}</a>
            </div>
            <v-switch
              v-model="form.show_login"
              :label="t('sh_show_login', 'Show login (signed-in visitors see their user menu)')"
              color="primary"
              density="compact"
              hide-details
            />
            <v-switch
              v-model="form.show_language"
              :label="t('sh_show_language', 'Show the language switcher')"
              color="primary"
              density="compact"
              hide-details
            />
            <div class="text-caption text-medium-emphasis ml-1">
              <template v-if="server.languagesEnabled > 1">
                {{ t('sh_languages_enabled', '%d languages are enabled.', server.languagesEnabled) }}
              </template>
              <template v-else>
                {{ t('sh_languages_one', 'Only one language is enabled, so the switcher is hidden. Enable more languages in Settings.') }}
              </template>
            </div>
          </v-card-text>
        </v-card>

        <!-- Colours and background -->
        <v-card border flat>
          <v-card-title class="text-subtitle-1">{{ t('sh_colors', 'Colours and background') }}</v-card-title>
          <v-card-text>
            <v-row dense>
              <v-col cols="12" sm="6">
                <div class="text-caption text-medium-emphasis mb-1">{{ t('sh_bg_color', 'Background colour') }}</div>
                <sh-color-field v-model="form.bg_color" :placeholder="t('sh_theme_default', 'Theme default')" :error="fieldMsg('bg_color')" />
              </v-col>
              <v-col cols="12" sm="6">
                <div class="text-caption text-medium-emphasis mb-1">{{ t('sh_text_color', 'Text and link colour') }}</div>
                <sh-color-field v-model="form.text_color" :placeholder="t('sh_theme_default', 'Theme default')" :error="fieldMsg('text_color')" />
              </v-col>
            </v-row>
            <div class="text-caption text-medium-emphasis mt-4 mb-1">{{ t('sh_bg_image', 'Background image') }}</div>
            <sh-image-upload
              kind="background"
              :url="form.bg_image_url"
              :empty-text="t('sh_no_bg_image', 'No background image')"
              :hint="t('sh_bg_hint', 'PNG, JPG, GIF or WebP, up to 5 MB. Wide images (2000px or more) look best.')"
              @uploaded="(r) => { form.bg_image = r.path; form.bg_image_url = r.url; }"
              @clear="form.bg_image = ''; form.bg_image_url = ''"
            />
            <v-row v-if="form.bg_image" dense class="mt-2">
              <v-col cols="6">
                <div class="text-caption text-medium-emphasis mb-1">{{ t('sh_bg_size', 'Fit') }}</div>
                <v-select v-model="form.bg_size" :items="sizes" density="compact" variant="outlined" hide-details />
              </v-col>
              <v-col cols="6">
                <div class="text-caption text-medium-emphasis mb-1">{{ t('sh_bg_position', 'Position') }}</div>
                <v-select v-model="form.bg_position" :items="positions" density="compact" variant="outlined" hide-details />
              </v-col>
            </v-row>
            <div v-if="form.bg_image && form.mode === 'preset' && form.preset !== 'hero'" class="text-caption text-medium-emphasis mt-2">
              {{ t('sh_bg_banner_tip', 'Tip: the Banner layout shows a background image best.') }}
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useSiteHeaderStore } from '../composables/useSiteHeaderStore';
import ShImageUpload from '../components/ImageUpload.vue';
import ShColorField from '../components/ColorField.vue';

defineOptions({ name: 'SiteHeaderDesignPage' });

const { server, form, fieldErrors, message, t } = useSiteHeaderStore();
const { siteUrl } = useAppConfig();

const settingsUrl = computed(() => `${String(siteUrl.value).replace(/\/$/, '')}/admin/configurations#/general`);
const menuUrl = computed(() => `${String(siteUrl.value).replace(/\/$/, '')}/admin/menu`);

const sizes = computed(() => [
  { title: t('sh_bg_cover', 'Fill the header'), value: 'cover' },
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

function choosePreset(id) {
  form.value.mode = 'preset';
  form.value.preset = id;
}

function fieldMsg(key) {
  const code = fieldErrors.value?.[key];
  return code ? message(code) : '';
}
</script>

<style scoped>
.sh-presets {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 12px;
}
.sh-preset {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 10px;
  text-align: left;
  border: 1px solid rgba(var(--v-border-color), 0.25);
  border-radius: 8px;
  background: rgb(var(--v-theme-surface));
  color: inherit;
  text-decoration: none;
  cursor: pointer;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.sh-preset:hover {
  border-color: rgba(var(--v-theme-primary), 0.6);
}
.sh-preset:focus-visible {
  outline: 2px solid rgb(var(--v-theme-primary));
  outline-offset: 2px;
}
.sh-preset--active {
  border-color: rgb(var(--v-theme-primary));
  box-shadow: 0 0 0 1px rgb(var(--v-theme-primary));
}
.sh-preset__thumb {
  display: block;
  height: 72px;
  border-radius: 4px;
  background: rgba(var(--v-theme-on-surface), 0.03);
  color: rgb(var(--v-theme-primary));
  overflow: hidden;
}
.sh-preset__thumb :deep(svg) {
  width: 100%;
  height: 100%;
}
.sh-preset__thumb--icon {
  display: flex;
  align-items: center;
  justify-content: center;
}
.sh-preset__title {
  display: flex;
  align-items: center;
  gap: 4px;
  font-weight: 500;
  font-size: 0.875rem;
}
.sh-preset__desc {
  font-size: 0.75rem;
  color: rgba(var(--v-theme-on-surface), 0.6);
  line-height: 1.35;
}
</style>
