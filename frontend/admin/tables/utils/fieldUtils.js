export function emptyCodeListReference() {
  return { id: '', name: '', version: '', uri: '', note: '' };
}

export function normalizeCodeListReference(ref) {
  if (!ref || typeof ref !== 'object') {
    return emptyCodeListReference();
  }
  return {
    id: ref.id || '',
    name: ref.name || '',
    version: ref.version || '',
    uri: ref.uri || '',
    note: ref.note || '',
  };
}

/** @param {object} field */
export function normalizeFieldFromApi(field) {
  const codeListRef = normalizeCodeListReference(field.code_list_reference);
  return {
    name: field.name,
    label: field.label !== undefined && field.label !== null ? field.label : field.title || field.name,
    data_type: field.data_type || field.dataType || 'string',
    column_type: field.column_type || '',
    description: field.description !== undefined && field.description !== null ? field.description : '',
    time_period_format: field.time_period_format || '',
    unit_of_measurement: field.unit_of_measurement || '',
    format: field.format || '',
    field_order: field.field_order || 0,
    code_list: field.code_list || [],
    code_list_reference: codeListRef,
  };
}

/** @param {object} field */
export function cloneFieldForEdit(field) {
  const codeListRef = field.code_list_reference || null;
  return {
    ...field,
    code_list: field.code_list ? field.code_list.map((item) => ({ ...item })) : [],
    code_list_reference: codeListRef
      ? normalizeCodeListReference(codeListRef)
      : emptyCodeListReference(),
  };
}

/** @param {object} selectedField */
export function buildFieldUpsertPayload(selectedField, preserveFieldOrder = null) {
  const updateData = {
    name: selectedField.name,
    label:
      selectedField.label !== undefined && selectedField.label !== null
        ? selectedField.label
        : selectedField.name,
    data_type: selectedField.data_type || 'string',
    column_type: selectedField.column_type || null,
    description:
      selectedField.description !== undefined && selectedField.description !== null
        ? selectedField.description
        : '',
    time_period_format: selectedField.time_period_format || null,
    unit_of_measurement: selectedField.unit_of_measurement || null,
    format: selectedField.format || null,
    code_list: selectedField.code_list || [],
    code_list_reference:
      selectedField.code_list_reference &&
      (selectedField.code_list_reference.id ||
        selectedField.code_list_reference.name ||
        selectedField.code_list_reference.uri)
        ? selectedField.code_list_reference
        : null,
  };
  if (preserveFieldOrder !== null && preserveFieldOrder !== undefined) {
    updateData.field_order = preserveFieldOrder;
  }
  return updateData;
}

export function sortFields(fields, sortBy) {
  const list = [...fields];
  switch (sortBy) {
    case 'name_asc':
      list.sort((a, b) => (a.name || '').localeCompare(b.name || ''));
      break;
    case 'name_desc':
      list.sort((a, b) => (b.name || '').localeCompare(a.name || ''));
      break;
    case 'label_asc':
      list.sort((a, b) => {
        const labelA = a.label || a.name || '';
        const labelB = b.label || b.name || '';
        return labelA.localeCompare(labelB);
      });
      break;
    case 'label_desc':
      list.sort((a, b) => {
        const labelA = a.label || a.name || '';
        const labelB = b.label || b.name || '';
        return labelB.localeCompare(labelA);
      });
      break;
    case 'data_type':
      list.sort((a, b) => {
        const typeA = a.data_type || '';
        const typeB = b.data_type || '';
        return typeA.localeCompare(typeB) || (a.name || '').localeCompare(b.name || '');
      });
      break;
    case 'order':
    default:
      list.sort((a, b) => (a.field_order || 0) - (b.field_order || 0));
      break;
  }
  return list;
}

export function formatNumber(num) {
  return new Intl.NumberFormat().format(num);
}

export function formatDate(dateString) {
  if (!dateString) return 'N/A';
  try {
    const date = new Date(dateString);
    if (Number.isNaN(date.getTime())) return dateString;
    return (
      date.toLocaleDateString() +
      ' ' +
      date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    );
  } catch {
    return dateString;
  }
}
