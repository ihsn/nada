<template>
  <div class="sl-stack">
    <p class="text-body-2 mb-0">
      {{
        t(
          'sf_theme_help',
          'The public site uses the PHP footer in the active theme. Edit that file on the server (or use footer.local.php) to change the markup.'
        )
      }}
    </p>

    <v-alert v-if="server.hasLocalFooter" type="warning" variant="tonal" density="compact">
      {{
        t(
          'sf_local_footer',
          'This site has themes/%s/footer.local.php. That file is always used for the public footer until you rename or remove it. Custom HTML stays disabled for visitors.'
        ).replace('%s', server.themeName || 'nada52')
      }}
    </v-alert>

    <div class="sl-field">
      <div class="sl-label">{{ t('sf_theme_file', 'Footer file') }}</div>
      <code class="sf-theme-path">{{ server.themeFooterFile || 'themes/nada52/footer_theme.php' }}</code>
    </div>

    <ul class="text-body-2 mb-0 pl-4">
      <li>
        {{
          t(
            'sf_theme_tip_local',
            'For upgrades: copy footer_theme.php to footer.local.php, then edit the local file so stock theme updates do not overwrite your changes.'
          )
        }}
      </li>
      <li class="mt-2">
        {{
          t(
            'sf_theme_tip_builder',
            'To use custom HTML instead, choose that option above and publish. Remove footer.local.php first if it exists.'
          )
        }}
      </li>
    </ul>
  </div>
</template>

<script setup>
import { useSiteFooterStore } from '../composables/useSiteFooterStore';

defineOptions({ name: 'SfThemeFilePanel' });

const { server, t } = useSiteFooterStore();
</script>

<style scoped>
.sf-theme-path {
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
