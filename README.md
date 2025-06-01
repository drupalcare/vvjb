# Views Vanilla Javascript Basic Carousel

## Description

The **Views Vanilla Javascript Basic Carousel** module allows you to create
accessible and responsive carousels for displaying various content items on
your Drupal site. This module integrates with the Views module, providing a
new display style that you can select when creating or editing views.

## Features

- Renders content items in a horizontal or vertical carousel.
- Supports responsive layout via screen size breakpoints.
- Autoplay with play/pause support.
- Configurable:
  - Orientation (horizontal, vertical, hybrid).
  - Items per screen (small/large).
  - Item gap and optional item width.
  - Looping and navigation style (arrows, dots, both, or none).
  - Autoplay interval (1000–15000ms).

## Requirements

- Drupal 10 or later
- Views module

## Installation

1. Download and enable the **Views Vanilla JavaScript Basic Carousel** module.
2. Clear the cache: `drush cr` or via the Drupal admin interface.

## Usage

1. Create or edit a view using the Views module.
2. In the **Format** section, select **Basic Carousel** from the list of
   available display styles.
3. Under **Format Settings**, configure the available options to match your
   design and interaction requirements.
4. In the **Pager** settings, define the number of items to display. Since
   this is a continuous scrolling carousel, traditional pagination is not
   used.

## Configuration Options

- **Orientation:** Choose between horizontal, vertical, or hybrid layout.
- **Items per Screen:** Define the number of visible items on small and large
  screens.
- **Custom Item Width:** Optional fixed width per item.
- **Gap Between Items:** Space between slides, in pixels.
- **Looping:** Toggle whether the carousel should loop back to the beginning.
- **Navigation Style:** Use arrows, dots, both, or no navigation.
- **Autoplay Time:** Set the interval between automatic slide transitions.

## Token Support in Views Text Areas

In Views headers, footers, or empty text areas—when using *Global: Text area* or
*Global: Unfiltered text*—there is an option called **"Use replacement tokens
from the first row."**

The default Twig-style tokens (e.g., `{{ title }}` or `{{ field_image }}`)
**will not work** with the VVJB style. Instead, use the custom tokens provided
by VVJB:

**Examples:**

- `{{ title }}` → `[vvjb:title]`  
- `{{ field_image }}` → `[vvjb:field_image]`

To strip any HTML from the output, you can append `:plain` to the token:

- `[vvjb:title:plain]`

These tokens pull data from the **first row** of the View result and are
designed to work seamlessly with the VVJB rendering system.

## Accessibility

- Uses `aria-live` regions to announce slide changes.
- Supports full keyboard navigation.
- Play/pause interactions are keyboard accessible.

## Maintainers

- [Alaa Haddad](https://www.drupal.org/u/flashwebcenter)

## License

This project is licensed under the
[GNU General Public License, version 2 or later](http://www.gnu.org/licenses/gpl-2.0.html).

---

This README follows Drupal documentation best practices. It clearly outlines
the module's purpose, usage, and configuration, and emphasizes accessibility
and responsive behavior for front-end implementations.
