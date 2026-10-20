/**
 * NADA-AI runs one job per key at a time and answers 409 with the job already holding it
 * ({detail, job: {kind, status, progress, ...}}). These turn that answer into a message that says what is running.
 */

const JOB_LABELS = {
  index_variables: 'Index every variable',
  recreate_variables: 'Rebuild the variable index',
  index_citations: 'Index every citation',
  recreate_citations: 'Rebuild the citation index',
};

/** The job a 409 says is already running, or null when the error is anything else. */
export function conflictingJob(e) {
  if (e?.response?.status !== 409) return null;
  return e.response.data?.job || {};
}

/** "Rebuild the variable index is already running (42% done). ..." for a 409, or null for any other error. */
export function conflictMessage(e) {
  const job = conflictingJob(e);
  if (job === null) return null;
  const label = JOB_LABELS[job.kind] || (job.kind ? `A ${job.kind} job` : 'Another job');
  const percent = Number(job.progress?.percent);
  const done = Number.isFinite(percent) ? ` (${Math.round(percent)}% done)` : '';
  return `${label} is already running${done}. Nothing new was started; try again when it has finished.`;
}
