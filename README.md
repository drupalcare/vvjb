# Views Vanilla JavaScript Basic Carousel (VVJB)

A vanilla-JavaScript carousel **Views format** for Drupal 11.3+ / 12. Renders
Views rows as a responsive, touch-friendly, autoplay-capable carousel —
horizontal, vertical, or hybrid orientations — with W3C ARIA-conformant keyboard
navigation, configurable item-per-screen counts at small/large breakpoints,
looping, deep-linking, scrollable-dot navigation, progress bar, page counter,
and play/pause control.

v2.0 builds on the **`drupal/vvj_core` foundation** (composer auto-installs it).
Behavior runs in a `<vvjb-carousel>` custom element extending `VvjElementBase`.

## Features

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
- Deep linking — shareable URL fragments per slide (`#carousel-products-3`)
- ARIA `aria-roledescription="carousel"`, `inert` on hidden slides, screen-
  reader announcer for slide changes
- Lazy IntersectionObserver hydration; AbortController-tracked listeners
- View Transitions wrapping slide swaps (graceful fallback)
- 5 responsive breakpoints (576 / 768 / 992 / 1200 / 1400 px)
- **Views tokens** — `[vvjb:field]` / `[vvjb:field:plain]` in
  header/footer/empty text when *Use replacement tokens from the first row* is
  enabled (same pattern as other VVJ modules)
- **Drupal admin help** — `/admin/help/vvjb`

## Token Support in Views Text Areas

In Views headers, footers, or empty text with *Use replacement tokens from the
first row*, default Twig tokens (`{{ title }}`) do not work. Use VVJB tokens
instead:

- `{{ title }}` → `[vvjb:title]`
- `{{ field_image }}` → `[vvjb:field_image]`
- Append `:plain` for plain text: `[vvjb:title:plain]`

v2 resolves tokens through the shared `vvj_core.token_resolver` service; syntax
is unchanged from v1. Tokens read from the **first row** of rendered View
fields. Complex field rewrites are not supported.

## Installation

```bash
composer require drupal/vvjb:^2.0
drush en vvjb
```

## Usage

1. Create or edit a View.
2. Set **Format** to *Views Vanilla JavaScript Basic Carousel*.
3. Set **Show** to *Fields*.
4. Configure orientation / items per screen / gap / autoplay / navigation /
   responsive in the format settings.
5. Save.

**Sample view:** Optional config `views.view.vvjb_example` (`config/optional/`)
installs with the module when there is no ID conflict — use it as a reference
implementation.

## Public API

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

## Drop-in upgrade from 1.x

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

## License

GPL-2.0-or-later.
