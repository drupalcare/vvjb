<?php

namespace Drupal\vvjb\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
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
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::buildOptionsForm($form, $form_state);

    $form['breakpoints'] = [
      '#type' => 'select',
      '#title' => $this->t('Responsive Breakpoint'),
      '#options' => [
        '576' => '576 px',
        '768' => '768 px',
        '992' => '992 px',
        '1200' => '1200 px',
        '1400' => '1400 px',
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
  }

  protected function generateUniqueId(): int {
    return random_int(10000000, 99999999);
  }

  public function render(): array {
    $rows = [];
    foreach ($this->view->result as $row) {
      $rows[] = $this->view->rowPlugin->render($row);
    }

    $libraries = [
      'vvjb/vvjb',
      'vvjb/vvjb__' . $this->options['breakpoints'],
    ];

    return [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#rows' => $rows,
      '#unique_id' => $this->options['unique_id'],
      '#attached' => [
        'library' => $libraries,
      ],
    ];
  }

}

