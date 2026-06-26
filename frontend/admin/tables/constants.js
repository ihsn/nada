export const DATA_TYPES = [
  { title: 'String', value: 'string' },
  { title: 'Integer', value: 'integer' },
  { title: 'Float', value: 'float' },
  { title: 'Double', value: 'double' },
  { title: 'Boolean', value: 'boolean' },
  { title: 'Date', value: 'date' },
  { title: 'DateTime', value: 'datetime' },
  { title: 'Array', value: 'array' },
  { title: 'Object', value: 'object' },
  { title: 'Null', value: 'null' },
];

export const COLUMN_TYPES = [
  { title: '-', value: '' },
  { title: 'Dimension', value: 'dimension' },
  { title: 'Time Period', value: 'time_period' },
  { title: 'Measure', value: 'measure' },
  { title: 'Attribute', value: 'attribute' },
  { title: 'Indicator ID', value: 'indicator_id' },
  { title: 'Indicator Name', value: 'indicator_name' },
  { title: 'Geography', value: 'geography' },
  { title: 'Observation Value', value: 'observation_value' },
  { title: 'Periodicity', value: 'periodicity' },
];

export const FIELD_SORT_OPTIONS = [
  { title: 'Order', value: 'order' },
  { title: 'Name (A-Z)', value: 'name_asc' },
  { title: 'Name (Z-A)', value: 'name_desc' },
  { title: 'Label (A-Z)', value: 'label_asc' },
  { title: 'Label (Z-A)', value: 'label_desc' },
  { title: 'Data Type', value: 'data_type' },
];

export const COLUMN_TYPE_COLORS = {
  dimension: 'primary',
  time_period: 'info',
  measure: 'success',
  attribute: 'warning',
  indicator_id: 'purple',
  indicator_name: 'purple',
  geography: 'teal',
  observation_value: 'orange',
  periodicity: 'cyan',
};
