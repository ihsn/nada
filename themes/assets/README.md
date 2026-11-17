# Shared theme assets

Framework CSS and JS used by public themes. Themes keep only their own skin
(PHP templates, `css/style.css`, `custom.css`, images).

| Path | Contents |
|------|----------|
| `bootstrap/4.1.3/` | Bootstrap 4.1.3 CSS and JS |
| `popper/1.14.3/` | Popper (Bootstrap 4 tooltips/dropdowns) |
| `fontawesome/5.15.2/` | Font Awesome 5 (css + webfonts) — `nada52` |
| `fontawesome/4.7.0/` | Font Awesome 4 (css + fonts) — legacy admin |

Heads load these via `base_url('themes/assets/…')` when CDN is off. Do not copy
these files into individual theme folders.
