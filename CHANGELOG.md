# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-08-11

### Added

- Opt-in declarative WebMCP annotation for Contact Form 7, Fluent Forms, WPForms, Forminator, Ninja Forms, and SureForms.
- Settings list with search, builder/status filters, pagination, bulk enable/disable, and a single-form editor.
- Per-form tool name, description, and field `toolparamdescription`.
- Optional Chrome Origin Trial token in `wp_head`.
- Execute Tool fixture matrix under `docs/fixtures/`.

### Changed

- Display name, slug, and text domain are now SilvaItamar Form Annotator for WebMCP (`silvaitamar-form-annotator-for-webmcp`), after WordPress.org trademark feedback. Internal prefix `siwmfa` is unchanged. GitHub repository: `silvaitamar/wp-form-annotator-for-webmcp`.

### Notes

- Lead and support forms never use `toolautosubmit`.
- This plugin does not ship a contact form (lab-only in `wp-webmcp-forms`).

[1.0.0]: https://github.com/silvaitamar/wp-form-annotator-for-webmcp/releases/tag/v1.0.0
