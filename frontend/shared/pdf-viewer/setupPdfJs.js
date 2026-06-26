import * as pdfjsLib from 'pdfjs-dist';
import pdfjsWorker from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

let configured = false;

export function setupPdfJs() {
  if (configured) return pdfjsLib;
  pdfjsLib.GlobalWorkerOptions.workerSrc = pdfjsWorker;
  configured = true;
  return pdfjsLib;
}

export { pdfjsLib };
