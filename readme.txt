=== Form Annotator for WebMCP ===
Contributors: itamarsilvacc
Tags: forms, contact-form, ai, chrome, webmcp
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Annotate WordPress forms with declarative WebMCP attributes so browser AI agents can fill lead and support forms reliably.

== Description ==

**Form Annotator for WebMCP** is a WordPress plugin that adds [declarative WebMCP](https://developer.chrome.com/docs/ai/webmcp) attributes (`toolname`, `tooldescription`, `toolparamdescription`) to existing contact and lead forms. In-browser AI agents (Chrome with WebMCP) can then discover those forms on the page and fill them without guessing the DOM.

It is for site owners who already use a form plugin and want agents to fill lead, contact, or support requests. It does not replace your form plugin, and it does not submit the form for the visitor.

= How it works =

1. Create a form in a supported builder (Contact Form 7, Fluent Forms, WPForms, Forminator, Ninja Forms, or SureForms).
2. Open **Settings → Form Annotator**, enable the form (one by one or in bulk), then **Annotate** to set the tool name, description, and field text.
3. On the front end the plugin injects WebMCP attributes into the real `<form>` markup. Lead and support forms never use `toolautosubmit` — a human confirms send.

= What this plugin does =

* Opt-in annotation per form (nothing is annotated until you enable it).
* A settings list with search, builder/status filters, pagination, and bulk enable/disable.
* A single-form editor for tool name, tool description, and per-field `toolparamdescription`.
* Optional Chrome Origin Trial token (printed in `wp_head` when set).
* Soft dependencies: each adapter loads only when that form plugin is active.

= Supported form plugins =

Contact Form 7, Fluent Forms, WPForms, Forminator, Ninja Forms, and SureForms. You must install a builder and create a form; this plugin does not ship its own contact form.

= What this plugin does not do =

* It is not a REST “WebMCP Bridge” for posts, menus, or WooCommerce cart tools.
* It does not generate `llms.txt` and it is not an SEO or GEO plugin.
* It is not an MCP server for IDE clients (Cursor, Claude Desktop, and similar).
* It does not auto-submit lead, contact, or support forms.

= Requirements =

* WordPress 6.4 or later and PHP 8.0 or later
* At least one supported form plugin with a published form
* To test tools today: Chrome with `chrome://flags/#enable-webmcp-testing` or a valid Origin Trial token, plus a WebMCP inspector extension

== Installation ==

1. Upload the `silvaitamar-form-annotator-for-webmcp` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** screen.
3. Install one of the supported form plugins and create a form, if you do not already have one.
4. Open **Settings → Form Annotator**, find the form (search or filter by builder), enable it, then **Annotate** to set the tool name, description, and field text.

== Frequently Asked Questions ==

= What is WebMCP on a WordPress site? =

[WebMCP](https://developer.chrome.com/docs/ai/webmcp) is a browser API: the page declares tools in HTML. This plugin adds those attributes to WordPress forms so an in-browser agent can fill a contact or lead form from the current page.

= Is WebMCP the same as MCP for Cursor or Claude? =

No. WebMCP runs in the browser, on the page. MCP servers for IDEs (Cursor, Claude Desktop) are a different protocol. This plugin is not an MCP server and does not need an API key.

= Can a browser AI agent fill my WordPress contact form? =

Yes, after you enable that form in **Settings → Form Annotator** and annotate it. The agent sees `toolname`, `tooldescription`, and `toolparamdescription` on the real form. The visitor still confirms submit.

= Does this plugin submit the form automatically? =

No. Lead, contact, and support forms never get `toolautosubmit`. The human confirms the send.

= Do I need ChatGPT, an MCP server, or a REST API? =

No. Annotation is HTML on the form. Testing today uses Chrome with the WebMCP flag (or an Origin Trial token) and an inspector extension.

= Does this replace Contact Form 7, Fluent Forms, or WPForms? =

No. Those plugins still create and process the form. This plugin only adds WebMCP attributes when you enable a form. If a builder is not active, its adapter does not load.

= Is this an SEO plugin? Does it generate llms.txt? =

No. It does not write `llms.txt`, sitemaps, or schema for search engines. It annotates forms for in-browser agents. The Lighthouse Agentic Browsing audit `webmcp-form-coverage` looks for these attributes on the real `<form>`; the plugin does not guarantee a score.

= How do I test annotations in the browser? =

Use Chrome with `chrome://flags/#enable-webmcp-testing` (or an Origin Trial token under **Settings → Form Annotator → Origin Trial**) and a WebMCP inspector extension. Enable a form, view it on the front end, and confirm `toolname`, `tooldescription`, and `toolparamdescription` on the markup.

= How do I translate this plugin? =

Translations are managed on [translate.wordpress.org](https://translate.wordpress.org/) after the plugin is listed. Language packs install automatically — do not bundle `.mo` files for locales that already have a pack.

== Privacy ==

This plugin stores annotation settings and an optional Origin Trial token in the WordPress database (`siwmfa_forms`, `siwmfa_settings`). It does not send data to remote services. When a token is saved, it is printed as a meta tag on the front end.

== Changelog ==

= 1.0.1 =
* Display name only: Form Annotator for WebMCP (slug and text domain unchanged).

= 1.0.0 =
* First public release: opt-in WebMCP annotation for Contact Form 7, Fluent Forms, WPForms, Forminator, Ninja Forms, and SureForms.
* Settings list with search, filters, pagination, bulk enable/disable, and a single-form editor.
* Optional Chrome Origin Trial token.
* Lead and support forms never auto-submit.
* Tested up to WordPress 7.1.

== Upgrade Notice ==

= 1.0.1 =
Display name update only. No settings or database changes.

= 1.0.0 =
First public release. Annotate existing form-plugin markup with declarative WebMCP attributes.
