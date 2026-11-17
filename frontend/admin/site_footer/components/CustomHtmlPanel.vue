<template>
  <div class="sl-stack">
    <v-alert v-if="warnings.includes('HTML_CLEANED')" type="warning" variant="tonal" density="compact">
      {{
        t(
          'sf_html_cleaned',
          'Some tags or attributes are not allowed and were removed from the saved footer, for example scripts, forms, frames and event attributes. The preview shows what visitors will see.'
        )
      }}
      <template #append>
        <v-btn size="small" variant="text" @click="cleanedDialog = true">{{ t('sf_show_cleaned', 'Show saved HTML') }}</v-btn>
      </template>
    </v-alert>

    <div class="sl-field">
      <expandable-code-editor
        ref="htmlEditor"
        v-model="form.html"
        language="html"
        min-height="280px"
        :label="t('sf_html', 'Footer HTML')"
        :limit="server.limits.html"
        :expand-label="t('sf_expand', 'Expand editor')"
        :format-label="t('sf_format', 'Format')"
        :done-label="t('sf_done', 'Done')"
        :expanded-hint="t('sf_expanded_hint', 'Editing in the expanded window…')"
      />
      <div class="sl-help">
        {{
          t(
            'sf_html_rules',
            'Bootstrap 4 classes work. Scripts, styles, forms, frames and on… attributes are removed. Put CSS under Extra CSS.'
          )
        }}
      </div>
    </div>

    <div class="sl-section-label">{{ t('sf_tokens', 'Tokens') }}</div>
    <div class="sl-field">
      <div class="sl-help">
        {{ t('sf_tokens_help', 'Click a token to insert it at the cursor. Each one is replaced with the live value for each visitor.') }}
      </div>
      <div class="d-flex flex-wrap ga-1">
        <v-chip
          v-for="tok in server.tokens"
          :key="tok.token"
          size="small"
          label
          variant="tonal"
          color="primary"
          :title="tok.description"
          @click="insert(tok.token)"
        >
          {{ tok.token }}
        </v-chip>
      </div>
    </div>

    <v-dialog v-model="cleanedDialog" max-width="820">
      <v-card>
        <v-card-title class="text-subtitle-1">{{ t('sf_html', 'Footer HTML') }}</v-card-title>
        <v-card-text>
          <sf-code-editor :model-value="server.draft?.html || ''" language="html" min-height="240px" readonly />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="cleanedDialog = false">{{ t('sf_close', 'Close') }}</v-btn>
          <v-btn color="primary" variant="flat" @click="useCleaned">{{ t('sf_use_saved', 'Put it in the editor') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useSiteFooterStore } from '../composables/useSiteFooterStore';
import ExpandableCodeEditor from '../../shared/ExpandableCodeEditor.vue';
import SfCodeEditor from './CodeEditor.vue';

defineOptions({ name: 'SfCustomHtmlPanel' });

const { server, form, warnings, t } = useSiteFooterStore();
const htmlEditor = ref(null);
const cleanedDialog = ref(false);

function insert(token) {
  htmlEditor.value?.insert(token);
}

function useCleaned() {
  form.value.html = server.draft?.html || '';
  cleanedDialog.value = false;
}
</script>
