import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';
import { createServer } from 'vite';

const here = path.dirname(fileURLToPath(import.meta.url));
const frontend = path.resolve(here, '../../..');
const root = path.dirname(frontend);

function portugueseTranslations() {
  const file = path.join(root, 'application/language/portuguese/analytics_lang.php');
  const json = execFileSync(
    'php',
    ['-r', '$lang = []; include $argv[1]; echo json_encode($lang, JSON_UNESCAPED_UNICODE);', file],
    { encoding: 'utf8' }
  );
  return JSON.parse(json);
}

async function withAnalytics(config, run) {
  globalThis.window = { APP_CONFIG: config };
  const server = await createServer({
    root: frontend,
    configFile: false,
    logLevel: 'silent',
    appType: 'custom',
    resolve: { alias: { '@': frontend } },
    server: { middlewareMode: true, hmr: false },
  });
  try {
    const formatters = await server.ssrLoadModule('/admin/analytics_reports/utils/formatters.js');
    const messages = await server.ssrLoadModule('/admin/analytics_reports/composables/useAggregationRunner.js');
    const api = await server.ssrLoadModule('/admin/analytics_reports/composables/useAnalyticsApi.js');
    const constants = await server.ssrLoadModule('/admin/analytics_reports/utils/constants.js');
    const i18n = await server.ssrLoadModule('/shared/composables/useI18n.js');
    await run({ formatters, messages, api, constants, t: i18n.useI18n().t });
  } finally {
    await server.close();
    delete globalThis.window;
  }
}

test('analytics translations and English fallbacks', async (t) => {
  await t.test('renders Portuguese labels, values and aggregation messages', async () => {
    await withAnalytics(
      { analyticsLocale: 'pt-PT', translations: portugueseTranslations() },
      ({ formatters, messages, api, constants, t: translate }) => {
        assert.equal(translate('analytics_pageviews_today', 'Pageviews today'), 'Visualizações de página hoje');
        assert.equal(
          translate('analytics_showing_items', 'Showing %s–%s of %s', 1001, 1100, 12345),
          'A mostrar 1001–1100 de 12345'
        );
        assert.equal(constants.MONTH_OPTIONS[0].title, 'Janeiro');
        assert.equal(constants.MONTHLY_CHART_RANGE_OPTIONS[0].title, 'Últimos 3 meses');
        const monthLabel = formatters.formatMonthLabel({ year: 2025, month: 2, label: 'Feb 2025' });
        assert.equal(
          monthLabel,
          new Date(Date.UTC(2025, 1, 1)).toLocaleDateString('pt-PT', {
            month: 'short',
            year: 'numeric',
            timeZone: 'UTC',
          })
        );
        assert.notEqual(monthLabel, 'Feb 2025');
        assert.equal(formatters.timeAgo(new Date(Date.now() - 86400000).toISOString()), 'há 1 dia');
        assert.equal(
          messages.localizeAggregationMessage('Processed 2025-03-01. 2 date(s) remaining.', ''),
          'Processada a data 2025-03-01. Datas ainda por processar: 2.'
        );
        assert.equal(
          messages.localizeAggregationMessage('Monthly aggregates updated for 2025-2. 3 month(s) remaining to roll up.', ''),
          'Agregados mensais actualizados para 2025-2. Meses ainda por agregar: 3.'
        );
        assert.equal(
          messages.localizeAggregationMessage('Legacy migration complete. Backed up 3 survey(s), seeded 2 legacy total(s) into analytics.', ''),
          'Migração de dados anteriores concluída. Cópias de segurança de estudos: 3, totais anteriores adicionados às estatísticas: 2.'
        );
        assert.equal(messages.localizeAggregationMessage('Unexpected technical detail', ''), 'Unexpected technical detail');
        assert.equal(api.apiErrorMessage({}), 'O pedido falhou');
        assert.equal(api.apiErrorMessage({ response: { data: { message: 'detail' } } }), 'detail');
      }
    );
  });

  await t.test('preserves English when translations are unavailable', async () => {
    await withAnalytics(
      { analyticsLocale: 'en-US', translations: {} },
      ({ formatters, messages, api, constants, t: translate }) => {
        assert.equal(translate('analytics_pageviews_today', 'Pageviews today'), 'Pageviews today');
        assert.equal(constants.MONTH_OPTIONS[0].title, 'January');
        assert.equal(constants.MONTHLY_CHART_RANGE_OPTIONS[0].title, 'Last 3 months');
        assert.equal(formatters.formatMonthLabel({ year: 2025, month: 2, label: 'Feb 2025' }), 'Feb 2025');
        const status = 'Monthly aggregates updated for 2025-2. 3 month(s) remaining to roll up.';
        assert.equal(messages.localizeAggregationMessage(status, ''), status);
        assert.equal(api.apiErrorMessage({}), 'REQUEST_FAILED');
      }
    );
  });
});
