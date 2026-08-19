# Compatibility notes (v1 builders)

Generic notes for WordPress.org / GitHub. No staging URLs or client names.

| Builder | Soft-dep check | Markup | Caveat |
|---------|----------------|--------|--------|
| Contact Form 7 | `WPCF7_ContactForm` | SSR filters | Hidden/honeypot tags skipped |
| Fluent Forms | `FLUENTFORM` / `wpFluent()` | SSR field filters | Skip captcha/html/hidden elements |
| WPForms | `wpforms()` | `wpforms_frontend_form_atts` + `wpforms_field_properties` | Form JSON may omit `id`; resolve from `data-formid`. Select/radio/checkbox: annotate `input_container`. Adapters boot on `plugins_loaded` (WPForms loads after this plugin alphabetically). Studio + `open_basedir`: WPForms Lite registers “Upgrade to Pro” as submenu slug `https://wpforms.com/lite-upgrade/`; core `menu-header.php` then `file_exists( WP_PLUGIN_DIR . '/' . $url )` and PHP warns. Not this plugin — ignore or hide `WP_DEBUG_DISPLAY` in local. |
| Forminator | `Forminator_API` | `forminator_render_form_markup` | Param keys = element ids (`email-1`, …) |
| Ninja Forms | `Ninja_Forms` | Post-JS `nfFormReady` | `<form>` is Backbone. Listselect/textarea templates honor `custom_name_attribute`; default names are `nf-field-{id}` |
| SureForms | `SRFM_VER` / CPT | `srfm/form` block + shortcode + `render_block` | The `<form>` is printed in PHP (`get_form_markup`); field attrs come from inner blocks. Dropdowns: strip Tom Select hidden `name` / `aria-hidden` so the native `<select>` is the WebMCP param |

This plugin does **not** ship a contact form. Native HTML/shortcode forms belong in the lab (`wp-webmcp-forms`), not the wp.org product.

WordPress: 6.4 through **7.1**. PHP 8.0+.

Lead and support tools never auto-submit. Search/`toolautosubmit` is out of v1 (planned v1.1).
