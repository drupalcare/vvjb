<?php

declare(strict_types=1);

namespace Drupal\vvjb\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsStyle;
use Drupal\vvj_core\Plugin\views\style\VvjStylePluginBase;
use Drupal\vvjb\VvjbConstants;

/**
 * Renders Views rows as a vanilla-JavaScript basic carousel.
 *
 * @ingroup views_style_plugins
 */
#[ViewsStyle(
  id: 'views_vvjb',
  title: new TranslatableMarkup('Views Vanilla JavaScript Basic Carousel'),
  help: new TranslatableMarkup('Render items in a responsive, touch-friendly, JS-only carousel.'),
  theme: 'views_view_vvjb',
  display_types: ['normal'],
)]
class BasicCarousel extends VvjStylePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getModuleSlug(): string {
    return 'vvjb';
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomElementTag(): string {
    return 'vvjb-carousel';
  }

  /**
   * {@inheritdoc}
   *
   * Vvjb does not ship a `vvjb-style` library — the main `vvjb` library
   * already includes the visual layer.
   */
  protected function supportsEnableCss(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * @return list<string>
   *   Reserved words that conflict with vvjb's `#carousel-` prefix.
   */
  protected function getDeeplinkReservedWords(): array {
    return VvjbConstants::DEEPLINK_RESERVED_WORDS;
  }

  /**
   * Restore v1's "Enable Deep Linking" checkbox description (verbatim).
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The description.
   */
  protected function getDeeplinkEnableDescription(): TranslatableMarkup {
    return $this->t('Generate shareable links for each slide that appear in the browser URL. Note: This feature requires navigation to include dots (Dots or Both).');
  }

  /**
   * Restore v1's deep-link "URL Identifier" field description (verbatim).
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The description.
   */
  protected function getDeeplinkIdentifierDescription(): TranslatableMarkup {
    return $this->t('Short identifier used in slide links. Example: "products" creates links like #carousel-products-3. Will be automatically cleaned: converted to lowercase, spaces become hyphens, special characters removed.');
  }

  /**
   * {@inheritdoc}
   */
  protected function definePatternOptions(array $options): array {
    $options['orientation'] = ['default' => VvjbConstants::DEFAULT_ORIENTATION];
    $options['items_small'] = ['default' => VvjbConstants::DEFAULT_ITEMS_SMALL];
    $options['items_big'] = ['default' => VvjbConstants::DEFAULT_ITEMS_BIG];
    $options['gap'] = ['default' => VvjbConstants::DEFAULT_GAP];
    $options['item_width'] = ['default' => VvjbConstants::DEFAULT_ITEM_WIDTH];
    $options['looping'] = ['default' => VvjbConstants::DEFAULT_LOOPING];
    $options['slide_time'] = ['default' => VvjbConstants::DEFAULT_SLIDE_TIME];
    $options['navigation'] = ['default' => VvjbConstants::DEFAULT_NAVIGATION];
    $options['dot_style'] = ['default' => VvjbConstants::DEFAULT_DOT_STYLE];
    $options['breakpoints'] = ['default' => VvjbConstants::DEFAULT_BREAKPOINT];
    $options['show_play_pause'] = ['default' => TRUE];
    $options['show_progress_bar'] = ['default' => TRUE];
    $options['show_page_counter'] = ['default' => TRUE];
    $options['enable_keyboard_nav'] = ['default' => TRUE];
    $options['enable_touch_swipe'] = ['default' => TRUE];
    $options['enable_pause_on_hover'] = ['default' => TRUE];
    $options['pause_on_reduced_motion'] = ['default' => TRUE];
    $options['scrollable_dots_width'] = ['default' => VvjbConstants::DEFAULT_SCROLLABLE_DOTS_WIDTH];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildPatternSections(array &$form): void {
    $this->buildLayoutSection($form);
    $this->buildBehaviorSection($form);
    $this->buildControlsSection($form);
    $this->buildNavigationSection($form);
    $this->buildAccessibilitySection($form);
    $this->buildResponsiveSection($form);
  }

  /**
   * Layout section — orientation, items per screen, gap, item width.
   *
   * @param array<int|string, mixed> $form
   *   The form, modified in place.
   */
  protected function buildLayoutSection(array &$form): void {
    $form['layout_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Layout Settings'),
      '#open' => TRUE,
      '#weight' => -40,
    ];

    $form['layout_section']['orientation'] = [
      '#type' => 'select',
      '#title' => $this->t('Orientation'),
      '#options' => [
        VvjbConstants::ORIENTATION_HORIZONTAL => $this->t('Horizontal'),
        VvjbConstants::ORIENTATION_VERTICAL => $this->t('Vertical'),
        VvjbConstants::ORIENTATION_HYBRID => $this->t('Hybrid (vertical below breakpoint)'),
      ],
      '#default_value' => $this->options['orientation'],
      '#description' => $this->t('Choose the carousel orientation.'),
    ];

    $form['layout_section']['items_small'] = [
      '#type' => 'number',
      '#title' => $this->t('Items per screen (small)'),
      '#default_value' => $this->options['items_small'],
      '#min' => VvjbConstants::MIN_ITEMS,
      '#step' => 1,
      '#description' => $this->t('Number of items to display on small screens.'),
    ];

    $form['layout_section']['items_big'] = [
      '#type' => 'number',
      '#title' => $this->t('Items per screen (large)'),
      '#default_value' => $this->options['items_big'],
      '#min' => VvjbConstants::MIN_ITEMS,
      '#step' => 1,
      '#description' => $this->t('Number of items to display on large screens.'),
    ];

    $form['layout_section']['gap'] = [
      '#type' => 'number',
      '#title' => $this->t('Gap between items (px)'),
      '#default_value' => $this->options['gap'],
      '#min' => VvjbConstants::MIN_GAP,
      '#step' => 1,
      '#description' => $this->t('Space between carousel items in pixels.'),
    ];

    $form['layout_section']['item_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Custom item max width (px, optional)'),
      '#default_value' => $this->options['item_width'],
      '#min' => VvjbConstants::MIN_ITEM_WIDTH,
      '#step' => 1,
      '#description' => $this->t('Set a custom maximum width for items. Leave at 0 for automatic width.'),
    ];
  }

  /**
   * Behavior section — autoplay, looping, hover, swipe, keyboard.
   *
   * @param array<int|string, mixed> $form
   *   The form, modified in place.
   */
  protected function buildBehaviorSection(array &$form): void {
    $form['behavior_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Behavior Settings'),
      '#open' => TRUE,
      '#weight' => -30,
    ];

    $form['behavior_section']['slide_time'] = [
      '#type' => 'number',
      '#title' => $this->t('Autoplay interval (ms)'),
      '#default_value' => $this->options['slide_time'],
      '#min' => VvjbConstants::MIN_SLIDE_TIME_MS,
      '#max' => VvjbConstants::MAX_SLIDE_TIME_MS,
      '#step' => VvjbConstants::SLIDE_TIME_STEP,
      '#description' => $this->t('Set to 0 to disable autoplay.'),
    ];

    $form['behavior_section']['looping'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable looping'),
      '#default_value' => $this->options['looping'],
      '#description' => $this->t('Allow the carousel to loop back to the beginning.'),
    ];

    $form['behavior_section']['enable_pause_on_hover'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pause on hover'),
      '#default_value' => $this->options['enable_pause_on_hover'],
      '#description' => $this->t('Pause the carousel when the user hovers over it.'),
    ];

    $form['behavior_section']['enable_touch_swipe'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable touch/swipe gestures'),
      '#default_value' => $this->options['enable_touch_swipe'],
      '#description' => $this->t('Allow users to swipe between carousel items on touch devices.'),
    ];

    $form['behavior_section']['enable_keyboard_nav'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable keyboard navigation'),
      '#default_value' => $this->options['enable_keyboard_nav'],
      '#description' => $this->t('Allow users to navigate using arrow keys, Space, Home, and End keys.'),
    ];
  }

  /**
   * Controls section — play/pause, progress bar, page counter visibility.
   *
   * @param array<int|string, mixed> $form
   *   The form, modified in place.
   */
  protected function buildControlsSection(array &$form): void {
    $form['controls_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Control Elements'),
      '#open' => TRUE,
      '#weight' => -25,
    ];

    $form['controls_section']['show_play_pause'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show play/pause button'),
      '#default_value' => $this->options['show_play_pause'],
      '#description' => $this->t('Display a button to manually control carousel autoplay.'),
    ];

    $form['controls_section']['show_progress_bar'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show progress bar'),
      '#default_value' => $this->options['show_progress_bar'],
      '#description' => $this->t('Display an animated progress indicator showing time until next transition.'),
    ];

    $form['controls_section']['show_page_counter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show page counter'),
      '#default_value' => $this->options['show_page_counter'],
      '#description' => $this->t('Display "X of Y" page counter showing current position.'),
    ];
  }

  /**
   * Navigation section — type + scrollable-dots width.
   *
   * @param array<int|string, mixed> $form
   *   The form, modified in place.
   */
  protected function buildNavigationSection(array &$form): void {
    $form['navigation_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Navigation Settings'),
      '#open' => TRUE,
      '#weight' => -20,
    ];

    $form['navigation_section']['navigation'] = [
      '#type' => 'select',
      '#title' => $this->t('Navigation type'),
      '#options' => [
        VvjbConstants::NAV_ARROWS => $this->t('Arrows only'),
        VvjbConstants::NAV_DOTS => $this->t('Dots only'),
        VvjbConstants::NAV_BOTH => $this->t('Arrows and Dots'),
        VvjbConstants::NAV_NONE => $this->t('None'),
      ],
      '#default_value' => $this->options['navigation'],
      '#description' => $this->t('Choose the navigation controls to display.'),
    ];

    $form['navigation_section']['scrollable_dots_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Scrollable dots width (px)'),
      '#default_value' => $this->options['scrollable_dots_width'],
      '#min' => 0,
      '#max' => VvjbConstants::MAX_SCROLLABLE_DOTS_WIDTH,
      '#step' => 1,
      '#field_suffix' => 'px',
      '#description' => $this->t('Set a maximum width for the dots navigation to make it scrollable. Set to 0 to disable. Range: @min-@max px.', [
        '@min' => VvjbConstants::MIN_SCROLLABLE_DOTS_WIDTH,
        '@max' => VvjbConstants::MAX_SCROLLABLE_DOTS_WIDTH,
      ]),
      '#states' => [
        'visible' => [
          [':input[name="style_options[navigation_section][navigation]"]' => ['value' => VvjbConstants::NAV_DOTS]],
          [':input[name="style_options[navigation_section][navigation]"]' => ['value' => VvjbConstants::NAV_BOTH]],
        ],
      ],
    ];

    $form['navigation_section']['dot_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Dot navigation style'),
      '#options' => [
        VvjbConstants::DOT_STYLE_CIRCLE => $this->t('Circle (default)'),
        VvjbConstants::DOT_STYLE_BAR => $this->t('Bar'),
        VvjbConstants::DOT_STYLE_SQUARE => $this->t('Square'),
      ],
      '#default_value' => $this->options['dot_style'],
      '#description' => $this->t('Shape of the dot navigation indicators: Circle (round, the default), Bar (horizontal pill), or Square. Only affects the dots, so it applies when Navigation is set to "Dots only" or "Arrows and Dots".'),
      '#states' => [
        'visible' => [
          [':input[name="style_options[navigation_section][navigation]"]' => ['value' => VvjbConstants::NAV_DOTS]],
          [':input[name="style_options[navigation_section][navigation]"]' => ['value' => VvjbConstants::NAV_BOTH]],
        ],
      ],
    ];
  }

  /**
   * Accessibility section — reduced-motion handling.
   *
   * @param array<int|string, mixed> $form
   *   The form, modified in place.
   */
  protected function buildAccessibilitySection(array &$form): void {
    $form['accessibility_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Accessibility Settings'),
      '#open' => FALSE,
      '#weight' => -15,
    ];

    $form['accessibility_section']['pause_on_reduced_motion'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pause on reduced motion preference'),
      '#default_value' => $this->options['pause_on_reduced_motion'],
      '#description' => $this->t('Automatically pause autoplay for users who prefer reduced motion.'),
    ];
  }

  /**
   * Responsive section — breakpoint at which orientation/layout switches.
   *
   * @param array<int|string, mixed> $form
   *   The form, modified in place.
   */
  protected function buildResponsiveSection(array &$form): void {
    $form['responsive_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Responsive Settings'),
      '#open' => FALSE,
      '#weight' => -10,
    ];

    // Assign (do not `+`-union) the pattern-specific keys: array union keeps
    // the LEFT operand's value for shared keys, so a unioned '#options' is
    // silently discarded and the base registry's options (incl. an 'all'
    // value with no backing vvjb library) leak. v1 offered these 5 only.
    $breakpoint_select = $this->buildBreakpointSelect(
      'breakpoints',
      VvjbConstants::DEFAULT_BREAKPOINT,
    );
    $breakpoint_select['#title'] = $this->t('Responsive Breakpoint');
    $breakpoint_select['#description'] = $this->t('Switch orientation or layout mode below this screen width.');
    $breakpoint_select['#options'] = [
      '576' => $this->t('576 px'),
      '768' => $this->t('768 px'),
      '992' => $this->t('992 px'),
      '1200' => $this->t('1200 px'),
      '1400' => $this->t('1400 px'),
    ];
    $form['responsive_section']['breakpoints'] = $breakpoint_select;
  }

  /**
   * {@inheritdoc}
   *
   * Add the breakpoint-specific responsive library on top of the
   * shared library list.
   *
   * @return list<string>
   *   Library identifiers.
   */
  protected function buildLibraryList(): array {
    $libraries = parent::buildLibraryList();
    $breakpoint = $this->options['breakpoints'] ?? VvjbConstants::DEFAULT_BREAKPOINT;
    $breakpoint = is_scalar($breakpoint)
      ? (string) $breakpoint
      : (string) VvjbConstants::DEFAULT_BREAKPOINT;
    $libraries[] = 'vvjb/vvjb__' . $breakpoint;
    return $libraries;
  }

  /**
   * {@inheritdoc}
   *
   * @param array<int|string, mixed> $form
   *   The Views style options form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::validateOptionsForm($form, $form_state);

    $values = $form_state->getValue('style_options');
    if (!is_array($values)) {
      return;
    }

    $behavior = $values['behavior_section'] ?? NULL;
    $behavior_form = $form['behavior_section'] ?? NULL;
    if (is_array($behavior) && is_array($behavior_form)) {
      $raw_slide = $behavior['slide_time'] ?? VvjbConstants::DEFAULT_SLIDE_TIME;
      $slide_time = is_numeric($raw_slide) ? (float) $raw_slide : 0.0;
      $slide_el = $behavior_form['slide_time'] ?? NULL;
      if (is_array($slide_el)
        && ($slide_time < VvjbConstants::MIN_SLIDE_TIME_MS
          || $slide_time > VvjbConstants::MAX_SLIDE_TIME_MS)) {
        $form_state->setError(
          $slide_el,
          $this->t('Autoplay interval must be between @min and @max milliseconds.', [
            '@min' => VvjbConstants::MIN_SLIDE_TIME_MS,
            '@max' => VvjbConstants::MAX_SLIDE_TIME_MS,
          ]),
        );
      }
    }

    $navigation = $values['navigation_section'] ?? NULL;
    $navigation_form = $form['navigation_section'] ?? NULL;
    if (is_array($navigation) && is_array($navigation_form)) {
      $raw_dots = $navigation['scrollable_dots_width'] ?? 0;
      $scrollable_dots_width = is_numeric($raw_dots) ? (float) $raw_dots : 0.0;
      $dots_el = $navigation_form['scrollable_dots_width'] ?? NULL;
      if (is_array($dots_el) && $scrollable_dots_width > 0
        && ($scrollable_dots_width < VvjbConstants::MIN_SCROLLABLE_DOTS_WIDTH
          || $scrollable_dots_width > VvjbConstants::MAX_SCROLLABLE_DOTS_WIDTH)) {
        $form_state->setError(
          $dots_el,
          $this->t('Scrollable dots width must be between @min and @max pixels, or 0 to disable.', [
            '@min' => VvjbConstants::MIN_SCROLLABLE_DOTS_WIDTH,
            '@max' => VvjbConstants::MAX_SCROLLABLE_DOTS_WIDTH,
          ]),
        );
      }
    }

    // Cross-field rule: deep linking requires dots.
    $deeplink_values = $form_state->getValue(['style_options', 'deeplink_section']);
    $navigation_values = $form_state->getValue(['style_options', 'navigation_section']);
    if (is_array($deeplink_values) && !empty($deeplink_values['enable_deeplink'])) {
      $nav = is_array($navigation_values)
        ? ($navigation_values['navigation'] ?? VvjbConstants::DEFAULT_NAVIGATION)
        : VvjbConstants::DEFAULT_NAVIGATION;
      $deeplink_form = $form['deeplink_section'] ?? NULL;
      $deeplink_el = is_array($deeplink_form)
        ? ($deeplink_form['enable_deeplink'] ?? NULL)
        : NULL;
      if (is_array($deeplink_el)
        && !in_array($nav, [VvjbConstants::NAV_DOTS, VvjbConstants::NAV_BOTH], TRUE)) {
        $form_state->setError(
          $deeplink_el,
          $this->t('Deep linking requires navigation to include dots (Dots or Both).'),
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * @param array<int|string, mixed> $form
   *   The Views style options form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state): void {
    $values = $form_state->getValue('style_options');
    if (!is_array($values)) {
      $values = [];
    }
    $form_state->setValue('style_options', $this->flattenFormValues($values));
    parent::submitOptionsForm($form, $form_state);
  }

  /**
   * Flatten the sectioned form values back into the persisted shape.
   *
   * @param array<int|string, mixed> $values
   *   Submitted form values keyed by section then by option name.
   *
   * @return array<string, mixed>
   *   Flattened option array.
   */
  protected function flattenFormValues(array $values): array {
    $flattened = [];

    $layout = $values['layout_section'] ?? NULL;
    if (is_array($layout)) {
      $flattened['orientation'] = $layout['orientation'] ?? VvjbConstants::DEFAULT_ORIENTATION;
      $flattened['items_small'] = $layout['items_small'] ?? VvjbConstants::DEFAULT_ITEMS_SMALL;
      $flattened['items_big'] = $layout['items_big'] ?? VvjbConstants::DEFAULT_ITEMS_BIG;
      $flattened['gap'] = $layout['gap'] ?? VvjbConstants::DEFAULT_GAP;
      $flattened['item_width'] = $layout['item_width'] ?? VvjbConstants::DEFAULT_ITEM_WIDTH;
    }

    $behavior = $values['behavior_section'] ?? NULL;
    if (is_array($behavior)) {
      $flattened['slide_time'] = $behavior['slide_time'] ?? VvjbConstants::DEFAULT_SLIDE_TIME;
      $flattened['looping'] = $behavior['looping'] ?? VvjbConstants::DEFAULT_LOOPING;
      $flattened['enable_pause_on_hover'] = $behavior['enable_pause_on_hover'] ?? TRUE;
      $flattened['enable_touch_swipe'] = $behavior['enable_touch_swipe'] ?? TRUE;
      $flattened['enable_keyboard_nav'] = $behavior['enable_keyboard_nav'] ?? TRUE;
    }

    $controls = $values['controls_section'] ?? NULL;
    if (is_array($controls)) {
      $flattened['show_play_pause'] = $controls['show_play_pause'] ?? TRUE;
      $flattened['show_page_counter'] = $controls['show_page_counter'] ?? TRUE;
      $flattened['show_progress_bar'] = $controls['show_progress_bar'] ?? FALSE;
    }

    $accessibility = $values['accessibility_section'] ?? NULL;
    if (is_array($accessibility)) {
      $flattened['pause_on_reduced_motion'] = $accessibility['pause_on_reduced_motion'] ?? TRUE;
    }

    $navigation = $values['navigation_section'] ?? NULL;
    if (is_array($navigation)) {
      $flattened['navigation'] = $navigation['navigation'] ?? VvjbConstants::DEFAULT_NAVIGATION;
      $flattened['dot_style'] = $navigation['dot_style'] ?? VvjbConstants::DEFAULT_DOT_STYLE;
      $flattened['scrollable_dots_width'] = $navigation['scrollable_dots_width'] ?? VvjbConstants::DEFAULT_SCROLLABLE_DOTS_WIDTH;
    }

    $responsive = $values['responsive_section'] ?? NULL;
    if (is_array($responsive)) {
      $flattened['breakpoints'] = $responsive['breakpoints'] ?? VvjbConstants::DEFAULT_BREAKPOINT;
    }

    $deeplink = $values['deeplink_section'] ?? NULL;
    if (is_array($deeplink)) {
      $flattened['enable_deeplink'] = $deeplink['enable_deeplink'] ?? FALSE;
      $flattened['deeplink_identifier'] = $deeplink['deeplink_identifier'] ?? '';
    }

    $flattened['unique_id'] = $this->options['unique_id'] ?? $this->generateUniqueId();

    return $flattened;
  }

  /**
   * {@inheritdoc}
   *
   * Each Views row is rendered as a slide via the generic row plugin, so
   * vvjb works with ANY row style (Fields or Content/entity rows) exactly as
   * v1 did — the shipped example uses `entity:node` teaser rows. Opt out of
   * the base's Fields-row enforcement (which would force-switch the row and
   * block validation).
   */
  protected function requiresFieldsRow(): bool {
    return FALSE;
  }

}
