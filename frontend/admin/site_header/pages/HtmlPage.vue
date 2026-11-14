<template>
  <div v-if="form" class="sh-html">
    <v-card border flat class="mb-4">
      <v-card-text class="d-flex align-center flex-wrap ga-3">
        <v-switch
          :model-value="form.mode === 'custom'"
          :label="t('sh_use_custom', 'Use my HTML for the header')"
          color="primary"
          density="compact"
          hide-details
          inset
          @update:model-value="(on) => (form.mode = on ? 'custom' : 'preset')"
        />
        <v-spacer />
        <v-menu>
          <template #activator="{ props: menuProps }">
            <v-btn v-bind="menuProps" size="small" variant="tonal" prepend-icon="mdi-content-copy" append-icon="mdi-menu-down">
              {{ t('sh_start_from', 'Start from a layout') }}
            </v-btn>
          </template>
          <v-list density="compact">
            <v-list-item v-for="p in server.presets" :key="p.id" :title="p.title" @click="askLoad(p)" />
          </v-list>
        </v-menu>
        <v-btn size="small" variant="text" prepend-icon="mdi-eraser" :disabled="!form.html && !form.css" @click="askLoad(null)">
          {{ t('sh_clear', 'Clear') }}
        </v-btn>
      </v-card-text>
    </v-card>

    <v-alert v-if="form.mode !== 'custom'" type="info" variant="tonal" density="compact" class="mb-4">
      {{ t('sh_custom_off', 'The site uses a layout from the Design tab. Turn on “Use my HTML for the header” to use the HTML below instead. Extra CSS applies in both cases.') }}
    </v-alert>
    <v-alert v-if="form.mode === 'custom' && !hasMenuToken" type="warning" variant="tonal" density="compact" class="mb-4">
      {{ noMenuText }}
    </v-alert>
    <v-alert v-if="warnings.includes('HTML_CLEANED')" type="warning" variant="tonal" density="compact" class="mb-4">
      {{ t('sh_html_cleaned', 'Some tags or attributes are not allowed and were removed from the saved header, for example scripts, forms, frames and event attributes. The preview shows what visitors will see.') }}
      <template #append>
        <v-btn size="small" variant="text" @click="cleanedDialog = true">{{ t('sh_show_cleaned', 'Show saved HTML') }}</v-btn>
      </template>
    </v-alert>
    <v-alert v-if="warnings.includes('CSS_CLEANED')" type="warning" variant="tonal" density="compact" class="mb-4">
      {{ t('sh_css_cleaned', 'Some CSS was removed: @import, expressions, backslash escapes and url() addresses on other sites are not allowed.') }}
    </v-alert>

    <v-row>
      <v-col cols="12" lg="8">
        <div class="d-flex align-baseline mb-1">
          <div class="text-caption text-medium-emphasis">{{ t('sh_html', 'Header HTML') }}</div>
          <v-spacer />
          <div class="text-caption" :class="htmlOver ? 'text-error' : 'text-medium-emphasis'">
            {{ (form.html || '').length }} / {{ server.limits.html }}
          </div>
        </div>
        <sh-code-editor ref="htmlEditor" v-model="form.html" language="html" min-height="320px" />
        <div class="text-caption text-medium-emphasis mt-1">
          {{ t('sh_html_rules', 'Bootstrap 4 classes work. Scripts, styles, forms, frames and on… attributes are removed. Put CSS in the box below.') }}
        </div>

        <div class="d-flex align-baseline mt-5 mb-1">
          <div class="text-caption text-medium-emphasis">{{ t('sh_css', 'Extra CSS') }}</div>
          <v-spacer />
          <div class="text-caption" :class="cssOver ? 'text-error' : 'text-medium-emphasis'">
            {{ (form.css || '').length }} / {{ server.limits.css }}
          </div>
        </div>
        <sh-code-editor v-model="form.css" language="css" min-height="160px" />
        <div class="text-caption text-medium-emphasis mt-1">
          {{ t('sh_css_rules', 'Start selectors with .site-header so the rest of the page is not affected. Images must come from this site.') }}
        </div>
      </v-col>

      <v-col cols="12" lg="4">
        <v-card border flat class="sh-tokens">
          <v-card-title class="text-subtitle-1">{{ t('sh_tokens', 'Tokens') }}</v-card-title>
          <v-card-subtitle class="pb-2 sh-wrap">{{ t('sh_tokens_help', 'Click a token to insert it at the cursor. Each one is replaced with the live component for each visitor and language.') }}</v-card-subtitle>
          <v-list density="compact" class="py-0">
            <v-list-item v-for="tok in server.tokens" :key="tok.token" class="sh-token" @click="insertToken(tok.token)">
              <code class="sh-token__code">{{ tok.token }}</code>
              <div class="text-caption text-medium-emphasis">{{ tok.description }}</div>
            </v-list-item>
          </v-list>
          <v-divider />
          <v-card-text class="text-caption text-medium-emphasis">
            <div class="mb-1">{{ t('sh_tokens_example', 'Example of a one-line header:') }}</div>
            <pre class="sh-example">{{ example }}</pre>
            <div class="mt-2">{{ t('sh_optional_tip', 'Add the class sh-optional to a wrapper to hide it when none of the tokens inside it show anything, for example a language bar on a one-language site.') }}</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-dialog v-model="confirm.show" max-width="460">
      <v-card>
        <v-card-title class="text-subtitle-1">{{ t('sh_replace_title', 'Replace your HTML?') }}</v-card-title>
        <v-card-text>{{ t('sh_replace_text', 'This replaces the HTML and CSS in the editors. You can undo with Discard draft until you publish.') }}</v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="confirm.show = false">{{ t('sh_cancel', 'Cancel') }}</v-btn>
          <v-btn color="primary" variant="flat" @click="doLoad(confirm.preset)">{{ t('sh_replace', 'Replace') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="cleanedDialog" max-width="820">
      <v-card>
        <v-card-title class="text-subtitle-1">{{ t('sh_saved_html', 'Saved HTML') }}</v-card-title>
        <v-card-text>
          <sh-code-editor :model-value="server.draft?.html || ''" language="html" min-height="240px" readonly />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="cleanedDialog = false">{{ t('sh_close', 'Close') }}</v-btn>
          <v-btn color="primary" variant="flat" @click="useCleaned">{{ t('sh_use_saved', 'Put it in the editor') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { useSiteHeaderStore } from '../composables/useSiteHeaderStore';
import ShCodeEditor from '../components/CodeEditor.vue';

defineOptions({ name: 'SiteHeaderHtmlPage' });

const { server, form, warnings, hasMenuToken, t } = useSiteHeaderStore();

const htmlEditor = ref(null);
const cleanedDialog = ref(false);
const confirm = reactive({ show: false, preset: null });

const htmlOver = computed(() => (form.value?.html || '').length > (server.limits.html || Infinity));
const cssOver = computed(() => (form.value?.css || '').length > (server.limits.css || Infinity));

const noMenuText = computed(() =>
  t('sh_no_menu_token', 'Your HTML has no %s token. Visitors will not see the site menu, and phones will have no menu button.', '{{menu}}')
);

const example = `<div class="container">
  <nav class="navbar navbar-expand-md navbar-light">
    {{logo+text}}
    {{menu+login+language}}
  </nav>
</div>`;

function insertToken(token) {
  htmlEditor.value?.insert(token);
}

function askLoad(preset) {
  if (!(form.value.html || '').trim() && !(form.value.css || '').trim()) {
    doLoad(preset);
    return;
  }
  confirm.preset = preset;
  confirm.show = true;
}

function doLoad(preset) {
  confirm.show = false;
  form.value.html = preset ? preset.html : '';
  form.value.css = preset ? preset.css : '';
  if (preset) {
    form.value.mode = 'custom';
  }
}

function useCleaned() {
  form.value.html = server.draft?.html || '';
  cleanedDialog.value = false;
}
</script>

<style scoped>
.sh-wrap {
  white-space: normal;
}
.sh-tokens {
  position: sticky;
  top: 72px;
}
.sh-token {
  cursor: pointer;
}
.sh-token__code {
  font-size: 0.8125rem;
  color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.06);
  padding: 1px 4px;
  border-radius: 3px;
}
.sh-example {
  white-space: pre-wrap;
  font-size: 0.75rem;
  background: rgba(var(--v-theme-on-surface), 0.04);
  padding: 8px;
  border-radius: 4px;
}
</style>
