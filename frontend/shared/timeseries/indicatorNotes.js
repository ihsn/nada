/**
 * Cell and table notes for indicator chart/table views (published from the Metadata Editor).
 *
 * The chart API (`/api/timeseries/data/{idno}/chart`) returns per record `notes: { FOOTNOTE_COLUMN: [ids] }`,
 * the text once in `notes: { id: { note, column? } }`, and `table_notes: [{ id, note }]`.
 * Markers are numbered 1..n per view in order of first appearance; each note is listed once.
 * Note IDs are internal: they are never shown to readers.
 */

/** Unique note IDs of one record, in footnote-column order then ID order. */
export function cellNoteIds(record) {
  const out = [];
  const notes = record && record.notes;
  if (!notes || typeof notes !== 'object') return out;
  for (const col of Object.keys(notes)) {
    const ids = notes[col];
    if (!Array.isArray(ids)) continue;
    for (const raw of ids) {
      const id = String(raw).trim();
      if (id !== '' && !out.includes(id)) out.push(id);
    }
  }
  return out;
}

/**
 * Numbering for one view.
 * @param {Record<string, { note?: string }>} dictionary
 */
export function createNoteNumbering(dictionary) {
  const dict = dictionary && typeof dictionary === 'object' ? dictionary : {};
  const numberById = new Map();
  const order = [];
  return {
    /** Ascending marker numbers for a cell's IDs (assigns new numbers on first use). */
    markers(ids) {
      const nums = [];
      for (const id of ids || []) {
        if (!numberById.has(id)) {
          order.push(id);
          numberById.set(id, order.length);
        }
        const n = numberById.get(id);
        if (!nums.includes(n)) nums.push(n);
      }
      return nums.sort((a, b) => a - b);
    },
    /** Notes used in this view: [{ number, id, text, missing }] in marker order. */
    list() {
      return order.map((id, i) => {
        const entry = dict[id];
        const text = entry && entry.note != null ? String(entry.note) : '';
        return { number: i + 1, id, text, missing: text.trim() === '' };
      });
    },
  };
}

/** "1,3" for superscripts. */
export function formatMarkers(numbers) {
  return (numbers || []).join(',');
}

/** "n. text" lines for marker numbers, using a view's notes list. */
export function noteLines(numbers, notesList, missingText, maxLength = 0) {
  const byNumber = new Map((notesList || []).map((n) => [n.number, n]));
  return (numbers || []).map((num) => {
    const note = byNumber.get(num);
    let text = note ? (note.missing ? missingText : note.text) : '';
    if (maxLength && text.length > maxLength) text = `${text.slice(0, maxLength - 3)}...`;
    return `${num}. ${text}`;
  });
}

/**
 * Number the notes of a pivot model (rows, then columns, as displayed) and return a copy whose rows carry
 * `noteMarkers: { colKey: number[] }`, plus the notes list for the view.
 * @param {object|null} model result of pivotIndicatorTable (rows may carry `noteIds: { colKey: string[] }`)
 * @param {Record<string, { note?: string }>} dictionary
 */
export function applyPivotNotes(model, dictionary) {
  if (!model) return { model, notes: [] };
  const numbering = createNoteNumbering(dictionary);
  const leafColumns = model.leafColumns || [];
  const bodySections = (model.bodySections || []).map((section) => ({
    ...section,
    rows: (section.rows || []).map((row) => {
      if (!row.noteIds) return row;
      const noteMarkers = {};
      for (const col of leafColumns) {
        const ids = row.noteIds[col.key];
        if (ids && ids.length) noteMarkers[col.key] = numbering.markers(ids);
      }
      return { ...row, noteMarkers };
    }),
  }));
  return { model: { ...model, bodySections }, notes: numbering.list() };
}
