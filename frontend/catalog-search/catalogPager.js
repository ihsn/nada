/**
 * What the result pager shows and where each button goes (CatalogPager.vue), kept free of Vue so it can be checked on
 * its own.
 *
 * Layout: "Page N of M" on the left; on the right ‹ and › around the page numbers — the first page ("1 …"), a window of
 * pages around the current one, and the last page only when it really is the end and can be reached by number. There
 * is no "last page" button otherwise: the end of a long list is reached by reversing the sort.
 *
 * Page numbers work up to the last page the server pages by number (`max_offset` rows); past it ‹ and › follow the
 * result's cursors (`next_cursor`/`prev_cursor`) and only "1 …" and the current page are shown, since a cursor knows
 * its neighbours only. A result without `max_offset` (served by the database search) pages by number to its end.
 *
 * @param {object} results    a search result: found, found_is_lower_bound, page, max_offset, next_cursor, prev_cursor
 * @param {object} query      the URL query: page, ps
 * @param {number} windowSize page numbers shown around the current page (site setting catalog_pager_numbers)
 */
export function catalogPagerModel(results, query, windowSize = 5) {
  const pageSize = Number(query?.ps) || 15;
  const found = Number(results?.found) || 0;
  const current = Number(results?.page) || Number(query?.page) || 1;
  const maxOffset = results?.max_offset != null ? Number(results.max_offset) : null;
  const lowerBound = Boolean(results?.found_is_lower_bound);
  const totalPages = Math.max(1, Math.ceil(found / pageSize));
  /** The last page reached by page number; past it, by cursor. */
  const lastNumbered = maxOffset != null ? Math.floor(maxOffset / pageSize) : Infinity;
  /** The last page a number can reach, and whether it is the real end of the list. */
  const reachable = lowerBound ? lastNumbered : Math.min(totalPages, lastNumbered);
  const endIsReachable = !lowerBound && totalPages <= lastNumbered;

  /** @type {Array<{type: 'page', page: number, current: boolean} | {type: 'gap'}>} */
  const items = [];
  const page = (n) => items.push({ type: 'page', page: n, current: n === current });
  if (current <= reachable) {
    const size = Math.max(1, windowSize);
    let start = Math.max(1, current - Math.floor(size / 2));
    const end = Math.min(reachable, start + size - 1);
    start = Math.max(1, end - size + 1);
    if (start > 1) {
      page(1);
      if (start > 2) items.push({ type: 'gap' });
    }
    for (let n = start; n <= end; n += 1) page(n);
    if (endIsReachable) {
      if (end < totalPages - 1) items.push({ type: 'gap' });
      if (end < totalPages) page(totalPages);
    } else if (end < totalPages || lowerBound) {
      items.push({ type: 'gap' }); // more beyond, reached with ›
    }
  } else {
    page(1);
    items.push({ type: 'gap' });
    page(current);
  }

  const hasPrev = current > 1;
  // with no cursor, the next page exists while pages remain within what is paged by number (a keyword study search,
  // a database-served result); a cursor says so past it
  const hasNext = results?.next_cursor ? true : current < Math.min(totalPages, lastNumbered);

  /** @returns {{page: number}|{cursor: string}} */
  function target(n, cursor) {
    return n <= lastNumbered ? { page: n } : { cursor };
  }

  return {
    current,
    totalPages,
    lowerBound,
    items,
    hasPrev,
    hasNext,
    /** A page reached by cursor: past the pages numbered by the server. */
    pastNumbered: results?.paging === 'cursor' && maxOffset != null,
    maxOffset,
    next: () => target(current + 1, results?.next_cursor),
    prev: () => target(current - 1, results?.prev_cursor),
  };
}
