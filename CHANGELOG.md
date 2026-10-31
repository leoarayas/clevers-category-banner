# Changelog

All notable changes to this project will be documented in this file.

## [1.3.0] - 2026-10-30

### Added
- Universal theme support: dynamic hook registration falling back to `woocommerce_before_main_content` on non-Astra themes.
- Filter `clevers_catban_render_hook` and `clevers_catban_render_priority` for full customization by developers.
- Shortcode `[clevers_category_banner]` supporting `id`, `slug`, `show_title`, `show_desc` and `full_width`.
- Contained styling class `.clevers-catban--contained` for contained layouts.
- GitHub Actions CI workflow for multi-PHP syntax checking.

### Changed
- Preserved 100% backward compatibility with Astra theme hook (`astra_content_before` priority 1).
- Improved transient cache key generation for parameterized shortcode views.

## [1.2.0] - 2026-06-26

- Initial stable version with Astra theme support, transient caching, and responsive srcset markup.
