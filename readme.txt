=== SilvaItamar WebMCP Form Annotator ===
Contributors: itamarsilvacc
Tags: webmcp, ai agents, forms, agentic, lighthouse
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.3.0-dev
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Annotate WordPress forms with declarative WebMCP attributes so browser AI agents can fill lead and support forms reliably.

== Description ==

**SilvaItamar WebMCP Form Annotator** injects declarative [WebMCP](https://developer.chrome.com/docs/ai/webmcp) attributes (`toolname`, `tooldescription`, `toolparamdescription`) into real form markup so in-browser AI agents can discover and fill conversion and support forms without guessing the DOM.

= What this plugin does =

* Opt-in annotation per form (Contact Form 7, Fluent Forms, WPForms, Forminator, Ninja Forms, SureForms).
* No `toolautosubmit` on lead/contact/support forms — a human confirms submit.
* Optional Chrome Origin Trial token in Settings.
* Soft dependencies: adapters load only when the related form plugin is active.

= What this plugin does not do =

* It is not a REST “WebMCP Bridge” for posts, menus, or WooCommerce cart tools.
* It does not generate `llms.txt` or replace SEO/GEO plugins.
* It is not an MCP server for IDE clients (Cursor/Claude Desktop).
* It does not ship its own contact form — it only annotates forms from supported builders.

= Requirements =

* WordPress 6.4 or later
* PHP 8.0 or later
* To test tools today: Chrome with WebMCP flag or a valid Origin Trial token, plus the Model Context Tool Inspector extension

== Installation ==

1. Upload the `silvaitamar-webmcp-form-annotator` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** screen.
3. Open **Settings → WebMCP Forms**, enable a form, and set the tool name/description.

== Frequently Asked Questions ==

= Is WebMCP the same as MCP for Cursor/Claude? =

No. WebMCP is a browser API for tools on the page. MCP servers for IDEs are a different protocol.

= Will this pass Lighthouse Agentic Browsing form coverage? =

Declarative attributes on the real `<form>` are what the `webmcp-form-coverage` audit looks for. Enable a form in **Settings → WebMCP Forms**.

= Can I remove “SilvaItamar” from the plugin name later? =

Yes. After WordPress.org approval, a display-name-only update can rename it to **WebMCP Form Annotator** without changing the slug or internal prefix (same approach as our other directory plugins).

== Changelog ==

= 0.3.0-dev =
* Adapters for WPForms, Forminator, Ninja Forms, and SureForms.
* Execute Tool fixture matrix for each builder.

= 0.2.0-dev =
* Opt-in declarative annotation for Contact Form 7 and Fluent Forms.
* Per-form tool name, description, and field `toolparamdescription`.
* Origin Trial token output in `wp_head`.

= 0.1.0-dev =
* Scaffold: bootstrap, settings page, PHPCS/WPCS prefix `siwmfa`, packaging stubs.

== Upgrade Notice ==

= 0.3.0-dev =
Development build: remaining v1 form builders. Not a WordPress.org release yet.

= 0.2.0-dev =
Development build: CF7 and Fluent Forms adapters. Not a WordPress.org release yet.
