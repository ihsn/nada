<template>
  <div class="sl-stack">
    <p class="text-body-2 mb-0">
      {{
        t(
          'sh_theme_help',
          'The public site uses the PHP header in the active theme. Edit that file on the server (or use header.local.php) to change the markup.'
        )
      }}
    </p>

    <v-alert v-if="server.hasLocalHeader" type="warning" variant="tonal" density="compact">
      {{
        t(
          'sh_local_header',
          'This site has themes/%s/header.local.php. That file is always used for the public header until you rename or remove it. The builder and custom HTML options stay disabled for visitors.'
        ).replace('%s', server.themeName || 'nada52')
      }}
    </v-alert>

    <div class="sl-field">
      <div class="sl-label">{{ t('sh_theme_file', 'Header file') }}</div>
      <code class="sh-theme-path">{{ server.themeHeaderFile || 'themes/nada52/header_theme.php' }}</code>
    </div>

    <ul class="text-body-2 mb-0 pl-4">
      <li>
        {{
          t(
            'sh_theme_tip_local',
            'For upgrades: copy header_theme.php to header.local.php, then edit the local file so stock theme updates do not overwrite your changes.'
          )
        }}
      </li>
      <li class="mt-2">
        {{
          t(
            'sh_theme_tip_builder',
            'To use the layout builder or custom HTML instead, choose that option above and publish. Remove header.local.php first if it exists.'
          )
        }}
      </li>
    </ul>
  </div>
</template>

<script setup>
import { useSiteHeaderStore } from '../composables/useSiteHeaderStore';

defineOptions({ name: 'ShThemeFilePanel' });

const { server, t } = useSiteHeaderStore();
</script>

<style scoped>
.sh-theme-path {
  display: inline-block;
  align-self: flex-start;
  max-width: 100%;
  word-break: break-all;
  padding: 4px 8px;
  border-radius: 4px;
  background: rgba(var(--v-theme-on-surface), 0.06);
  font-size: 0.75rem;
}
</style>
