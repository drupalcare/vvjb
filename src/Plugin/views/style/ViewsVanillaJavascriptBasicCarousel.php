<?php

namespace Drupal\vvjb\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

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
   * Set default options.
   *
   * @return array
   *   The default options.
   */
  protected function defineOptions(): array {
    $options = parent::defineOptions();
    $options['unique_id'] = ['default' => $this->generateUniqueId()];
    $options['orientation'] = ['default' => 'horizontal'];
    $options['items_small'] = ['default' => 1];
    $options['items_big'] = ['default' => 3];
    $options['gap'] = ['default' => 16];
    $options['item_width'] = ['default' => 0];
    $options['looping'] = ['default' => TRUE];
    $options['slide_time'] = ['default' => 5000];
    $options['navigation'] = ['default' => 'both'];
    $options['breakpoints'] = ['default' => 992];
    return $options;
  }

  /**
   * Builds the options form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::buildOptionsForm($form, $form_state);

    $form['breakpoints'] = [
      '#type' => 'select',
      '#title' => $this->t('Responsive Breakpoint'),
      '#options' => [
        '576' => $this->t('576 px'),
        '768' => $this->t('768 px'),
        '992' => $this->t('992 px'),
        '1200' => $this->t('1200 px'),
        '1400' => $this->t('1400 px'),
      ],
      '#default_value' => $this->options['breakpoints'],
      '#description' => $this->t('Switch orientation or layout mode below this screen width.'),
    ];

    $form['orientation'] = [
      '#type' => 'select',
      '#title' => $this->t('Orientation'),
      '#options' => [
        'horizontal' => $this->t('Horizontal'),
        'vertical' => $this->t('Vertical'),
        'hybrid' => $this->t('Hybrid (vertical below breakpoint)'),
      ],
      '#default_value' => $this->options['orientation'],
    ];

    $form['items_small'] = [
      '#type' => 'number',
      '#title' => $this->t('Items per screen (small)'),
      '#default_value' => $this->options['items_small'],
      '#min' => 1,
      '#step' => 1,
    ];

    $form['items_big'] = [
      '#type' => 'number',
      '#title' => $this->t('Items per screen (large)'),
      '#default_value' => $this->options['items_big'],
      '#min' => 1,
      '#step' => 1,
    ];

    $form['gap'] = [
      '#type' => 'number',
      '#title' => $this->t('Gap between items (px)'),
      '#default_value' => $this->options['gap'],
      '#min' => 0,
      '#step' => 1,
    ];

    $form['item_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Custom item width (px, optional)'),
      '#default_value' => $this->options['item_width'],
      '#min' => 0,
      '#step' => 1,
    ];

    $form['slide_time'] = [
      '#type' => 'number',
      '#title' => $this->t('Autoplay interval (ms)'),
      '#default_value' => $this->options['slide_time'],
      '#min' => 0,
      '#max' => 15000,
      '#step' => 1000,
      '#description' => $this->t('Set to 0 to disable autoplay.'),
    ];

    $form['looping'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable looping'),
      '#default_value' => $this->options['looping'],
    ];

    $form['navigation'] = [
      '#type' => 'select',
      '#title' => $this->t('Navigation type'),
      '#options' => [
        'arrows' => $this->t('Arrows only'),
        'dots' => $this->t('Dots only'),
        'both' => $this->t('Arrows and Dots'),
        'none' => $this->t('None'),
      ],
      '#default_value' => $this->options['navigation'],
    ];

    $form['vvjb_token_info'] = [
      '#type' => 'details',
      '#title' => $this->t('VVJB Tokens'),
      '#open' => TRUE,
    ];

    $form['vvjb_token_info']['description'] = [
      '#markup' => $this->t('<p>When using <em>Global: Text area</em> or <em>Global: Unfiltered text</em> in the Views header, footer, or empty text areas, the default Twig-style tokens (e.g., <code>{{ title }}</code>) will not work with the VVJB style plugin.</p>
        <p>Instead, use the custom VVJB token format to access field values from the <strong>first row</strong> of the View result:</p>
        <ul>
          <li><code>[vvjb:field_name]</code> – The rendered output of the field (e.g., linked title, image, formatted text).</li>
          <li><code>[vvjb:field_name:plain]</code> – A plain-text version of the field, with all HTML stripped.</li>
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
   * Generates a unique numeric ID for the view display.
   *
   * @return int
   *   A unique ID.
   *
   * @throws \Exception
   *   If an appropriate source of randomness cannot be found.
   */
  protected function generateUniqueId(): int {
    return random_int(10000000, 99999999);
  }

  /**
   * Renders the view with the accordion style.
   *
   * @return array
   *   A render array for the accordion.
   */
  public function render(): array {
    $rows = [];
    foreach ($this->view->result as $row) {
      $rows[] = $this->view->rowPlugin->render($row);
    }

    $libraries = [
      'vvjb/vvjb',
      'vvjb/vvjb__' . $this->options['breakpoints'],
    ];

    $build = [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#rows' => $rows,
      '#unique_id' => $this->options['unique_id'],
      '#attached' => [
        'library' => $libraries,
      ],
    ];

    return $build;
  }

}
