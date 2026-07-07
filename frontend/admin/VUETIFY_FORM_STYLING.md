# Vuetify Form Styling Guide (Admin)

Use this guide for new and updated admin forms to keep layout and interaction consistent.

## Core Standards

- Use separate label text above each field (do not rely on field `label` props).
- Use `variant="outlined"` and `density="compact"` for form controls.
- For non-editable fields, prefer `readonly` over `disabled` when possible.
- Reserve `disabled` for short-lived technical states (for example, while saving/loading).
- Keep helper text (`hint` + `persistent-hint`) only where it adds real value.

## Label Pattern (Required)

Use a standalone label element above the control:

```vue
<div class="text-caption text-medium-emphasis mb-1">Title</div>
<v-text-field
  v-model="form.title"
  density="compact"
  variant="outlined"
  hide-details
/>
```

Recommended label utility classes:

- `text-caption text-medium-emphasis mb-1`
- Add `mt-3` on later labels to create vertical rhythm.

## Field Defaults

For most admin forms:

- `density="compact"`
- `variant="outlined"`
- `hide-details` when there is no hint/validation message to show

Example:

```vue
<div class="text-caption text-medium-emphasis mt-3 mb-1">Status</div>
<v-select
  v-model="form.status"
  :items="statusOptions"
  item-title="title"
  item-value="value"
  density="compact"
  variant="outlined"
  hide-details
/>
```

## Read-Only and Locked States

Use a computed lock gate in parent forms:

```js
const isLocked = computed(() => {
  const status = Number(model.value?.status);
  return status === 20 || status === 40; // Published or Archived
});
```

Apply state rules consistently:

- `readonly` when record is locked (Published/Archived).
- `disabled` while save request is in-flight.
- Disable submit buttons when locked.
- Keep API-side validation/locking as the source of truth.

Example:

```vue
<v-text-field
  v-model="form.title"
  :readonly="isLocked"
  :disabled="saving"
  density="compact"
  variant="outlined"
  hide-details
/>
```

## Textareas and Autocomplete

Apply the same style standards:

```vue
<v-textarea
  v-model="form.notes"
  :readonly="isLocked"
  :disabled="saving"
  rows="2"
  auto-grow
  density="compact"
  variant="outlined"
/>
```

```vue
<v-autocomplete
  v-model="form.codelist_id"
  :items="pickerItems"
  item-title="title"
  item-value="id"
  density="compact"
  variant="outlined"
/>
```

## Do/Don't

- Do: keep labels outside fields and aligned with the same spacing pattern.
- Do: keep field styling uniform across pages (`outlined` + `compact`).
- Do: centralize lock logic in one computed flag and reuse it.
- Don't: mix `comfortable` and `compact` in the same form unless intentionally documented.
- Don't: duplicate attributes on the same component (for example, two `density` props).

## Checklist for New Forms

- Labels are separate elements above controls.
- Controls use `variant="outlined"` and `density="compact"`.
- Lock/read-only behavior is consistent across all editable controls.
- Save/create actions are blocked when locked.
- Lint passes with no warnings/errors for edited files.
