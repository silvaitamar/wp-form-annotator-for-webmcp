=== SilvaItamar WebMCP Form Annotator ===
Contributors: itamarsilvacc
Tags: forms, contact-form, ai, chrome, webmcp
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Annotate WordPress forms with declarative WebMCP attributes so browser AI agents can fill lead and support forms reliably.

== Description ==

**SilvaItamar WebMCP Form Annotator** injects declarative [WebMCP](https://developer.chrome.com/docs/ai/webmcp) attributes (`toolname`, `tooldescription`, `toolparamdescription`) into real form markup so in-browser AI agents can discover and fill conversion and support forms without guessing the DOM.

= What this plugin does =

* Opt-in annotation per form.
* A settings list under **Settings → WebMCP Forms** with search, builder/status filters, pagination, and bulk enable/disable.
* A single-form editor (**Annotate**) for the tool name, description, and field text.
* No `toolautosubmit` on lead, contact, or support forms — a human confirms submit.
* Optional Chrome Origin Trial token (printed in `wp_head` when set).
* Soft dependencies: each adapter loads only when that form plugin is active.

= Supported form plugins =

Contact Form 7, Fluent Forms, WPForms, Forminator, Ninja Forms, and SureForms. You must install a builder and create a form; this plugin does not ship its own contact form.

= What this plugin does not do =

* It is not a REST “WebMCP Bridge” for posts, menus, or WooCommerce cart tools.
* It does not generate `llms.txt` or replace SEO/GEO plugins.
* It is not an MCP server for IDE clients (Cursor, Claude Desktop, and similar).

= Requirements =

* WordPress 6.4 or later
* PHP 8.0 or later
* At least one supported form plugin with a published form
* To test tools today: Chrome with the WebMCP flag (`chrome://flags/#enable-webmcp-testing`) or a valid Origin Trial token, plus a WebMCP inspector extension

== Installation ==

1. Upload the `silvaitamar-webmcp-form-annotator` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** screen.
3. Install one of the supported form plugins and create a form, if you do not already have one.
4. Open **Settings → WebMCP Forms**, find the form (search or filter by builder), enable it, then **Annotate** to set the tool name, description, and field text.

== Frequently Asked Questions ==

= Is WebMCP the same as MCP for Cursor or Claude? =

No. WebMCP is a browser API for tools on the page. MCP servers for IDEs are a different protocol.

= Which form plugins are supported? =

Contact Form 7, Fluent Forms, WPForms, Forminator, Ninja Forms, and SureForms. If a builder is not active, its adapter does not load.

= How do I test annotations in the browser? =

Use Chrome with `chrome://flags/#enable-webmcp-testing` (or an Origin Trial token under **Settings → WebMCP Forms → Origin Trial**) and a WebMCP inspector extension. Enable a form, view it on the front end, and confirm `toolname`, `tooldescription`, and `toolparamdescription` on the markup.

= Will this pass Lighthouse Agentic Browsing form coverage? =

Declarative attributes on the real `<form>` are what the `webmcp-form-coverage` audit looks for. Enable a form in **Settings → WebMCP Forms**. This plugin does not guarantee a specific Lighthouse score.

= How do I translate this plugin? =

Translations are managed on [translate.wordpress.org](https://translate.wordpress.org/) after the plugin is listed. Language packs install automatically — do not bundle `.mo` files for locales that already have a pack.

== Privacy ==

This plugin stores annotation settings and an optional Origin Trial token in the WordPress database (`siwmfa_forms`, `siwmfa_settings`). It does not send data to remote services. When a token is saved, it is printed as a meta tag on the front end.

== Changelog ==

= 1.0.0 =
* First public release: opt-in WebMCP annotation for Contact Form 7, Fluent Forms, WPForms, Forminator, Ninja Forms, and SureForms.
* Settings list with search, filters, pagination, bulk enable/disable, and a single-form editor.
* Optional Chrome Origin Trial token.
* Lead and support forms never auto-submit.

== Upgrade Notice ==

= 1.0.0 =
First public release. Annotate existing form-plugin markup with declarative WebMCP attributes.
