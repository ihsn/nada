import {
  SINGLE_COL_KEY,
  SINGLE_ROW_KEY,
  TIME_PERIOD_KEY,
  normalizeTableLayout,
} from './indicatorSchemaUtils.js';

/** Parse "A=x | B=y" series key into { A: "x", B: "y" }. */
export function seriesColumnsFromKey(seriesKey) {
  const out = {};
  const raw = String(seriesKey ?? '').trim();
  if (!raw || raw === 'Series') return out;
  const segments = raw.split(' | ');
  for (const seg of segments) {
    const eq = seg.indexOf('=');
    if (eq <= 0) continue;
    const key = seg.slice(0, eq).trim();
    const val = seg.slice(eq + 1).trim();
    if (!key) continue;
    out[key] = val;
  }
  return out;
}

function normalizeLayoutKey(dimKey, timePeriodComponentName) {
  const k = String(dimKey ?? '').trim();
  if (!k) return '';
  if (k === TIME_PERIOD_KEY) return TIME_PERIOD_KEY;
  if (timePeriodComponentName && k === timePeriodComponentName) return TIME_PERIOD_KEY;
  return k;
}

function isTimeDim(dimKey, timePeriodComponentName) {
  return normalizeLayoutKey(dimKey, timePeriodComponentName) === TIME_PERIOD_KEY;
}

function getRecordDimensionValue(record, dimKey, timePeriodComponentName) {
  const key = normalizeLayoutKey(dimKey, timePeriodComponentName);
  if (!key) return '';

  if (key === TIME_PERIOD_KEY) {
    if (record?.time_period != null && String(record.time_period) !== '') {
      return String(record.time_period);
    }
    if (timePeriodComponentName && record?.[timePeriodComponentName] != null) {
      return String(record[timePeriodComponentName]);
    }
    return '';
  }

  if (record?.[key] != null && record[key] !== '') {
    return String(record[key]);
  }
  const parsed = seriesColumnsFromKey(record?.series_key);
  return parsed[key] != null ? String(parsed[key]) : '';
}

function buildPath(dimKeys, record, timePeriodComponentName) {
  if (!dimKeys.length) return [];
  return dimKeys.map((k) => getRecordDimensionValue(record, k, timePeriodComponentName));
}

function pathToKey(parts) {
  return parts.join('\x00');
}

function comparePart(a, b, dimKey, timePeriodComponentName, timeOrder) {
  if (isTimeDim(dimKey, timePeriodComponentName)) {
    const cmp = String(a).localeCompare(String(b), undefined, { numeric: true, sensitivity: 'base' });
    return timeOrder === 'desc' ? -cmp : cmp;
  }
  return String(a).localeCompare(String(b), undefined, { numeric: true, sensitivity: 'base' });
}

function comparePaths(pathA, pathB, dimKeys, timePeriodComponentName, timeOrder) {
  const len = Math.max(pathA.length, pathB.length);
  for (let i = 0; i < len; i += 1) {
    const a = pathA[i] ?? '';
    const b = pathB[i] ?? '';
    if (a === b) continue;
    const dimKey = dimKeys[i] ?? '';
    return comparePart(a, b, dimKey, timePeriodComponentName, timeOrder);
  }
  return 0;
}

function sortPaths(paths, dimKeys, timePeriodComponentName, timeOrder) {
  return [...paths].sort((a, b) => comparePaths(a, b, dimKeys, timePeriodComponentName, timeOrder));
}

function labelForPathPart(dimKey, code, resolveLabel) {
  return resolveLabel(dimKey, code);
}

function buildFlatLabel(pathParts, dimKeys, resolveLabel) {
  return pathParts
    .map((code, i) => labelForPathPart(dimKeys[i], code, resolveLabel))
    .filter(Boolean)
    .join(' · ');
}

/**
 * Build multi-row column header structure from sorted leaf paths.
 * @returns {{ headerRows: Array<Array<{ title: string; key?: string; colspan: number; rowspan: number }>>; leafColumns: Array<{ key: string; title: string }> }}
 */
function buildGroupedColumnHeaders(sortedPaths, dimKeys, resolveLabel) {
  if (!dimKeys.length) {
    return {
      headerRows: [[{ title: 'Value', key: SINGLE_COL_KEY, colspan: 1, rowspan: 1 }]],
      leafColumns: [{ key: SINGLE_COL_KEY, title: 'Value' }],
    };
  }

  if (dimKeys.length === 1) {
    const leafColumns = sortedPaths.map((parts) => ({
      key: pathToKey(parts),
      title: labelForPathPart(dimKeys[0], parts[0], resolveLabel),
    }));
    return {
      headerRows: [
        leafColumns.map((c) => ({ title: c.title, key: c.key, colspan: 1, rowspan: 1 })),
      ],
      leafColumns,
    };
  }

  const depth = dimKeys.length;
  const headerRows = Array.from({ length: depth }, () => []);
  const leafColumns = sortedPaths.map((parts) => ({
    key: pathToKey(parts),
    title: labelForPathPart(dimKeys[depth - 1], parts[depth - 1], resolveLabel),
  }));

  for (let level = 0; level < depth - 1; level += 1) {
    let i = 0;
    while (i < sortedPaths.length) {
      const prefix = sortedPaths[i].slice(0, level + 1);
      let j = i + 1;
      while (j < sortedPaths.length) {
        const nextPrefix = sortedPaths[j].slice(0, level + 1);
        if (pathToKey(nextPrefix) !== pathToKey(prefix)) break;
        j += 1;
      }
      const span = j - i;
      headerRows[level].push({
        title: labelForPathPart(dimKeys[level], prefix[level], resolveLabel),
        colspan: span,
        rowspan: 1,
      });
      i = j;
    }
  }

  headerRows[depth - 1] = leafColumns.map((c) => ({
    title: c.title,
    key: c.key,
    colspan: 1,
    rowspan: 1,
  }));

  // Mark which cells/columns start a new top-level column group (excluding the first group).
  // Used by the table to render a subtle left border at group boundaries without a full grid.
  if (headerRows[0].length > 1) {
    const topGroupStarts = new Set();
    let gPos = 0;
    for (let gi = 0; gi < headerRows[0].length; gi += 1) {
      if (gi > 0) {
        topGroupStarts.add(gPos);
        headerRows[0][gi].groupStart = true;
      }
      gPos += headerRows[0][gi].colspan || 1;
    }
    for (let level = 1; level < depth; level += 1) {
      let leafPos = 0;
      for (const cell of headerRows[level]) {
        if (topGroupStarts.has(leafPos)) cell.groupStart = true;
        leafPos += cell.colspan || 1;
      }
    }
    leafColumns.forEach((col, i) => {
      if (topGroupStarts.has(i)) col.groupStart = true;
    });
  }

  return { headerRows, leafColumns };
}

function buildFlatColumnHeaders(sortedPaths, dimKeys, resolveLabel) {
  const leafColumns = sortedPaths.map((parts) => ({
    key: pathToKey(parts),
    title: buildFlatLabel(parts, dimKeys, resolveLabel),
  }));
  return {
    headerRows: [leafColumns.map((c) => ({ title: c.title, key: c.key, colspan: 1, rowspan: 1 }))],
    leafColumns,
  };
}

/**
 * Row body with optional nested section banners (outer dims) and leaf row labels (innermost dim).
 */
function buildGroupedRowBody(
  sortedRowPaths,
  rowDimKeys,
  leafColumns,
  cells,
  resolveLabel,
  formatValue,
  useGroupedRows
) {
  function cellsForRow(rowKey) {
    const cellMap = {};
    for (const col of leafColumns) {
      const raw = cells.get(`${rowKey}\x01${col.key}`);
      cellMap[col.key] = raw != null ? formatValue(raw) : '';
    }
    return cellMap;
  }

  function buildSections(paths, dimKeys, level) {
    if (!paths.length) return [];

    if (!useGroupedRows || dimKeys.length <= 1) {
      return [
        {
          sectionKey: '',
          title: '',
          depth: 0,
          rows: paths.map((parts) => {
            const rowKey = pathToKey(parts);
            let label = '';
            if (dimKeys.length === 1) {
              label = labelForPathPart(dimKeys[0], parts[0], resolveLabel);
            } else if (dimKeys.length > 1) {
              label = buildFlatLabel(parts, dimKeys, resolveLabel);
            }
            return { rowKey, label, cells: cellsForRow(rowKey) };
          }),
        },
      ];
    }

    if (level >= dimKeys.length - 1) {
      const leafDim = dimKeys[dimKeys.length - 1];
      return [
        {
          sectionKey: '',
          title: '',
          depth: level,
          rows: paths.map((parts) => {
            const rowKey = pathToKey(parts);
            return {
              rowKey,
              label: labelForPathPart(leafDim, parts[parts.length - 1], resolveLabel),
              cells: cellsForRow(rowKey),
            };
          }),
        },
      ];
    }

    const sections = [];
    let i = 0;
    while (i < paths.length) {
      const code = paths[i][level];
      let j = i + 1;
      while (j < paths.length && paths[j][level] === code) j += 1;
      const chunk = paths.slice(i, j);
      sections.push({
        sectionKey: pathToKey(chunk[0].slice(0, level + 1)),
        title: labelForPathPart(dimKeys[level], code, resolveLabel),
        depth: level,
        rows: [],
        isGroupHeader: true,
      });
      sections.push(...buildSections(chunk, dimKeys, level + 1));
      i = j;
    }
    return sections;
  }

  const bodySections = buildSections(sortedRowPaths, rowDimKeys, 0);
  return { bodySections, rowCount: sortedRowPaths.length };
}

/**
 * @param {Array<object>} records
 * @param {import('./indicatorSchemaUtils.js').TableLayoutSpec} layout
 * @param {{
 *   timePeriodComponentName?: string;
 *   resolveLabel?: (dimKey: string, code: string) => string;
 *   formatValue?: (value: number | string | null | undefined) => string;
 *   singleRowLabel?: string;
 * }} [options]
 */
export function pivotIndicatorTable(records, layout, options = {}) {
  const {
    timePeriodComponentName = '',
    resolveLabel = (_dimKey, code) => code,
    formatValue = (v) => (v == null || v === '' ? '' : String(v)),
    singleRowLabel = '',
  } = options;

  const norm = normalizeTableLayout(layout);
  const rowDims = norm.rows;
  const colDims = norm.columns;
  const timeOrder = norm.time_order;
  const useGroupedHeaders = layoutUsesGrouped(norm);

  const cells = new Map();
  const colPathSet = new Map();
  const rowPathSet = new Map();

  for (const record of records || []) {
    if (!record || typeof record !== 'object') continue;
    const val = record.observation_value;
    if (val == null || val === '' || !Number.isFinite(Number(val))) continue;

    const colPath = colDims.length ? buildPath(colDims, record, timePeriodComponentName) : [];
    const rowPath = rowDims.length ? buildPath(rowDims, record, timePeriodComponentName) : [];

    if (colDims.length && colPath.some((p) => p === '')) continue;
    if (rowDims.length && rowPath.some((p) => p === '')) continue;

    const colKey = colDims.length ? pathToKey(colPath) : SINGLE_COL_KEY;
    const rowKey = rowDims.length ? pathToKey(rowPath) : SINGLE_ROW_KEY;

    if (colDims.length) colPathSet.set(colKey, colPath);
    if (rowDims.length) rowPathSet.set(rowKey, rowPath);

    cells.set(`${rowKey}\x01${colKey}`, Number(val));
  }

  let sortedColPaths = colDims.length
    ? sortPaths([...colPathSet.values()], colDims, timePeriodComponentName, timeOrder)
    : [[]];

  let sortedRowPaths = rowDims.length
    ? sortPaths([...rowPathSet.values()], rowDims, timePeriodComponentName, timeOrder)
    : [[]];

  if (!rowDims.length && (colDims.length || cells.size)) {
    sortedRowPaths = [[]];
    rowPathSet.set(SINGLE_ROW_KEY, []);
  }

  if (!colDims.length) {
    sortedColPaths = [[]];
    colPathSet.set(SINGLE_COL_KEY, []);
  }

  let colHeaders;
  if (!colDims.length) {
    colHeaders = {
      headerRows: [[{ title: 'Value', key: SINGLE_COL_KEY, colspan: 1, rowspan: 1 }]],
      leafColumns: [{ key: SINGLE_COL_KEY, title: 'Value' }],
    };
  } else if (norm.flatten_labels || (colDims.length > 1 && !useGroupedHeaders)) {
    colHeaders = buildFlatColumnHeaders(sortedColPaths, colDims, resolveLabel);
  } else if (colDims.length > 1) {
    colHeaders = buildGroupedColumnHeaders(sortedColPaths, colDims, resolveLabel);
  } else {
    colHeaders = buildGroupedColumnHeaders(sortedColPaths, colDims, resolveLabel);
  }

  const useGroupedRows = useGroupedHeaders && rowDims.length > 1;
  const { bodySections, rowCount } = buildGroupedRowBody(
    sortedRowPaths,
    rowDims,
    colHeaders.leafColumns,
    cells,
    resolveLabel,
    formatValue,
    useGroupedRows
  );

  const cornerRowspan = colHeaders.headerRows.length;
  const showRowLabels = rowDims.length > 0;

  if (!rowDims.length) {
    const rows = [
      {
        rowKey: SINGLE_ROW_KEY,
        label: singleRowLabel,
        cells: Object.fromEntries(
          colHeaders.leafColumns.map((c) => {
            const raw = cells.get(`${SINGLE_ROW_KEY}\x01${c.key}`);
            return [c.key, raw != null ? formatValue(raw) : ''];
          })
        ),
      },
    ];
    return {
      headerRows: colHeaders.headerRows,
      leafColumns: colHeaders.leafColumns,
      bodySections: [{ sectionKey: '', title: '', depth: 0, rows }],
      cornerRowspan,
      showRowLabels: false,
      rowCount: rows.length,
      columnCount: colHeaders.leafColumns.length,
      grouped: useGroupedHeaders,
    };
  }

  return {
    headerRows: colHeaders.headerRows,
    leafColumns: colHeaders.leafColumns,
    bodySections,
    cornerRowspan,
    showRowLabels,
    rowCount,
    columnCount: colHeaders.leafColumns.length,
    grouped: useGroupedHeaders,
  };
}

function layoutUsesGrouped(norm) {
  if (norm.flatten_labels || norm.group_headers === false) return false;
  return norm.rows.length > 1 || norm.columns.length > 1;
}
