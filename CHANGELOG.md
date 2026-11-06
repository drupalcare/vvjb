# Changelog

## [Unreleased]

### Added

- Patterns: the Solo and APEX bridges map nothing; the controls sit on the dark stage and keep the stage tokens.
- Patterns: every hard-coded colour is now a vvjb token, and Solo and APEX get a theme bridge (`pattern-bridge-solo`, `pattern-bridge-apex`) that maps the vvjb controls to the theme colours; the black-and-white look is unchanged.

### Fixed

- Patterns: the hybrid vertical switch and the small-screen item count now follow the Pattern's own width (a ResizeObserver on the element) instead of the window; the script marks the switch with `vvjb-stacked` and the Pattern CSS stacks on that class instead of viewport media queries. Classic carousels still use the window width.
- Patterns: the viewport breakpoint files (`vvjb-NNN.css`) no longer lay out Pattern markup; a hybrid Pattern (Switchboard) stacks its slides in one column under its own CSS below the chosen breakpoint. Classic carousels are unchanged.
- Accessibility: the current slide no longer makes its non-control elements (`tabindex="-1"`, links without `href`) into Tab stops; only links, buttons and fields follow the slide.
- Accessibility: the Swatch and Ledger Patterns now show the summary and link (24 px tall at least) instead of hiding them.
- Accessibility: carousel keys (Space, arrows, Home, End) act only when the carousel itself has focus, so a focused button keeps Space; dots are always buttons; control and dot groups carry `role="group"`; the progress bar is hidden from screen readers; timer steps no longer fill the live regions.

- Patterns: prev and next arrows click again (the Pattern reset had made them inherit `pointer-events: none` from the arrow bar); Switchboard card edge no longer clipped on the right.
- `_setWrapperSize()` lists the horizontal branch first so its layout reads come before any style write (JS-RULES.md rule 3); behavior unchanged.
- Hover rules that must beat a rest rule are flattened with `:where()` so no new Pattern selector exceeds 0,2,0; the look is unchanged (check-vvj-cascade: winners changed 0).
- **The arrows no longer disappear at the ends.** On a carousel that does not
  loop, the previous arrow on the first page and the next arrow on the last
  page were hidden, which left a gap and shifted the controls beside them.
  They now stay in place, dimmed and marked `aria-disabled="true"`, and a
  click on them does nothing. Keyboard focus stays on an arrow that becomes
  disabled. The `vvjb-hidden` class is replaced by `vvjb-disabled`; a theme
  that styled `vvjb-hidden` should style `vvjb-disabled` instead. Looping
  carousels are unchanged.
- **Pages no longer drift when the theme adds a margin to the items.** Page
  positions were worked out from each item's width plus the gap setting, so
  any margin a theme put on `.vvjb-item` was missed and every page landed a
  little further off, until the last items could not be reached. The carousel
  now measures where the browser actually placed each item, so margins, the
  gap and any other spacing are all counted. Carousels without such a margin
  page exactly as before.
- **A centered carousel no longer looks off center.** When the visible items
  are narrower than the carousel (a fixed Item width with few items per page),
  the group is centered, but the next item showed faded in the empty gutter
  beside it and pulled the group visually to one side. The window is now
  clipped to the visible items, leaving 4px so an edge item's focus ring
  stays whole. Carousels whose items fill the width look exactly as before.

### Changed

- Code style: JavaScript and CSS follow Drupal core's ESLint, Stylelint and Prettier rules, and the spell-check job passes with a project word file. Rendering and behavior are unchanged.
- Layout: Pattern layout now follows the width the Pattern itself gets (container queries on `vvj-region`) instead of the screen width, so Patterns lay out correctly in a narrow content column; overlays and the Pattern root keep screen-width queries.

## [2.0.0] (UNRELEASED)

**Drop-in upgrade for 1.x sites.** v2 modernizes internals: extends `vvj_core`
foundation, ships `<vvjb-carousel>` custom element extending `VvjElementBase`,
AbortController-tracked listeners, View Transitions wrapping slide swaps, while
preserving every option key, library name, CSS class, plugin ID, theme hook, JS
behavior key, and public-API method from 1.x.

### Added

- **Patterns**: 5 ready-made designs for this format (Dockrail, Ledger, Switchboard, Swatch, Nightstory). Pick one in the "Patterns" section of the format settings and its layout, animation, styling and deep linking settings are applied to the view. Needs the `vvj_patterns` module from `vvj_core`; the optional `vvj_patterns_demo` module adds example views.
- **Dot navigation style** select (Circle / Bar / Square) in Navigation
  Settings. Prints the matching wrapper class (`bar-dots` / `square-dots`;
  Circle is the default and adds no class) that v1 required site builders to
  type into the view's CSS-class field by hand. No CSS added, it reuses the
  existing dot-style rules and outputs the class in the same spot (the views
  wrapper). Shown only when dots are enabled.

### Fixed

- Update 10002 converts v1 views to the v2 option shape (value types:
  strings and integers stored where v2 declares integer, boolean and float).
- Restored v1's support for **any** Views row plugin (Fields *or* Content/entity
  rows). The shared base defaulted to requiring Fields: force-switching the row
  plugin on edit and blocking validation, which broke non-Fields carousels
  including the shipped `vvjb_example` (`entity:node` teaser rows). vvjb now
  opts out (`requiresFieldsRow()` → FALSE), matching v1.
- Removed the phantom "Active on all screens" (`all`) option from the responsive
  breakpoint select. A PHP array-union (`+`) bug leaked the base registry's
  options; v1 offered exactly 576/768/992/1200/1400.
- Deep-link "URL Identifier" field show/hide + required toggle now works (fixed
  in the `vvj_core` base. The toggle attributes are slug-derived).

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
