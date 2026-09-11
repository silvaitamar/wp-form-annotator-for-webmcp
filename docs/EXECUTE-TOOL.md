# Execute Tool matrix (v1 builders)

Fixtures in `docs/fixtures/` are payloads for the Chrome **Model Context Tool Inspector** → Execute Tool. They fill fields only; **do not** include a submit action (`toolautosubmit` is never set on lead/support forms).

Keys must match the HTML `name` of each control (the same keys shown in **Settings → Form Annotator**). IDs below are examples from a clean Studio install — copy names from the rendered page if they differ.

| Builder | Tool (example) | Fixture | Markup path |
|---------|----------------|---------|-------------|
| Contact Form 7 | `submit_contact` | `execute-tool-cf7.json` | `wpcf7_form_*` SSR |
| Fluent Forms | `submit_contact` | `execute-tool-fluent.json` | `fluentform/*` SSR |
| WPForms | `submit_contact` | `execute-tool-wpforms.json` | `wpforms[fields][{id}]` SSR |
| Forminator | `submit_contact` | `execute-tool-forminator.json` | `forminator_render_form_markup` SSR |
| Ninja Forms | `submit_contact` | `execute-tool-ninja.json` | Post-JS `nfFormReady` |
| SureForms | `submit_contact` | `execute-tool-sureforms.json` | `srfm/form` block / shortcode SSR |
| Jetpack Forms | `submit_contact` | `execute-tool-jetpack.json` | Synced `jetpack_form` + `ref`; SSR filters |
| Site search | `search_site` | `execute-tool-search.json` | `get_search_form` / `core/search` (`toolautosubmit` OK) |
| Filter Everything | `filter_everything` | `execute-tool-filter-everything.json` | GET forms inside set / `[fe_widget]` (`toolautosubmit` OK) |
| Search & Filter | `search_and_filter` | `execute-tool-search-filter.json` | `[searchandfilter]` (`toolautosubmit` OK; one call with all params) |

## How to run

1. Enable the form in **Settings → Form Annotator** and fill `toolparamdescription` for each field.
2. Open the page that renders the form in Chrome with WebMCP (flag or Origin Trial).
3. In Tool Inspector, select the tool and paste the matching fixture JSON.
4. Confirm fields filled; submit stays with the human.

## Builder notes

- **WPForms:** select/radio/checkbox get `toolparamdescription` on the input container (no `inputs.primary`).
- **Ninja:** `<form>` is Backbone; annotation is JS. Listselect/textarea templates honor `custom_name_attribute`. Default names are `nf-field-{id}`.
- **SureForms:** the wrapping `<form>` comes from the `srfm/form` embed (or `[sureforms]`), not from field blocks. Dropdowns use Tom Select + a hidden input. The adapter strips `aria-hidden` and hidden `name` so the native `<select>` is what WebMCP sees. Copy real `name` values from the page into the fixture.
- **Forminator:** param keys are element ids (`email-1`, `textarea-1`, …). Compound name fields may use `name-1-first-name`.
- **Jetpack Forms:** only forms stored as CPT `jetpack_form` (dashboard Jetpack → Forms). Embed with `ref`. Set each field’s Name/ID in Advanced for stable `name` attributes. Never set `toolautosubmit` on these lead forms.
- **Site search:** does not ship a search UI — only annotates the theme/core form. Param key is usually `s`. Fixture may include submit; markup can carry `toolautosubmit="true"`.
- **Filter Everything:** free plugin is mostly link/AJAX facets; WebMCP annotates real GET forms (search field, range, date, sorting) inside a set. Prefer `[fe_widget id="SET_ID"]` in page content (lab: widget on the posts home). Param is usually `srch`. For `toolautosubmit`, keep the Filter Set **Apply button** off — with Apply mode FE calls `preventDefault` on submit (AJAX path) and WebMCP errors with missing `respondWith`. Native GET navigation works like Site search / Search & Filter. FacetWP/SearchWP/HUSKY are out of v1.1.
- **Search & Filter:** shortcode `[searchandfilter fields="search,category,post_tag"]`. Param keys use prefix `of` (`ofsearch`, `ofcategory`, `ofpost_tag`). Category/tag `<select>` values are **term IDs** (`"4"`, `"7"`), or `"0"` for All — never `""`. The portable fixture uses `"0"` so it works on any site; replace with real term IDs when testing a specific category/tag. With `toolautosubmit`, the page navigates and the tool often returns `null`; agents must treat that as success and stop. The default `tooldescription` states a hard one-call limit so chat prompts do not fan out into dozens of keyword retries.
