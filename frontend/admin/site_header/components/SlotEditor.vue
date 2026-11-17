<template>
  <div class="sh-slot-ed">
    <div class="sh-slot-ed__label text-caption text-medium-emphasis">{{ label }}</div>
    <div class="sh-slot-ed__items">
      <v-menu v-for="(item, i) in items" :key="`${item.type}-${i}`" :close-on-content-click="false" location="bottom start">
        <template #activator="{ props: menuProps }">
          <v-chip v-bind="menuProps" size="small" label variant="tonal" :color="item.type === 'text' ? undefined : 'primary'" :prepend-icon="ICONS[item.type]" class="sh-slot-ed__chip">
            {{ itemLabel(item) }}
          </v-chip>
        </template>
        <v-card min-width="280" max-width="340" class="pa-4 sl-popup sl-stack">
          <div class="text-subtitle-2">{{ typeLabel(item.type) }}</div>

          <div v-if="item.type === 'menu'" class="sl-field">
            <div class="sl-switches">
              <v-checkbox
                v-model="item.login"
                :label="t('sh_menu_with_login', 'Login inside the menu')"
                :disabled="!!standalone('login')"
                :hint="standalone('login') ? t('sh_menu_login_elsewhere', 'Login is placed on its own in this header.') : ''"
                :persistent-hint="!!standalone('login')"
                density="compact"
                color="primary"
                :hide-details="!standalone('login')"
              />
              <v-checkbox
                v-model="item.language"
                :label="t('sh_menu_with_language', 'Languages inside the menu')"
                :disabled="!!standalone('language')"
                :hint="standalone('language') ? t('sh_menu_language_elsewhere', 'Languages are placed on their own in this header.') : ''"
                :persistent-hint="!!standalone('language')"
                density="compact"
                color="primary"
                :hide-details="!standalone('language')"
              />
            </div>
            <div class="sl-help">
              {{ t('sh_menu_inside_help', 'Items inside the menu fold into the menu button on phones.') }}
            </div>
          </div>

          <template v-else-if="item.type === 'search'">
            <div class="sl-field">
              <div class="sl-label">{{ t('sh_search_placeholder', 'Placeholder') }}</div>
              <v-text-field
                v-model="item.placeholder"
                :placeholder="t('sh_search_placeholder_default', 'Keywords')"
                density="compact"
                variant="outlined"
                hide-details
                maxlength="120"
              />
            </div>
            <div class="sl-field">
              <div class="sl-label">{{ t('sh_search_width', 'Width') }}</div>
              <v-btn-toggle v-model="item.width" mandatory density="compact" variant="outlined" divided color="primary">
                <v-btn value="compact" size="small">{{ t('sh_search_width_compact', 'Compact') }}</v-btn>
                <v-btn value="medium" size="small">{{ t('sh_search_width_medium', 'Medium') }}</v-btn>
                <v-btn value="wide" size="small">{{ t('sh_search_width_wide', 'Wide') }}</v-btn>
              </v-btn-toggle>
            </div>
            <div class="sl-field">
              <div class="sl-label">{{ t('sh_search_submit', 'Submit') }}</div>
              <v-btn-toggle v-model="item.submit" mandatory density="compact" variant="outlined" divided color="primary">
                <v-btn value="icon" size="small">{{ t('sh_search_submit_icon', 'Icon') }}</v-btn>
                <v-btn value="button" size="small">{{ t('sh_search_submit_button', 'Button') }}</v-btn>
              </v-btn-toggle>
              <div class="sl-help">
                {{ t('sh_search_hide_help', 'Hidden on the home page and catalog search. Always shown in this preview.') }}
              </div>
            </div>
          </template>

          <template v-else-if="item.type === 'text'">
            <div class="sl-field">
              <div class="sl-label">{{ t('sh_text', 'Text') }}</div>
              <v-text-field v-model="item.text" density="compact" variant="outlined" hide-details maxlength="120" autofocus />
            </div>
            <div class="sl-field">
              <div class="sl-label">{{ t('sh_link_optional', 'Link (optional)') }}</div>
              <v-text-field
                v-model="item.url"
                placeholder="https://… or page/contact"
                density="compact"
                variant="outlined"
                :error-messages="fieldMsg(`rows.${rowId}.${slotName}.${i}.url`)"
                :hide-details="!fieldMsg(`rows.${rowId}.${slotName}.${i}.url`)"
                maxlength="500"
              />
            </div>
          </template>

          <div class="sl-field">
            <div class="sl-label">{{ t('sh_move', 'Move') }}</div>
            <div class="d-flex flex-wrap ga-1">
              <v-btn size="x-small" variant="tonal" icon="mdi-chevron-left" :disabled="i === 0" :title="t('sh_move_before', 'Earlier')" @click="shift(i, -1)" />
              <v-btn size="x-small" variant="tonal" icon="mdi-chevron-right" :disabled="i === items.length - 1" :title="t('sh_move_after', 'Later')" @click="shift(i, 1)" />
              <v-btn v-for="s in otherSlots" :key="s" size="x-small" variant="tonal" @click="moveTo(i, s)">{{ slotLabel(s) }}</v-btn>
            </div>
          </div>
          <v-divider />
          <v-btn size="small" variant="text" color="error" prepend-icon="mdi-delete-outline" class="align-self-start" @click="remove(i)">{{ t('sh_remove', 'Remove') }}</v-btn>
        </v-card>
      </v-menu>

      <v-menu location="bottom start">
        <template #activator="{ props: menuProps }">
          <v-btn v-bind="menuProps" size="x-small" variant="text" prepend-icon="mdi-plus" class="sh-slot-ed__add">{{ t('sh_add', 'Add') }}</v-btn>
        </template>
        <v-list density="compact" min-width="240">
          <v-list-item v-for="type in TYPES" :key="type" :prepend-icon="ICONS[type]" @click="add(type)">
            <v-list-item-title>{{ typeLabel(type) }}</v-list-item-title>
            <v-list-item-subtitle v-if="placedElsewhere(type)">
              {{ t('sh_in_row', 'Now in %s — moves here', rowLabel(placedElsewhere(type).row)) }}
            </v-list-item-subtitle>
          </v-list-item>
        </v-list>
      </v-menu>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { SINGLE_ITEMS, SLOTS, useSiteHeaderStore } from '../composables/useSiteHeaderStore';

defineOptions({ name: 'ShSlotEditor' });

const props = defineProps({
  rowId: { type: String, required: true },
  slotName: { type: String, required: true },
});

const { form, fieldErrors, findItem, message, t } = useSiteHeaderStore();

const TYPES = ['brand', 'menu', 'login', 'language', 'search', 'text'];
const ICONS = {
  brand: 'mdi-card-account-details-outline',
  menu: 'mdi-menu',
  login: 'mdi-account-circle-outline',
  language: 'mdi-translate',
  search: 'mdi-magnify',
  text: 'mdi-format-text',
};

const items = computed(() => form.value.rows[props.rowId][props.slotName]);
const otherSlots = computed(() => SLOTS.filter((s) => s !== props.slotName));
const label = computed(() => slotLabel(props.slotName));

function slotLabel(s) {
  return { left: t('sh_left', 'Left'), center: t('sh_center', 'Center'), right: t('sh_right', 'Right') }[s];
}
function rowLabel(r) {
  return { top: t('sh_row_top', 'Top bar'), main: t('sh_row_main', 'Main row'), bottom: t('sh_row_bottom', 'Bottom bar') }[r];
}
function typeLabel(type) {
  return {
    brand: t('sh_item_brand', 'Brand (logo / title)'),
    menu: t('sh_item_menu', 'Menu'),
    login: t('sh_item_login', 'Login'),
    language: t('sh_item_language', 'Languages'),
    search: t('sh_item_search', 'Search'),
    text: t('sh_item_text', 'Text or link'),
  }[type];
}
function itemLabel(item) {
  if (item.type === 'text') return item.text || t('sh_item_text', 'Text or link');
  if (item.type === 'menu') {
    const extra = [item.login && t('sh_item_login', 'Login'), item.language && t('sh_item_language', 'Languages')].filter(Boolean);
    return extra.length ? `${typeLabel('menu')} + ${extra.join(' + ')}` : typeLabel('menu');
  }
  return typeLabel(item.type);
}
function fieldMsg(key) {
  const code = fieldErrors.value?.[key];
  return code ? message(code) : '';
}

/** Login or language placed as its own item (not inside the menu) */
function standalone(type) {
  return findItem(type);
}

function placedElsewhere(type) {
  if (!SINGLE_ITEMS.includes(type)) return null;
  const at = findItem(type);
  return at && !(at.row === props.rowId && at.slot === props.slotName) ? at : null;
}

function add(type) {
  if (SINGLE_ITEMS.includes(type)) {
    const at = findItem(type);
    if (at) {
      const [item] = form.value.rows[at.row][at.slot].splice(at.index, 1);
      items.value.push(item);
      afterPlace(type);
      return;
    }
  }
  const item = { type };
  if (type === 'menu') Object.assign(item, { login: !findItem('login'), language: false });
  if (type === 'search') Object.assign(item, { placeholder: '', width: 'medium', submit: 'icon' });
  if (type === 'text') Object.assign(item, { text: t('sh_new_text', 'New link'), url: '' });
  items.value.push(item);
  afterPlace(type);
}

/** A login or language item on its own replaces the same item inside the menu */
function afterPlace(type) {
  if (type !== 'login' && type !== 'language') return;
  const menu = findItem('menu');
  if (menu) form.value.rows[menu.row][menu.slot][menu.index][type] = false;
}

function remove(i) {
  items.value.splice(i, 1);
}

function shift(i, d) {
  const list = items.value;
  const [item] = list.splice(i, 1);
  list.splice(i + d, 0, item);
}

function moveTo(i, slot) {
  const [item] = items.value.splice(i, 1);
  form.value.rows[props.rowId][slot].push(item);
}
</script>

<style scoped>
.sh-slot-ed {
  display: grid;
  grid-template-columns: 56px 1fr;
  align-items: center;
  gap: 8px;
  padding: 4px 0;
  border-bottom: 1px dashed rgba(var(--v-border-color), 0.15);
}
.sh-slot-ed:last-child {
  border-bottom: 0;
}
.sh-slot-ed__items {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 4px;
  min-height: 28px;
}
.sh-slot-ed__chip {
  max-width: 220px;
}
</style>
