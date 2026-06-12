/**
 * Pagination range for catalog result toolbars.
 * Matches legacy PHP: to = (page-1)*pageSize + rowsOnPage (not pageSize * page capped by found).
 */
export function catalogResultsRange(results, query) {
  const rowCount = results?.rows?.length ?? 0;
  const found = Number(results?.found ?? 0);
  const page = Number(query?.page ?? 1);
  const pageSize = Number(query?.ps ?? 15);
  const offset = (page - 1) * pageSize;

  if (rowCount <= 0) {
    return { from: 0, to: 0, total: found > 0 ? found : 0 };
  }

  const from = offset + 1;
  const to = offset + rowCount;
  const total = found > 0 ? found : to;

  return { from, to, total };
}
