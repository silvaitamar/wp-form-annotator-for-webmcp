# WordPress.org plugin assets

These files go in SVN `/assets/` after approval. They are **not** in the plugin ZIP (see `.distignore`).

| File | Size / role |
|------|-------------|
| `icon-128x128.png` | 128×128 |
| `icon-256x256.png` | 256×256 |
| `banner-772x250.png` | 772×250 |
| `banner-1544x500.png` | 1544×500 |
| `blueprints/blueprint.json` | Live Preview (Fluent Forms lite) |

No plugin display name is drawn on the graphics so a post-approval rename to **WebMCP Form Annotator** does not require new art.

Regenerate icons/banners:

```bash
php scripts/generate-wporg-assets.php
# fallback if PHP GD is missing:
python3 scripts/generate-wporg-assets.py
```

Rebuild the blueprint after editing `scripts/playground-seed.php`:

```bash
php scripts/build-wporg-blueprint.php
```

## Live Preview blueprint

Canonical Git path: `.wordpress-org/blueprints/blueprint.json`  
Canonical SVN path (after the plugin is approved): `assets/blueprints/blueprint.json`

Docs: [Previews and Blueprints](https://developer.wordpress.org/plugins/wordpress-org/previews-and-blueprints/).

### What the blueprint does

1. Logs the visitor in as `admin` / `password` (same pattern as Duplicate Post Exclusion).
2. Installs **Fluent Forms** from wordpress.org (the only extra dependency).
3. Seeds one Contact form + a `/contact/` page already annotated (`submit_contact`, no `toolautosubmit`).
4. Sets that page as the site front (`landingPage`: `/`).

This plugin is **not** installed by the blueprint. The WordPress.org Preview button injects `installPlugin` for `silvaitamar-webmcp-form-annotator` when the blueprint runs from the directory. Until the plugin is listed, that slug 404s — do not add a self-install step.

### When the Preview button appears

| Stage | What you see |
|-------|----------------|
| Plugin not yet approved | No directory page. Test locally (below). |
| Approved + `assets/blueprints/blueprint.json` in SVN | **Test Preview** for committers only |
| Committer sets preview to **public** on Advanced | **Preview** for everyone |

After the first SVN assets commit, open the plugin’s Advanced tab and switch preview from private/test to public (same toggle used on Duplicate Post Exclusion).

### Local test (before wp.org listing)

From the plugin root (needs network to fetch Fluent Forms):

```bash
npx --yes @wp-playground/cli@latest server --auto-mount --blueprint=.wordpress-org/blueprints/blueprint.json
```

`--auto-mount` loads **this** working copy; the blueprint installs Fluent Forms and runs the seed. Open `/` (annotated form) and **Settings → WebMCP Forms**.

JSON sanity:

```bash
python3 -c "import json; json.load(open('.wordpress-org/blueprints/blueprint.json', encoding='utf-8')); print('ok')"
```
