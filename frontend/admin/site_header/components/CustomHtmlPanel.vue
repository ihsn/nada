<template>
  <div class="sl-stack">
    <div class="d-flex flex-wrap ga-2">
      <v-btn size="small" variant="tonal" prepend-icon="mdi-content-copy" :loading="copying" @click="askCopy">
        {{ t('sh_copy_builder', 'Copy from the builder') }}
      </v-btn>
      <v-btn size="small" variant="text" prepend-icon="mdi-eraser" :disabled="!form.html" @click="form.html = ''">
        {{ t('sh_clear', 'Clear') }}
      </v-btn>
    </div>

    <v-alert v-if="!hasMenuToken" type="warning" variant="tonal" density="compact">{{ noMenuText }}</v-alert>
    <v-alert v-if="warnings.includes('HTML_CLEANED')" type="warning" variant="tonal" density="compact">
      {{ t('sh_html_cleaned', 'Some tags or attributes are not allowed and were removed from the saved header, for example scripts, forms, frames and event attributes. The preview shows what visitors will see.') }}
      <template #append>
        <v-btn size="small" variant="text" @click="cleanedDialog = true">{{ t('sh_show_cleaned', 'Show saved HTML') }}</v-btn>
      </template>
    </v-alert>

    <div class="sl-field">
      <expandable-code-editor
        ref="htmlEditor"
        v-model="form.html"
        language="html"
        min-height="300px"
        :label="t('sh_html', 'Header HTML')"
        :limit="server.limits.html"
        :expand-label="t('sh_expand', 'Expand editor')"
        :format-label="t('sh_format', 'Format')"
        :done-label="t('sh_done', 'Done')"
        :expanded-hint="t('sh_expanded_hint', 'Editing in the expanded window…')"
      />
      <div class="sl-help">
        {{ t('sh_html_rules', 'Bootstrap 4 classes work. Scripts, styles, forms, frames and on… attributes are removed. Put CSS under Extra CSS.') }}
      </div>
    </div>

    <div class="sl-section-label">{{ t('sh_tokens', 'Tokens') }}</div>
    <div class="sl-field">
      <div class="sl-help">
        {{ t('sh_tokens_help', 'Click a token to insert it at the cursor. Each one is replaced with the live component for each visitor and language.') }}
      </div>
      <div class="d-flex flex-wrap ga-1">
        <v-chip v-for="tok in server.tokens" :key="tok.token" size="small" label variant="tonal" color="primary" :title="tok.description" @click="insert(tok.token)">
          {{ tok.token }}
        </v-chip>
      </div>
    </div>
    <div class="sl-help">
      {{ t('sh_optional_tip', 'Add the class sh-optional to a wrapper to hide it when none of the tokens inside it show anything, for example a language bar on a one-language site.') }}
      {{ t('sh_copy_tip', 'HTML copied from the builder keeps its row sizes and colours (classes sh-row-top, sh-row-main, sh-row-bottom).') }}
    </div>

    <v-dialog v-model="confirmCopy" max-width="440">
      <v-card>
        <v-card-title class="text-subtitle-1">{{ t('sh_replace_title', 'Replace your HTML?') }}</v-card-title>
        <v-card-text>{{ t('sh_replace_text', 'This replaces the HTML in the editor with the builder’s current layout. Discard draft undoes it until you publish.') }}</v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="confirmCopy = false">{{ t('sh_cancel', 'Cancel') }}</v-btn>
          <v-btn color="primary" variant="flat" @click="copy">{{ t('sh_replace', 'Replace') }}</v-btn>
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
import { computed, ref } from 'vue';
import { useSiteHeaderStore } from '../composables/useSiteHeaderStore';
import ExpandableCodeEditor from '../../shared/ExpandableCodeEditor.vue';
import ShCodeEditor from './CodeEditor.vue';

defineOptions({ name: 'ShCustomHtmlPanel' });

const { server, form, warnings, hasMenuToken, builderTemplate, notify, t } = useSiteHeaderStore();

const htmlEditor = ref(null);
const cleanedDialog = ref(false);
const confirmCopy = ref(false);
const copying = ref(false);

const noMenuText = computed(() =>
  t('sh_no_menu_token', 'Your HTML has no %s token. Visitors will not see the site menu, and phones will have no menu button.', '{{menu}}')
);

function insert(token) {
  htmlEditor.value?.insert(token);
}

function askCopy() {
  if ((form.value.html || '').trim()) {
    confirmCopy.value = true;
  } else {
    copy();
  }
}

async function copy() {
  confirmCopy.value = false;
  copying.value = true;
  try {
    form.value.html = await builderTemplate();
  } catch {
    notify(t('sh_err_generic', 'Something went wrong.'), 'error');
  } finally {
    copying.value = false;
  }
}

function useCleaned() {
  form.value.html = server.draft?.html || '';
  cleanedDialog.value = false;
}
</script>
