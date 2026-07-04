# Changelog

## [2.0.0] — UNRELEASED

**Drop-in upgrade for 1.x sites.** v2 modernizes internals — extends `vvj_core`
foundation, ships `<vvjb-carousel>` custom element extending `VvjElementBase`,
AbortController-tracked listeners, View Transitions wrapping slide swaps — while
preserving every option key, library name, CSS class, plugin ID, theme hook, JS
behavior key, and public-API method from 1.x.

### Added

- **Dot navigation style** select (Circle / Bar / Square) in Navigation
  Settings. Prints the matching wrapper class (`bar-dots` / `square-dots`;
  Circle is the default and adds no class) that v1 required site builders to
  type into the view's CSS-class field by hand — no CSS added, it reuses the
  existing dot-style rules and outputs the class in the same spot (the views
  wrapper). Shown only when dots are enabled.

### Fixed

- Restored v1's support for **any** Views row plugin (Fields *or* Content/entity
  rows). The shared base defaulted to requiring Fields — force-switching the row
  plugin on edit and blocking validation — which broke non-Fields carousels
  including the shipped `vvjb_example` (`entity:node` teaser rows). vvjb now
  opts out (`requiresFieldsRow()` → FALSE), matching v1.
- Removed the phantom "Active on all screens" (`all`) option from the responsive
  breakpoint select — a PHP array-union (`+`) bug leaked the base registry's
  options; v1 offered exactly 576/768/992/1200/1400.
- Deep-link "URL Identifier" field show/hide + required toggle now works (fixed
  in the `vvj_core` base — the toggle attributes are slug-derived).

### Added

- `drupal/vvj_core` dependency.
- `<vvjb-carousel>` custom element. Lazy IntersectionObserver hydration;
  AbortController-tracked listeners; `disconnectedCallback()` cancels timers +
  observers automatically.

### Changed

- Plugin extends `VvjStylePluginBase`. Standard form sections built via base-
  class hooks (`buildLayoutSection`, `buildBehaviorSection`,
  `buildControlsSection`, `buildNavigationSection`, `buildAccessibilitySection`,
  `buildResponsiveSection`); deeplink + token-doc + warning-message sections
  come from the base.
- `buildLibraryList()` overridden to add the breakpoint-specific responsive
  library (`vvjb__BP`).
- Token resolution delegates to `vvj_core.token_resolver`.
- Hooks moved to OOP `#[Hook]` classes.
- `Drupal.vvjb.{goToSlide,nextSlide,prevSlide,pause,resume,getCurrentSlide,getTotalSlides,getInstance}`
  now delegate to methods on the custom element instance.

### Drupal core / PHP

- Requires Drupal `^11.3 || ^12`, PHP `>=8.3`.
