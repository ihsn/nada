<template>
  <div class="sl-stack">
    <v-alert v-if="warnings.includes('CSS_CLEANED')" type="warning" variant="tonal" density="compact">
      {{
        t(
          'sf_css_cleaned',
          'Some CSS was removed: @import, expressions, backslash escapes and url() addresses on other sites are not allowed.'
        )
      }}
    </v-alert>
    <div class="sl-field">
      <expandable-code-editor
        v-model="form.css"
        language="css"
        min-height="140px"
        :label="t('sf_css', 'Extra CSS')"
        :limit="server.limits.css"
        :expand-label="t('sf_expand', 'Expand editor')"
        :format-label="t('sf_format', 'Format')"
        :done-label="t('sf_done', 'Done')"
        :expanded-hint="t('sf_expanded_hint', 'Editing in the expanded window…')"
      />
      <div class="sl-help">
        {{
          t(
            'sf_css_rules',
            'Applies to custom HTML. Start selectors with .site-footer so the rest of the page is not affected. Images must come from this site.'
          )
        }}
      </div>
    </div>
    <div class="sl-help">
      {{ t('sf_css_classes', 'Useful classes: .site-footer, .sf-title, .sf-copy, .sf-menu.') }}
    </div>
    <div class="sl-field">
      <div class="sl-label">{{ t('sf_css_example_label', 'Example') }}</div>
      <pre class="sf-css-example">{{ t('sf_css_example', '.site-footer { background: #111; }') }}</pre>
    </div>
  </div>
</template>

<script setup>
import { useSiteFooterStore } from '../composables/useSiteFooterStore';
import ExpandableCodeEditor from '../../shared/ExpandableCodeEditor.vue';

defineOptions({ name: 'SfCssPanel' });

const { server, form, warnings, t } = useSiteFooterStore();
</script>

<style scoped>
.sf-css-example {
  margin: 0;
  font-size: 0.75rem;
  line-height: 1.4;
}
.sf-css-example {
  padding: 4px 6px;
  border-radius: 4px;
  background: rgba(var(--v-theme-on-surface), 0.04);
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  white-space: pre-wrap;
  overflow-x: auto;
  color: rgba(var(--v-theme-on-surface), 0.7);
}
</style>
