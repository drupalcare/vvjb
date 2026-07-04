<?php

declare(strict_types=1);

namespace Drupal\vvjb;

/**
 * Vvjb-specific constants — basic-carousel pattern-only values.
 *
 * Cross-cutting constants live in vvj_core's `ValidationBounds`.
 *
 * @internal Final, non-instantiable.
 */
final class VvjbConstants {

  /**
   * Carousel orientation values.
   */
  public const string ORIENTATION_HORIZONTAL = 'horizontal';
  public const string ORIENTATION_VERTICAL = 'vertical';
  public const string ORIENTATION_HYBRID = 'hybrid';
  public const string DEFAULT_ORIENTATION = self::ORIENTATION_HORIZONTAL;

  /**
   * Navigation control values.
   */
  public const string NAV_ARROWS = 'arrows';
  public const string NAV_DOTS = 'dots';
  public const string NAV_BOTH = 'both';
  public const string NAV_NONE = 'none';
  public const string DEFAULT_NAVIGATION = self::NAV_BOTH;

  /**
   * Dot-navigation style values.
   *
   * Circle is the default appearance (no class). Bar and Square map to the
   * `bar-dots` / `square-dots` wrapper classes the CSS already ships.
   */
  public const string DOT_STYLE_CIRCLE = 'circle';
  public const string DOT_STYLE_BAR = 'bar';
  public const string DOT_STYLE_SQUARE = 'square';
  public const string DEFAULT_DOT_STYLE = self::DOT_STYLE_CIRCLE;

  /**
   * Map of dot-style value → wrapper CSS class ('' = default circle).
   *
   * The class is printed on the views wrapper (an ancestor of the carousel),
   * where `.bar-dots .vvjb …` / `.square-dots .vvjb …` target it — the same
   * spot a site builder would add it by hand.
   *
   * @var array<string, string>
   */
  public const array DOT_STYLE_CLASSES = [
    self::DOT_STYLE_CIRCLE => '',
    self::DOT_STYLE_BAR => 'bar-dots',
    self::DOT_STYLE_SQUARE => 'square-dots',
  ];

  /**
   * Layout defaults.
   */
  public const int DEFAULT_ITEMS_SMALL = 1;
  public const int DEFAULT_ITEMS_BIG = 3;
  public const int DEFAULT_GAP = 16;
  public const int DEFAULT_ITEM_WIDTH = 0;

  /**
   * Behavior defaults.
   */
  public const bool DEFAULT_LOOPING = TRUE;
  public const int DEFAULT_SLIDE_TIME = 5000;

  /**
   * Slide-time bounds (milliseconds).
   *
   * Vvjb-specific, distinct from the seconds-based bounds in
   * vvj_core's ValidationBounds.
   */
  public const int MIN_SLIDE_TIME_MS = 0;
  public const int MAX_SLIDE_TIME_MS = 15000;
  public const int SLIDE_TIME_STEP = 1000;

  /**
   * Item count + gap bounds.
   */
  public const int MIN_ITEMS = 1;
  public const int MIN_GAP = 0;
  public const int MIN_ITEM_WIDTH = 0;

  /**
   * Responsive breakpoint default.
   */
  public const string DEFAULT_BREAKPOINT = '992';

  /**
   * Scrollable-dots width bounds.
   */
  public const int DEFAULT_SCROLLABLE_DOTS_WIDTH = 0;
  public const int MIN_SCROLLABLE_DOTS_WIDTH = 120;
  public const int MAX_SCROLLABLE_DOTS_WIDTH = 700;

  /**
   * Reserved deeplink-identifier words specific to vvjb.
   *
   * Conflict with vvjb's URL fragment prefix `#carousel-{id}-{n}`.
   *
   * @var list<string>
   */
  public const array DEEPLINK_RESERVED_WORDS = ['carousel', 'slide', 'vvjb', 'vvj'];

  /**
   * Map of option key → data attribute name.
   *
   * Rendered on the carousel root as `data-{name}="{value}"`.
   *
   * @var array<string, string>
   */
  public const array DATA_ATTRIBUTE_MAP = [
    'orientation' => 'orientation',
    'items_small' => 'items-small',
    'items_big' => 'items-big',
    'gap' => 'gap',
    'item_width' => 'item-width',
    'looping' => 'looping',
    'slide_time' => 'slide-time',
    'navigation' => 'navigation',
    'breakpoints' => 'breakpoints',
    'unique_id' => 'unique-id',
    'show_play_pause' => 'show-play-pause',
    'show_progress_bar' => 'show-progress-bar',
    'show_page_counter' => 'show-page-counter',
    'enable_keyboard_nav' => 'enable-keyboard-nav',
    'enable_touch_swipe' => 'enable-touch-swipe',
    'enable_pause_on_hover' => 'enable-pause-on-hover',
    'pause_on_reduced_motion' => 'pause-on-reduced-motion',
    'scrollable_dots_width' => 'scrollable-dots-width',
  ];

  /**
   * Prevent instantiation.
   */
  private function __construct() {
  }

}
