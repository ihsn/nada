/**
 * Playground for the global timeseries OpenAPI spec.
 *
 * Takes the spec as published and fills it in from the page's query string before showing it:
 *   ?idno=AG.CON.FERT.ZS            indicator ID; also replaces the generic c[…] box with one
 *                                   field per filterable column, read from that indicator's /schema
 *   &d[geography]=AFG,AFE&limit=25  any other parameter of the spec, pre-filled by name
 */
(function () {
  'use strict';

  var FILTER_COLUMN_TYPES = ['geography', 'periodicity', 'dimension'];
  var apiBase = new URL('../../index.php/api', location.href).href;
  var query = new URLSearchParams(location.search);
  var idno = (query.get('idno') || '').trim();

  function loadColumns() {
    if (!idno) return Promise.resolve([]);
    return fetch(apiBase + '/timeseries/data/' + encodeURIComponent(idno) + '/schema', {
      headers: { Accept: 'application/json' },
    })
      .then(function (res) {
        return res.ok ? res.json() : null;
      })
      .then(function (body) {
        var columns = body && body.result && body.result.components;
        return Array.isArray(columns) ? columns : [];
      })
      .catch(function () {
        return [];
      });
  }

  /** Replace the generic c[…] parameter on every operation with one named parameter per column. */
  function expandColumnFilters(spec, columns) {
    var requested = {};
    query.forEach(function (_, key) {
      var match = /^c\[(.+)\]$/.exec(key);
      if (match) requested[match[1]] = true;
    });
    var shown = columns.filter(function (col) {
      return col && col.name && (FILTER_COLUMN_TYPES.indexOf(col.column_type) !== -1 || requested[col.name]);
    });
    if (!shown.length) return;

    var refs = shown.map(function (col, i) {
      var key = 'column' + i;
      spec.components.parameters[key] = {
        name: 'c[' + col.name + ']',
        in: 'query',
        description: (col.label ? col.label + '. ' : '') + 'Codes separated by commas; see `/filter-options` for valid codes.',
        schema: { type: 'string' },
      };
      return { $ref: '#/components/parameters/' + key };
    });

    Object.keys(spec.paths).forEach(function (path) {
      var get = spec.paths[path].get;
      if (!get || !get.parameters) return;
      var at = get.parameters.findIndex(function (p) {
        return p.$ref === '#/components/parameters/c';
      });
      if (at !== -1) get.parameters.splice.apply(get.parameters, [at, 1].concat(refs));
    });
    delete spec.components.parameters.c;
  }

  /** Query-string values become each parameter's example, which Try it out uses as its starting value. */
  function prefill(spec) {
    var params = spec.components.parameters;
    if (idno) params.idno.example = idno;
    Object.keys(params).forEach(function (key) {
      var param = params[key];
      if (param.in === 'query' && query.has(param.name)) {
        param.example = query.get(param.name);
      }
    });
  }

  var customised = false;
  var ui = SwaggerUIBundle({
    url: '../../index.php/schemas/openapi/timeseries',
    dom_id: '#playground',
    deepLinking: true,
    tryItOutEnabled: true,
    displayRequestDuration: true,
    defaultModelsExpandDepth: -1,
    supportedSubmitMethods: ['get'],
    presets: [SwaggerUIBundle.presets.apis],
    layout: 'BaseLayout',
    onComplete: function () {
      if (customised) return;
      customised = true;
      var spec = ui.specSelectors.specJson().toJS();
      spec.servers = [{ url: apiBase }];
      loadColumns().then(function (columns) {
        expandColumnFilters(spec, columns);
        prefill(spec);
        ui.specActions.updateSpec(JSON.stringify(spec));
      });
    },
  });
})();
