<template>
  <div class="sl-stack">
    <div class="sl-field">
      <div class="sl-switches">
        <v-switch
          v-model="form.show_login"
          :label="t('sh_show_login', 'Show login (signed-in visitors see their user menu)')"
          color="primary"
          density="compact"
          hide-details
        />
        <v-switch v-model="form.show_language" :label="t('sh_show_language', 'Show the language switcher')" color="primary" density="compact" hide-details />
      </div>
      <div class="sl-help">
        <template v-if="server.languagesEnabled > 1">
          {{ t('sh_languages_enabled', '%d languages are enabled.', server.languagesEnabled) }}
        </template>
        <template v-else>
          {{ t('sh_languages_one', 'Only one language is enabled, so the switcher is hidden. Enable more languages in Settings.') }}
        </template>
      </div>
    </div>
    <div v-if="editorView === 'builder'" class="sl-help">
      {{ placement }}
    </div>
    <div class="sl-help">
      {{ t('sh_menu_items_help', 'Menu items come from the site menu.') }}
      <a :href="menuUrl">{{ t('sh_edit_menu', 'Edit the menu') }}</a>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useSiteHeaderStore } from '../composables/useSiteHeaderStore';

defineOptions({ name: 'ShLoginLanguagePanel' });

const { server, form, editorView, findItem, t } = useSiteHeaderStore();
const { siteUrl } = useAppConfig();
const menuUrl = computed(() => `${String(siteUrl.value).replace(/\/$/, '')}/admin/menu`);

const rowNames = computed(() => ({ top: t('sh_row_top', 'Top bar'), main: t('sh_row_main', 'Main row'), bottom: t('sh_row_bottom', 'Bottom bar') }));

const placement = computed(() => {
  const where = (type) => {
    const at = findItem(type);
    if (at) return rowNames.value[at.row];
    const menu = findItem('menu');
    if (menu && form.value.rows[menu.row][menu.slot][menu.index][type]) {
      return t('sh_inside_menu', '%s, inside the menu', rowNames.value[menu.row]);
    }
    return t('sh_not_placed', 'not placed');
  };
  return t('sh_placement', 'Login: %s. Languages: %s. Place them in the row sections.', where('login'), where('language'));
});
</script>
