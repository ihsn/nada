const LEAFLET_CSS = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
const LEAFLET_JS = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';

let loading = null;

function injectStylesheet(href) {
  if (document.querySelector(`link[data-mf-leaflet="css"]`)) return;
  const link = document.createElement('link');
  link.rel = 'stylesheet';
  link.href = href;
  link.setAttribute('data-mf-leaflet', 'css');
  document.head.appendChild(link);
}

function injectScript(src) {
  return new Promise((resolve, reject) => {
    const existing = document.querySelector(`script[data-mf-leaflet="js"]`);
    if (existing) {
      if (window.L) {
        resolve(window.L);
        return;
      }
      existing.addEventListener('load', () => resolve(window.L), { once: true });
      existing.addEventListener('error', () => reject(new Error('Failed to load Leaflet')), {
        once: true,
      });
      return;
    }
    const script = document.createElement('script');
    script.src = src;
    script.async = true;
    script.setAttribute('data-mf-leaflet', 'js');
    script.onload = () => resolve(window.L);
    script.onerror = () => reject(new Error('Failed to load Leaflet'));
    document.head.appendChild(script);
  });
}

/**
 * Load Leaflet once (CDN), matching the public study bounding-box views.
 * @returns {Promise<typeof import('leaflet')>}
 */
export function loadLeaflet() {
  if (typeof window !== 'undefined' && window.L) {
    return Promise.resolve(window.L);
  }
  if (!loading) {
    injectStylesheet(LEAFLET_CSS);
    loading = injectScript(LEAFLET_JS).then((L) => {
      if (!L) throw new Error('Leaflet loaded without global L');
      return L;
    });
  }
  return loading;
}
