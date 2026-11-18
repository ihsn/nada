<template>
  <div class="sl-stack">
    <v-alert v-if="warnings.includes('CSS_CLEANED')" type="warning" variant="tonal" density="compact">
      {{ t('sh_css_cleaned', 'Some CSS was removed: @import, expressions, backslash escapes and url() addresses on other sites are not allowed.') }}
    </v-alert>
    <div class="sl-field">
      <expandable-code-editor
        v-model="form.css"
        language="css"
        min-height="160px"
        :label="t('sh_css', 'Extra CSS')"
        :limit="server.limits.css"
        :expand-label="t('sh_expand', 'Expand editor')"
        :format-label="t('sh_format', 'Format')"
        :done-label="t('sh_done', 'Done')"
        :expanded-hint="t('sh_expanded_hint', 'Editing in the expanded window…')"
      />
      <div class="sl-help">
        {{ t('sh_css_rules', 'Applies to the builder and to custom HTML. Start selectors with .site-header so the rest of the page is not affected. Images must come from this site.') }}
      </div>
    </div>
    <table class="sh-css-table">
      <thead>
        <tr>
          <th>{{ t('sh_css_col_element', 'Element') }}</th>
          <th>{{ t('sh_css_col_class', 'Class') }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in classRows" :key="row.cls">
          <td>{{ row.label }}</td>
          <td><code>{{ row.cls }}</code></td>
        </tr>
      </tbody>
    </table>
    <div class="sl-field">
      <div class="sl-label">{{ t('sh_css_example_label', 'Example') }}</div>
      <pre class="sh-css-example">{{ t('sh_css_example', '.site-header .sh-search { max-width: 280px; }') }}</pre>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useSiteHeaderStore } from '../composables/useSiteHeaderStore';
import ExpandableCodeEditor from '../../shared/ExpandableCodeEditor.vue';

defineOptions({ name: 'ShCssPanel' });

const { server, form, warnings, t } = useSiteHeaderStore();

const classRows = computed(() => [
  { label: t('sh_item_brand', 'Brand (logo / title)'), cls: '.sh-brand' },
  { label: t('sh_css_el_logo', 'Logo only'), cls: '.sh-logo' },
  { label: t('sh_item_menu', 'Menu'), cls: '.sh-menu' },
  { label: t('sh_item_login', 'Login'), cls: '.sh-login' },
  { label: t('sh_item_language', 'Languages'), cls: '.sh-language' },
  { label: t('sh_item_search', 'Search'), cls: '.sh-search' },
  { label: t('sh_item_text', 'Text or link'), cls: '.sh-text' },
  { label: t('sh_row_top', 'Top bar'), cls: '.sh-row-top' },
  { label: t('sh_row_main', 'Main row'), cls: '.sh-row-main' },
  { label: t('sh_row_bottom', 'Bottom bar'), cls: '.sh-row-bottom' },
  { label: t('sh_left', 'Left'), cls: '.sh-slot-left' },
  { label: t('sh_center', 'Center'), cls: '.sh-slot-center' },
  { label: t('sh_right', 'Right'), cls: '.sh-slot-right' },
]);
</script>

<style scoped>
.sh-css-table,
.sh-css-example {
  margin: 0;
  font-size: 0.75rem;
  line-height: 1.4;
}
.sh-css-table {
  width: 100%;
  border-collapse: collapse;
  color: rgba(var(--v-theme-on-surface), 0.7);
}
.sh-css-table th,
.sh-css-table td {
  padding: 2px 6px;
  text-align: left;
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  vertical-align: top;
}
.sh-css-table th {
  font-weight: 600;
  color: rgba(var(--v-theme-on-surface), 0.55);
}
.sh-css-table code {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: inherit;
}
.sh-css-example {
  padding: 4px 6px;
  border-radius: 4px;
  background: rgba(var(--v-theme-on-surface), 0.04);
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  white-space: pre-wrap;
  overflow-x: auto;
  color: rgba(var(--v-theme-on-surface), 0.7);
}
</style>
