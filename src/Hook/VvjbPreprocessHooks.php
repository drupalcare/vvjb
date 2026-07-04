<?php

declare(strict_types=1);

namespace Drupal\vvjb\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Template\Attribute;
use Drupal\views\Hook\ViewsThemeHooks;
use Drupal\views\ViewExecutable;
use Drupal\vvjb\Plugin\views\style\BasicCarousel;
use Drupal\vvjb\VvjbConstants;

/**
 * Preprocess hooks for VVJB basic carousel.
 *
 * Builds the `data-*` attribute bag from the option map, exposes
 * deeplink configuration to the template, and adds the legacy
 * `.vvj-basic-carousel` class on the views_view wrapper for v1
 * contract preservation.
 */
final class VvjbPreprocessHooks {

  /**
   * Constructor.
   *
   * @param \Drupal\views\Hook\ViewsThemeHooks $viewsThemeHooks
   *   Views row preprocess (replaces removed procedural
   *   template_preprocess_views_view_unformatted in Drupal 12).
   */
  public function __construct(
    private readonly ViewsThemeHooks $viewsThemeHooks,
  ) {}

  /**
   * Implements hook_preprocess_HOOK() for views_view_vvjb.
   *
   * @param array<string, mixed> $variables
   *   Template variables.
   */
  #[Hook('preprocess_views_view_vvjb')]
  public function preprocessViewsViewVvjb(array &$variables): void {
    $view = $variables['view'] ?? NULL;
    if (!($view instanceof ViewExecutable)) {
      return;
    }
    $handler = $view->style_plugin;
    if (!($handler instanceof BasicCarousel)) {
      return;
    }
    $options = $handler->options;
    $variables['options'] = $options;
    $variables['settings'] = $options;

    /** @var array<string, string> $list_attributes */
    $list_attributes = [];
    foreach (VvjbConstants::DATA_ATTRIBUTE_MAP as $option_key => $data_key) {
      if (!isset($options[$option_key])) {
        continue;
      }
      $value = $options[$option_key];
      if (is_bool($value)) {
        $value = $value ? 'true' : 'false';
      }
      elseif ($option_key === 'looping') {
        $value = $value ? '1' : '0';
      }
      $list_attributes['data-' . $data_key] = is_scalar($value) ? (string) $value : '';
    }
    $variables['list_attributes'] = new Attribute($list_attributes);

    $variables['deeplink_enabled'] = (bool) ($options['enable_deeplink'] ?? FALSE);
    $deeplink_identifier = $options['deeplink_identifier'] ?? '';
    $variables['deeplink_identifier'] = is_scalar($deeplink_identifier) ? (string) $deeplink_identifier : '';

    $this->viewsThemeHooks->preprocessViewsViewUnformatted($variables);
  }

  /**
   * Implements hook_preprocess_HOOK() for views_view.
   *
   * Adds the legacy `vvj-basic-carousel` class for v1 contract
   * preservation.
   *
   * @param array<string, mixed> $variables
   *   Template variables.
   */
  #[Hook('preprocess_views_view')]
  public function preprocessViewsView(array &$variables): void {
    $view = $variables['view'] ?? NULL;
    if (!($view instanceof ViewExecutable)) {
      return;
    }
    $style = $view->style_plugin;
    if (!($style instanceof BasicCarousel)) {
      return;
    }
    $attributes = $variables['attributes'] ?? NULL;
    if (is_array($attributes)) {
      $classes = $attributes['class'] ?? [];
      if (!is_array($classes)) {
        $classes = [];
      }
      $classes[] = 'vvj-basic-carousel';

      // Print the chosen dot-navigation style as the wrapper class the CSS
      // already targets (`.bar-dots .vvjb …` / `.square-dots .vvjb …`) — the
      // same spot a site builder would add it by hand. Circle is the default
      // and adds no class.
      $dot_style = $style->options['dot_style'] ?? VvjbConstants::DEFAULT_DOT_STYLE;
      $dot_class = is_string($dot_style)
        ? (VvjbConstants::DOT_STYLE_CLASSES[$dot_style] ?? '')
        : '';
      if ($dot_class !== '') {
        $classes[] = $dot_class;
      }

      $attributes['class'] = $classes;
      $variables['attributes'] = $attributes;
    }
  }

}
