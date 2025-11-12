<?php

declare(strict_types=1);

namespace Drupal\vvjb\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\vvjb\VvjbConstants;
use Drupal\Core\Url;

/**
 * Style plugin to render items in a basic JS-powered carousel.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "views_vvjb",
 *   title = @Translation("Views Vanilla JavaScript Basic Carousel"),
 *   help = @Translation("Render items in a responsive, touch-friendly, JS-only carousel."),
 *   theme = "views_view_vvjb",
 *   display_types = {"normal"}
 * )
 */
class ViewsVanillaJavascriptBasicCarousel extends StylePluginBase {

  /**
   * Does the style plugin use a row plugin.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesRowClass = TRUE;

  /**
   * Cached unique ID for this view display.
   *
   * @var int|null
   */
  protected ?int $cachedUniqueId = NULL;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions(): array {
    $options = parent::defineOptions();
    $options['unique_id'] = ['default' => $this->generateUniqueId()];
    $options['orientation'] = ['default' => VvjbConstants::DEFAULT_ORIENTATION];
    $options['items_small'] = ['default' => VvjbConstants::DEFAULT_ITEMS_SMALL];
    $options['items_big'] = ['default' => VvjbConstants::DEFAULT_ITEMS_BIG];
    $options['gap'] = ['default' => VvjbConstants::DEFAULT_GAP];
    $options['item_width'] = ['default' => VvjbConstants::DEFAULT_ITEM_WIDTH];
    $options['looping'] = ['default' => VvjbConstants::DEFAULT_LOOPING];
    $options['slide_time'] = ['default' => VvjbConstants::DEFAULT_SLIDE_TIME];
    $options['navigation'] = ['default' => VvjbConstants::DEFAULT_NAVIGATION];
    $options['breakpoints'] = ['default' => VvjbConstants::DEFAULT_BREAKPOINT];
    $options['show_play_pause'] = ['default' => TRUE];
    $options['show_progress_bar'] = ['default' => TRUE];
    $options['show_page_counter'] = ['default' => TRUE];
    $options['enable_keyboard_nav'] = ['default' => TRUE];
    $options['enable_touch_swipe'] = ['default' => TRUE];
    $options['enable_pause_on_hover'] = ['default' => TRUE];
    $options['pause_on_reduced_motion'] = ['default' => TRUE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::buildOptionsForm($form, $form_state);

    $this->setDefaultElementWeights($form);
    $this->buildWarningMessage($form);
    $this->buildLayoutSection($form);
    $this->buildBehaviorSection($form);
    $this->buildControlsSection($form);
    $this->buildNavigationSection($form);
    $this->buildAccessibilitySection($form);
    $this->buildResponsiveSection($form);
    $this->buildTokenDocumentation($form);
  }

  /**
   * Set weights for default Drupal form elements.
   */
  protected function setDefaultElementWeights(array &$form): void {
    $default_elements = [
      'grouping' => -100,
      'row_class' => -90,
      'default_row_class' => -85,
      'uses_fields' => -80,
      'class' => -75,
      'wrapper_class' => -70,
    ];

    foreach ($default_elements as $element_key => $weight) {
      if (isset($form[$element_key])) {
        $form[$element_key]['#weight'] = $weight;
      }
    }
  }

  /**
   * Build warning message section.
   *
   * @param array $form
   *   The form array.
   */
  protected function buildWarningMessage(array &$form): void {
    if ($this->view->storage->id() === 'vvjb_example') {
      return;
    }

    $form['warning_message'] = [
      '#type' => 'markup',
      '#markup' => '<div class="messages messages--status">' . $this->t(
        'Note: The Basic Carousel component works with Fields/Content. To see an example, check the vvjb_example view by clicking <a href="@url">here</a> to edit it.', [
          '@url' => Url::fromRoute('entity.view.edit_form', ['view' => 'vvjb_example'])->toString(),
        ]
      ) . '</div>',
      '#weight' => -50,
    ];
  }

  /**
   * Build layout configuration section.
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
   * Build behavior configuration section.
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
      '#min' => VvjbConstants::MIN_SLIDE_TIME,
      '#max' => VvjbConstants::MAX_SLIDE_TIME,
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
   * Build controls configuration section.
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
   * Build navigation configuration section.
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
  }

  /**
   * Build accessibility configuration section.
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
   * Build responsive configuration section.
   */
  protected function buildResponsiveSection(array &$form): void {
    $form['responsive_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Responsive Settings'),
      '#open' => FALSE,
      '#weight' => -10,
    ];

    $form['responsive_section']['breakpoints'] = [
      '#type' => 'select',
      '#title' => $this->t('Responsive Breakpoint'),
      '#options' => [
        VvjbConstants::BREAKPOINT_576 => $this->t('576 px'),
        VvjbConstants::BREAKPOINT_768 => $this->t('768 px'),
        VvjbConstants::BREAKPOINT_992 => $this->t('992 px'),
        VvjbConstants::BREAKPOINT_1200 => $this->t('1200 px'),
        VvjbConstants::BREAKPOINT_1400 => $this->t('1400 px'),
      ],
      '#default_value' => $this->options['breakpoints'],
      '#description' => $this->t('Switch orientation or layout mode below this screen width.'),
    ];
  }

  /**
   * Build token documentation section.
   */
  protected function buildTokenDocumentation(array &$form): void {
    $form['token_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Token Documentation'),
      '#open' => FALSE,
      '#weight' => 100,
    ];

    $form['token_section']['description'] = [
      '#markup' => $this->t('<p>When using <em>Global: Text area</em> or <em>Global: Unfiltered text</em> in the Views header, footer, or empty text areas, the default Twig-style tokens (e.g., <code>{{ title }}</code>) will not work with the VVJB style plugin.</p>
        <p>Instead, use the custom VVJB token format to access field values from the <strong>first row</strong> of the View result:</p>
        <ul>
          <li><code>[vvjb:field_name]</code> — The rendered output of the field (e.g., linked title, image, formatted text).</li>
          <li><code>[vvjb:field_name:plain]</code> — A plain-text version of the field, with all HTML stripped.</li>
        </ul>
        <p>Examples:</p>
        <ul>
          <li><code>{{ title }}</code> ➜ <code>[vvjb:title]</code></li>
          <li><code>{{ field_image }}</code> ➜ <code>[vvjb:field_image]</code></li>
          <li><code>{{ body }}</code> ➜ <code>[vvjb:body:plain]</code></li>
        </ul>
        <p>These tokens offer safe and flexible field output for dynamic headings, summaries, and fallback messages in VVJB-enabled Views.</p>'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::validateOptionsForm($form, $form_state);

    $values = $form_state->getValue('style_options');

    if (isset($values['behavior_section']['slide_time'])) {
      $slide_time = $values['behavior_section']['slide_time'];
      if ($slide_time < VvjbConstants::MIN_SLIDE_TIME || $slide_time > VvjbConstants::MAX_SLIDE_TIME) {
        $form_state->setError(
          $form['behavior_section']['slide_time'],
          $this->t('Autoplay interval must be between @min and @max milliseconds.', [
            '@min' => VvjbConstants::MIN_SLIDE_TIME,
            '@max' => VvjbConstants::MAX_SLIDE_TIME,
          ])
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state): void {
    $values = $form_state->getValue('style_options');
    $flattened_values = $this->flattenFormValues($values);
    $form_state->setValue('style_options', $flattened_values);

    parent::submitOptionsForm($form, $form_state);
  }

  /**
   * Flatten nested form values to match original structure.
   */
  protected function flattenFormValues(array $values): array {
    $flattened = [];

    if (isset($values['layout_section'])) {
      $flattened['orientation'] = $values['layout_section']['orientation'] ?? VvjbConstants::DEFAULT_ORIENTATION;
      $flattened['items_small'] = $values['layout_section']['items_small'] ?? VvjbConstants::DEFAULT_ITEMS_SMALL;
      $flattened['items_big'] = $values['layout_section']['items_big'] ?? VvjbConstants::DEFAULT_ITEMS_BIG;
      $flattened['gap'] = $values['layout_section']['gap'] ?? VvjbConstants::DEFAULT_GAP;
      $flattened['item_width'] = $values['layout_section']['item_width'] ?? VvjbConstants::DEFAULT_ITEM_WIDTH;
    }

    if (isset($values['behavior_section'])) {
      $flattened['slide_time'] = $values['behavior_section']['slide_time'] ?? VvjbConstants::DEFAULT_SLIDE_TIME;
      $flattened['looping'] = $values['behavior_section']['looping'] ?? VvjbConstants::DEFAULT_LOOPING;
      $flattened['enable_pause_on_hover'] = $values['behavior_section']['enable_pause_on_hover'] ?? TRUE;
      $flattened['enable_touch_swipe'] = $values['behavior_section']['enable_touch_swipe'] ?? TRUE;
      $flattened['enable_keyboard_nav'] = $values['behavior_section']['enable_keyboard_nav'] ?? TRUE;
    }

    if (isset($values['controls_section'])) {
      $flattened['show_play_pause'] = $values['controls_section']['show_play_pause'] ?? TRUE;
      $flattened['show_page_counter'] = $values['controls_section']['show_page_counter'] ?? TRUE;
      $flattened['show_progress_bar'] = $values['controls_section']['show_progress_bar'] ?? FALSE;
    }

    if (isset($values['accessibility_section'])) {
      $flattened['pause_on_reduced_motion'] = $values['accessibility_section']['pause_on_reduced_motion'] ?? TRUE;
    }

    if (isset($values['navigation_section'])) {
      $flattened['navigation'] = $values['navigation_section']['navigation'] ?? VvjbConstants::DEFAULT_NAVIGATION;
    }

    if (isset($values['responsive_section'])) {
      $flattened['breakpoints'] = $values['responsive_section']['breakpoints'] ?? VvjbConstants::DEFAULT_BREAKPOINT;
    }

    $flattened['unique_id'] = $this->options['unique_id'] ?? $this->generateUniqueId();

    return $flattened;
  }

  /**
   * Generates a unique numeric ID for the view display.
   *
   * @return int
   *   A unique ID between 10000000 and 99999999.
   *
   * @throws \Exception
   *   If an appropriate source of randomness cannot be found.
   */
  protected function generateUniqueId(): int {
    if ($this->cachedUniqueId !== NULL) {
      return $this->cachedUniqueId;
    }

    $this->cachedUniqueId = random_int(VvjbConstants::MIN_UNIQUE_ID, VvjbConstants::MAX_UNIQUE_ID);

    if ($this->cachedUniqueId < VvjbConstants::MIN_UNIQUE_ID) {
      $this->cachedUniqueId += VvjbConstants::MIN_UNIQUE_ID;
    }
    if ($this->cachedUniqueId > VvjbConstants::MAX_UNIQUE_ID) {
      $range = VvjbConstants::MAX_UNIQUE_ID - VvjbConstants::MIN_UNIQUE_ID + 1;
      $this->cachedUniqueId = $this->cachedUniqueId % $range + VvjbConstants::MIN_UNIQUE_ID;
    }

    return $this->cachedUniqueId;
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $rows = [];

    if (!empty($this->view->result)) {
      foreach ($this->view->result as $row) {
        $rendered_row = $this->view->rowPlugin->render($row);
        if ($rendered_row !== NULL) {
          $rows[] = $rendered_row;
        }
      }
    }

    $libraries = $this->buildLibraryList();

    $build = [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#rows' => $rows,
      '#unique_id' => $this->options['unique_id'] ?? $this->generateUniqueId(),
      '#attached' => [
        'library' => $libraries,
      ],
    ];

    return $build;
  }

  /**
   * Build the list of libraries to attach.
   *
   * @return array
   *   An array of library names to attach.
   */
  protected function buildLibraryList(): array {
    return [
      'vvjb/vvjb',
      'vvjb/vvjb__' . ($this->options['breakpoints'] ?? VvjbConstants::DEFAULT_BREAKPOINT),
    ];
  }

}
