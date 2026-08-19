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
