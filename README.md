# Views Vanilla JavaScript Basic Carousel (VVJB)

A vanilla-JavaScript carousel **Views format** for Drupal 11.3+ / 12. Renders
Views rows as a responsive, touch-friendly, autoplay-capable carousel —
horizontal, vertical, or hybrid orientations, with W3C ARIA-conformant keyboard
navigation, configurable item-per-screen counts at small/large breakpoints,
looping, deep-linking, scrollable-dot navigation, progress bar, page counter,
and play/pause control.

v2.0 builds on the **`drupal/vvj_core` foundation** (composer auto-installs it).
Behavior runs in a `<vvjb-carousel>` custom element extending `VvjElementBase`.

## Table of contents

- Overview
- Requirements
- Installation
- Configuration
- Usage
- Patterns
- Upgrading from 1.x
- Troubleshooting
- Changelog
- Maintainers
- License

## Overview

- Three orientations: horizontal / vertical / hybrid (vertical below breakpoint)
- Configurable items-per-screen on small + large viewports
- Configurable gap, optional fixed item width
- Autoplay with 0–15 s interval (0 disables); pause-on-hover; pause-when-out-of-
  viewport (IntersectionObserver); pause-when-tab-hidden (visibility API);
  pause-on-reduced-motion
- Looping (wrap to first when reaching end)
- Touch / swipe gestures (RTL-aware)
- Keyboard nav: ArrowLeft/Right (or Up/Down for vertical), Space (toggle
  play/pause), Home, End
- Arrow + dot navigation (or arrows-only / dots-only / none)
- Scrollable dots with overflow indicators (configurable max width)
- Play/pause button with SVG icon swap + ARIA label
- RAF-driven progress bar with ARIA `role="progressbar"`
- Page counter ("X of Y")
- Deep linking: shareable URL fragments per slide (`#carousel-products-3`)
- ARIA `aria-roledescription="carousel"`, `inert` on hidden slides, screen-
  reader announcer for slide changes
- Lazy IntersectionObserver hydration; AbortController-tracked listeners
- View Transitions wrapping slide swaps (graceful fallback)
- 5 responsive breakpoints (576 / 768 / 992 / 1200 / 1400 px)
- **Views tokens**: `[vvjb:field]` / `[vvjb:field:plain]` in
  header/footer/empty text when *Use replacement tokens from the first row* is
  enabled (same pattern as other VVJ modules)
- **Drupal admin help**: `/admin/help/vvjb`

## Requirements

- Drupal core `^11.3 || ^12`.
- PHP 8.3 or later.
- Drupal core modules Views and Filter.
- [VVJ Core](https://www.drupal.org/project/vvj_core) (`vvj_core`), installed automatically by Composer.

## Installation

1. Install with Composer: `composer require drupal/vvjb:^2.0` (it pulls `drupal/vvj_core`), or download the module and `vvj_core` from [Drupal.org](https://www.drupal.org/project/vvjb).
2. Enable it in the admin interface or with Drush: `drush en vvjb`.
3. Clear caches so the new plugin is available: `drush cr`.

## Configuration

Settings live in the format settings of the view. Configure orientation / items per screen / gap / autoplay / navigation / responsive in the format settings.

For a ready-made starting point, choose a Pattern (see Patterns below).

## Usage

1. Create or edit a View.
2. Set **Format** to *Views Vanilla JavaScript Basic Carousel*.
3. Set **Show** to *Fields*.
4. Configure the format settings (see Configuration).
5. Save.

**Sample view:** Optional config `views.view.vvjb_example` (`config/optional/`)
installs with the module when there is no ID conflict. Use it as a reference
implementation.

### Token support in Views text areas

In Views headers, footers, or empty text with *Use replacement tokens from the
first row*, default Twig tokens (`{{ title }}`) do not work. Use VVJB tokens
instead:

- `{{ title }}` → `[vvjb:title]`
- `{{ field_image }}` → `[vvjb:field_image]`
- Append `:plain` for plain text: `[vvjb:title:plain]`

v2 resolves tokens through the shared `vvj_core.token_resolver` service; syntax
is unchanged from v1. Tokens read from the **first row** of rendered View
fields. Complex field rewrites are not supported.

### JavaScript API

External code can drive the carousel via `Drupal.vvjb.*`. Pass either the
deeplink identifier, a CSS selector, or an `Element` reference:

```js
Drupal.vvjb.goToSlide('products', 3);          // jump to page 3
Drupal.vvjb.nextSlide('products');             // advance one page
Drupal.vvjb.prevSlide('products');             // back one page
Drupal.vvjb.pause('products');                 // pause autoplay
Drupal.vvjb.resume('products');                // resume autoplay
Drupal.vvjb.getCurrentSlide('products');       // → number (1-indexed)
Drupal.vvjb.getTotalSlides('products');        // → number
Drupal.vvjb.getInstance('#vvjb-12345');        // → <vvjb-carousel> element
```

## Patterns

A Pattern is a ready-made design for this format. One choice in the format settings sets the layout, animation,
styling and deep linking together. Views Vanilla JavaScript Basic Carousel ships 5 Patterns:

| Pattern | What it looks like | Demo page path |
|---|---|---|
| Dockrail | Three portrait cards with editorial captions and a glass dock at the bottom: arrows, page dots, counter and pause. | `vvj/vvjb/dockrail` |
| Ledger | A vertical list of numbered rows with a thumbnail on the right and a control rail on the left. | `vvj/vvjb/ledger` |
| Switchboard | Three cards across, list rows below 992 pixels, with a numbered segmented control between chevron buttons; no autoplay. | `vvj/vvjb/switchboard` |
| Swatch | Edge-to-edge square tiles that open as circles, big round magnetic arrows, dots and a ring progress. | `vvj/vvjb/swatch` |
| Nightstory | Two tall photo cards with text on a scrim, story bars over the photos and a headline that rises word by word. | `vvj/vvjb/nightstory` |

The demo pages exist only while the optional `vvj_patterns_demo` module is enabled (see "Example views" below).

### Choosing a Pattern

1. Enable the shared Patterns base, which ships inside `vvj_core`: `drush en vvj_patterns`. The "Pattern" select
   appears in the format settings only while `vvj_patterns` is enabled.
2. Edit the view and open the format settings.
3. In the "Patterns" section, pick a Pattern in the "Pattern" select and save.
4. The Pattern's settings replace the layout, animation, styling and deep linking settings of the view. You can
   change them afterwards. Pick "None" to keep your own settings.
5. A list titled "To finish the <name> Pattern" shows what the view must supply: which fields, which markup
   classes and how many items. Follow it; a Pattern shows its finished design only when the view matches.

### Example views

Enable the optional `vvj_patterns_demo` module (`drush en vvj_patterns_demo`) to see every Pattern with content.
It creates a "VVJ Slide" content type with five demo slides and pictures, plus example views titled
"VVJ demo: <format>" for the VVJ format modules that are enabled, one page per Pattern at the paths in the table
above. Uninstalling the demo module removes the slides and pictures it created.

## Upgrading from 1.x

`composer update drupal/vvjb && drush cache:rebuild` is sufficient. v2
preserves:

- Plugin ID `views_vvjb`, theme hook `views_view_vvjb`
- Twig template name
- All option keys (19 total)
- Library names (`vvjb`, `vvjb-admin`, `vvjb__576/768/992/1200/1400`)
- JS behavior key `Drupal.behaviors.vvjbCarousel`
- Public API surface
  `Drupal.vvjb.{goToSlide,nextSlide,prevSlide,pause,resume,getCurrentSlide,getTotalSlides,getInstance}`
- All CSS class names (`.vvjb`, `.vvjb-inner`, `.vvjb-items`, `.vvjb-item`,
  `.vvjb-button`, `.vvjb-prev`/`.vvjb-next`, `.vvjb-carousel-dot`, `.vvjb-
  progress-bar`, `.vvjb-play-pause-button`, `.vvjb-page-counter`, etc.)

Outer rendered tag changed from `<div>` to `<vvjb-carousel>`. Theme selectors
targeting `.vvjb` keep matching.

## Troubleshooting

| Symptom | Fix |
|---|---|
| The format is missing from the Views "Format" list | Enable `vvj_core` and `vvjb`, then `drush cr`. |
| `Class "Drupal\vvjb\..." not found` after deploying v2 code | Stale container cache: `drush cr`. If Drush will not bootstrap, clear `cache_container` in the database and retry. |
| `drush updb` errors before `vvj_core` is enabled | Run `drush en vvj_core -y`, then `drush updb -y && drush cr`. |
| No "Patterns" section in the format settings | Enable `vvj_patterns`: `drush en vvj_patterns`. |

Report bugs and request features in the [issue queue](https://www.drupal.org/project/issues/vvjb).

## Changelog

See [CHANGELOG.md](./CHANGELOG.md).

## Maintainers

- Alaa Haddad - [flashwebcenter](https://www.drupal.org/u/flashwebcenter)

## License

GPL-2.0-or-later. See the [GNU General Public License, version 2 or later](https://www.gnu.org/licenses/gpl-2.0.html).
