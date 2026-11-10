const ICONS = {
  pdf: ['mdi-file-pdf-box', 'red'],
  doc: ['mdi-file-word', 'blue'],
  docx: ['mdi-file-word', 'blue'],
  odt: ['mdi-file-word', 'blue'],
  rtf: ['mdi-file-word', 'blue'],
  xls: ['mdi-file-excel', 'green'],
  xlsx: ['mdi-file-excel', 'green'],
  csv: ['mdi-file-delimited', 'teal'],
  tsv: ['mdi-file-delimited', 'teal'],
  ppt: ['mdi-file-powerpoint', 'orange'],
  pptx: ['mdi-file-powerpoint', 'orange'],
  zip: ['mdi-folder-zip', 'orange'],
  rar: ['mdi-folder-zip', 'orange'],
  '7z': ['mdi-folder-zip', 'orange'],
  gz: ['mdi-folder-zip', 'orange'],
  tar: ['mdi-folder-zip', 'orange'],
  txt: ['mdi-file-document-outline', 'grey'],
  json: ['mdi-code-json', 'purple'],
  xml: ['mdi-file-code', 'purple'],
};

export function fileIcon(ext) {
  return ICONS[String(ext || '').toLowerCase()]?.[0] || 'mdi-file-outline';
}

export function fileColor(ext) {
  return ICONS[String(ext || '').toLowerCase()]?.[1] || 'grey';
}

export function formatFileSize(bytes) {
  const n = Number(bytes) || 0;
  if (n <= 0) return '0 B';
  const units = ['B', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.min(units.length - 1, Math.floor(Math.log(n) / Math.log(1024)));
  return `${Math.round((n / 1024 ** i) * 100) / 100} ${units[i]}`;
}

export function formatTimestamp(ts) {
  if (!ts) return '';
  const d = new Date(Number(ts) * 1000);
  return Number.isNaN(d.getTime()) ? '' : d.toLocaleString();
}
