<?php

/**
 * @file
 * Contains the hook implementations for the VVJB Views style plugin.
 */

/**
 * Implements hook_views_data_alter().
 */
function vvjb_views_data_alter(array &$data): void {
  // Define the style plugin schema.
  $data['views_style_plugin']['views_vvjb'] = [
    'type' => 'views_style',
    'label' => t('Basic Vanilla JavaScript Carousel'),
    'mapping' => [
      'unique_id' => [
        'type' => 'string',
        'label' => t('Unique ID for the view display'),
      ],
      'orientation' => [
        'type' => 'string',
        'label' => t('Carousel Orientation'),
      ],
      'items_small' => [
        'type' => 'integer',
        'label' => t('Items per screen (small)'),
      ],
      'items_big' => [
        'type' => 'integer',
        'label' => t('Items per screen (large)'),
      ],
      'gap' => [
        'type' => 'integer',
        'label' => t('Gap between items (px)'),
      ],
      'item_width' => [
        'type' => 'integer',
        'label' => t('Custom item width (px)'),
      ],
      'looping' => [
        'type' => 'boolean',
        'label' => t('Enable looping'),
      ],
      'slide_time' => [
        'type' => 'integer',
        'label' => t('Autoplay interval (ms)'),
      ],
      'navigation' => [
        'type' => 'string',
        'label' => t('Navigation type'),
      ],
      'breakpoints' => [
        'type' => 'string',
        'label' => t('Responsive Breakpoint'),
      ],
    ],
  ];
}

