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

## Page cache (LiteSpeed and similar)

Annotations are injected when the form builder **renders** HTML in PHP (filters such as `fluentform/html_attributes`). They are **not** patched into HTML that a full-page cache already stored.

| Symptom | Likely cause |
|---------|----------------|
| Admin shows the form enabled, front end has no `toolname` | Guest page cache from before enable/annotate |
| “Purge” in the cache UI seems to do nothing | Incomplete purge (CDN/host layer, language variants, logged-in vs guest) |
| Disabling the cache plugin “fixes” it; re-enabling keeps attrs | First uncached PHP hit rebuilt the page; new cache snapshot includes attrs |

**After enable / annotate / bulk toggle**, this plugin fires `litespeed_purge_all` and `siwmfa_purge_caches` (plus a few other common page-cache helpers when present). If a host CDN sits in front of LSCWP, purge that layer too or exclude the contact page from cache while validating.

Object cache alone rarely explains missing attrs when options already store the enabled config — the usual culprit is **HTML page cache**.

## PageSpeed Insights vs local WebMCP checks

Declarative attrs (`toolname`, `tooldescription`, `toolparamdescription`) can be present in the HTML while **PageSpeed Insights** does not list WebMCP tools under Agentic Browsing.

Official Lighthouse docs ([Agentic browsing scoring](https://developer.chrome.com/docs/lighthouse/agentic-browsing/scoring)):

- The Agentic Browsing category and WebMCP support are **experimental**.
- Testing the category requires **Chrome 150+**.
- **WebMCP audits require registering for the WebMCP origin trial** (or equivalent local enablement such as `chrome://flags/#enable-webmcp-testing`).
- Lighthouse discovers tools by calling the CDP **`WebMCP` domain** — if that domain is unavailable in the runner, the WebMCP audits have nothing to observe and appear as **Not Applicable** (common in the PSI lab).

What still runs in PSI without WebMCP: the non-WebMCP agentic checks (accessibility subset, CLS, `llms.txt`, etc.) — hence a fractional score like **3/3** with **3 N/A** for the WebMCP trio (`webmcp-registered-tools`, `webmcp-form-coverage`, `webmcp-schema-validity`).

**Canonical verification:** Chrome local (flag or OT token from **Settings → Form Annotator → Origin Trial**) → DevTools **Lighthouse → Agentic Browsing**, and/or the [WebMCP panel](https://developer.chrome.com/docs/devtools/application/webmcp) / Tool Inspector. See also [Registered WebMCP tools](https://developer.chrome.com/docs/lighthouse/agentic-browsing/registered-webmcp-tools).

Confirm markup with View Source / `toolname=` on the real `<form>` before chasing PSI.
