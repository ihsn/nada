/**
 * Pagination range for catalog result toolbars: rows from–to of total.
 * Uses the result's own offset when it has one (a page reached by cursor has no page number to work it out from),
 * else (page-1)*pageSize. `lowerBound`: the total is only a lower bound ("20,000+").
 */
export function catalogResultsRange(results, query) {
  const rowCount = results?.rows?.length ?? 0;
  const found = Number(results?.found ?? 0);
  const page = Number(query?.page ?? 1);
  const pageSize = Number(query?.ps ?? 15);
  const resultOffset = results?.offset != null ? Number(results.offset) : NaN;
  const offset = Number.isFinite(resultOffset) ? resultOffset : (page - 1) * pageSize;
  const lowerBound = Boolean(results?.found_is_lower_bound);

  if (rowCount <= 0) {
    return { from: 0, to: 0, total: found > 0 ? found : 0, lowerBound };
  }

  const from = offset + 1;
  const to = offset + rowCount;
  const total = found > 0 ? found : to;

  return { from, to, total, lowerBound };
}
