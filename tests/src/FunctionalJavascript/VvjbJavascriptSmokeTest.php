<?php

declare(strict_types=1);

namespace Drupal\Tests\vvjb\FunctionalJavascript;

use Drupal\Tests\vvj_core\FunctionalJavascript\VvjPatternJavascriptTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Browser smoke tests for the vvjb example view custom element.
 */
#[Group('vvjb')]
#[Group('vvj_javascript')]
final class VvjbJavascriptSmokeTest extends VvjPatternJavascriptTestBase {

  /**
   * {@inheritdoc}
   *
   * @var list<string>
   */
  protected static $modules = [
    'node',
    'block',
    'views',
    'field',
    'file',
    'filter',
    'text',
    'vvj_core',
    'vvjb',
  ];

  /**
   * {@inheritdoc}
   */
  protected function vvjExampleViewId(): string {
    return 'vvjb_example';
  }

  /**
   * {@inheritdoc}
   */
  protected function vvjCustomElementTag(): string {
    return 'vvjb-carousel';
  }

}
