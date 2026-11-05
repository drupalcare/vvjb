# Views Vanilla JavaScript Basic Carousel (`vvjb`): agent brief

**Who this is for:** whoever works on `vvjb` next, human or agent, with no memory of how it got this way.

**Scope:** this module only. The shared base class, services and CSS contract live in `vvj_core`; change them there,
never copy them here.

## Before you change anything

1. Read [README.md](./README.md) (what the module promises) and [CHANGELOG.md](./CHANGELOG.md) (what changed and why).
2. Read `vvj_core`'s `VvjStylePluginBase` before editing the style plugin. Most of the plugin's behavior is inherited.
3. Check "What must not change" below. A change that touches any item on it needs a changelog entry and an update path.

## Where the facts live

| Fact | Home |
|---|---|
| Views style plugin (ID `views_vvjb`) | `src/Plugin/views/style/BasicCarousel.php` |
| Constants and defaults | `src/VvjbConstants.php` |
| Hooks (theme, preprocess, tokens, help) | `src/Hook/` (OOP `#[Hook]` classes; `vvjb.module` is a stub) |
| Install and update hooks | `vvjb.install` (updates 10001 and 10002) |
| Custom element `<vvjb-carousel>` | `js/vvjb-carousel-element.js` |
| Libraries | `vvjb.libraries.yml` |
| Templates | `templates/` |
| Pattern designs | `patterns/<id>/pattern.yml` plus that Pattern's CSS and JS (`dockrail`, `ledger`, `switchboard`, `swatch`, `nightstory`) |
| Settings schema | `config/schema/` |
| Sample view | `config/optional/` |
| Requirements | `vvjb.info.yml` and `composer.json` |

A Pattern's name, description, options and "needs" list are read from its `pattern.yml` by `vvj_patterns`
(`PatternRepository`). Edit that file, not the select list code. The Patterns select appears only while
`vvj_patterns` is enabled.

## Commands that check a change

From the Drupal project root, with Drupal's Coder and PHPStan installed through Composer:

```bash
vendor/bin/phpcs --standard=web/modules/contrib/vvjb/phpcs.xml.dist web/modules/contrib/vvjb
vendor/bin/phpstan analyse -c web/modules/contrib/vvjb/phpstan.neon
```

PHPStan reads `phpstan.neon` (level `max`). Findings are fixed, not suppressed with a generated baseline. How to run
the PHPUnit suites is in [CONTRIBUTING.md](./CONTRIBUTING.md).

## What must not change

- Plugin ID `views_vvjb`, the theme hook, Twig template names, library names, option keys, the CSS classes on the
  rendered HTML and the `Drupal.vvjb.*` JavaScript API. Sites and themes rely on them (the drop-in contract in the
  changelog).
- Every update hook docblock is one summary line. Put explanation in `//` comments inside the function body.
- SVG pasted by an administrator goes through `vvj_core.svg_sanitizer`. Do not add a second path that prints
  admin-supplied SVG.
- Behavior attaches with `Drupal.behaviors` and `once()`; listeners use the element's AbortController signal so
  `detach('unload')` removes them.
- A failing test is fixed in the code, not by editing the test to pass.
