/**
 * Normalize NADA / JsonSchema validation error payloads into a flat list.
 */
export function normalizeValidationErrors(errors) {
  const out = [];

  function push(property, message) {
    const msg = String(message ?? '').trim();
    if (!msg) return;
    const prop = property != null && property !== '' ? String(property) : '';
    out.push({ property: prop, message: msg });
  }

  function walk(value, path = '') {
    if (value == null) return;

    if (typeof value === 'string' || typeof value === 'number' || typeof value === 'boolean') {
      push(path, value);
      return;
    }

    if (Array.isArray(value)) {
      if (
        value.length &&
        value.every(
          (item) =>
            item &&
            typeof item === 'object' &&
            !Array.isArray(item) &&
            ('message' in item || 'property' in item)
        )
      ) {
        value.forEach((item) => {
          const prop = item.property != null ? item.property : path;
          const msg = item.message != null ? item.message : JSON.stringify(item);
          push(prop, msg);
        });
        return;
      }
      value.forEach((item, i) => walk(item, path ? `${path}[${i}]` : String(i)));
      return;
    }

    if (typeof value === 'object') {
      Object.keys(value).forEach((key) => {
        const next = path ? `${path}.${key}` : key;
        const child = value[key];
        if (Array.isArray(child) && child.every((x) => typeof x === 'string' || typeof x === 'number')) {
          child.forEach((msg) => push(next, msg));
        } else if (typeof child === 'string' || typeof child === 'number') {
          push(next, child);
        } else {
          walk(child, next);
        }
      });
    }
  }

  walk(errors);
  return out;
}

function issueSuffix(count) {
  const word = count === 1 ? 'issue' : 'issues';
  return ` (${count} ${word})`;
}

export function extractApiError(err, labels = {}) {
  const data = err?.response?.data || err?.responseData || null;
  const fallbackMessage = labels.requestFailed || 'Request failed';
  const message = (data && (data.message || data.error)) || err?.message || fallbackMessage;

  const rawErrors = data?.errors != null ? data.errors : err?.errors;
  const errors = normalizeValidationErrors(rawErrors);

  const validationFailed = labels.validationFailed || 'Validation failed';
  const saveFailed = labels.saveFailed || 'Save failed';

  let displayMessage = String(message);
  if (/^Request failed with status code \d+/i.test(displayMessage)) {
    displayMessage = errors.length ? validationFailed + issueSuffix(errors.length) : saveFailed;
  }
  if (displayMessage === 'VALIDATION_ERROR' || displayMessage === 'VALIDATION_ERRORS') {
    displayMessage = errors.length ? validationFailed + issueSuffix(errors.length) : validationFailed;
  }

  return {
    message: displayMessage,
    errors,
    status: data?.status || null,
    raw: data,
  };
}
