<?php

declare(strict_types=1);

namespace Drupal\vvjb;

/**
 * Defines constants for the VVJB module.
 */
final class VvjbConstants {

  public const ORIENTATION_HORIZONTAL = 'horizontal';
  public const ORIENTATION_VERTICAL = 'vertical';
  public const ORIENTATION_HYBRID = 'hybrid';

  public const NAV_ARROWS = 'arrows';
  public const NAV_DOTS = 'dots';
  public const NAV_BOTH = 'both';
  public const NAV_NONE = 'none';

  /**
   * Scrollable dots configuration constants.
   */
  public const DEFAULT_SCROLLABLE_DOTS_WIDTH = 0;
  public const MIN_SCROLLABLE_DOTS_WIDTH = 120;
  public const MAX_SCROLLABLE_DOTS_WIDTH = 700;

  public const BREAKPOINT_576 = '576';
  public const BREAKPOINT_768 = '768';
  public const BREAKPOINT_992 = '992';
  public const BREAKPOINT_1200 = '1200';
  public const BREAKPOINT_1400 = '1400';

  public const DEFAULT_ORIENTATION = self::ORIENTATION_HORIZONTAL;
  public const DEFAULT_ITEMS_SMALL = 1;
  public const DEFAULT_ITEMS_BIG = 3;
  public const DEFAULT_GAP = 16;
  public const DEFAULT_ITEM_WIDTH = 0;
  public const DEFAULT_LOOPING = TRUE;
  public const DEFAULT_SLIDE_TIME = 5000;
  public const DEFAULT_NAVIGATION = self::NAV_BOTH;
  public const DEFAULT_BREAKPOINT = self::BREAKPOINT_992;

  public const MIN_ITEMS = 1;
  public const MIN_GAP = 0;
  public const MIN_ITEM_WIDTH = 0;
  public const MIN_SLIDE_TIME = 0;
  public const MAX_SLIDE_TIME = 15000;
  public const SLIDE_TIME_STEP = 1000;

  public const MIN_UNIQUE_ID = 10000000;
  public const MAX_UNIQUE_ID = 99999999;

  public const TOKEN_NAMESPACE = 'vvjb';
  public const TOKEN_PLAIN_SUFFIX = ':plain';
  public const TOKEN_PATTERN = '/^[a-zA-Z0-9_]+(:plain)?$/';

  public const DATA_ATTRIBUTE_MAP = [
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

  public const VIEWS_TYPE_INTEGER = 'integer';
  public const VIEWS_TYPE_STRING = 'string';
  public const VIEWS_TYPE_BOOLEAN = 'boolean';

  /**
   * Deep linking configuration constants.
   */
  public const DEEPLINK_IDENTIFIER_MAX_LENGTH = 20;
  public const DEEPLINK_IDENTIFIER_PATTERN = '/^[a-z][a-z0-9-]*[a-z0-9]$/';
  public const DEEPLINK_RESERVED_WORDS = ['carousel', 'slide', 'vvjb'];

  private function __construct() {
  }

}
