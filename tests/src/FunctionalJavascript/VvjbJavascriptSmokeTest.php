<?php

declare(strict_types=1);

namespace Drupal\Tests\vvjb\FunctionalJavascript;

use Drupal\Tests\vvj_core\FunctionalJavascript\VvjPatternJavascriptTestBase;
use Drupal\views\Entity\View;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Browser smoke tests for the vvjb example view custom element.
 */
#[Group('vvjb')]
#[Group('vvj_javascript')]
#[RunTestsInSeparateProcesses]
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

  /**
   * The JavaScript-built navigation dots carry the `control-btn` class.
   *
   * The dots exist only after the element hydrates, so the kernel render test
   * cannot see them. `control-btn` is the opt-out hook a theme that styles
   * every button uses to leave these controls alone (owner decision
   * 2026-10-07). Both dot shapes are checked: `<button>` by default, `<a>`
   * when deep linking is on.
   */
  public function testNavigationDotsCarryControlBtn(): void {
    $this->drupalGet('<front>');
    $this->assertDotsCarryControlBtn('button');

    $view = View::load('vvjb_example');
    $this->assertNotNull($view);
    $display = &$view->getDisplay('default');
    $display['display_options']['style']['options']['enable_deeplink'] = TRUE;
    $display['display_options']['style']['options']['deeplink_identifier'] = 'probe';
    $view->save();

    $this->drupalGet('<front>');
    $this->assertDotsCarryControlBtn('a');
  }

  /**
   * Waits for the dots, then asserts each one carries `control-btn`.
   *
   * @param string $tag
   *   The element the dots are expected to be built as.
   */
  private function assertDotsCarryControlBtn(string $tag): void {
    // A count first, so "no dots were built" fails here instead of passing
    // as "no dot lacks the class".
    $this->assertJsCondition(
      "document.querySelectorAll('" . $tag . ".vvjb-carousel-dot').length > 0",
    );
    $result = $this->getSession()->evaluateScript(
      "(function () {"
      . " const dots = document.querySelectorAll('.vvjb-carousel-dot');"
      . " const missing = [...dots].filter((d) => !d.classList.contains('control-btn'));"
      . " return {total: dots.length, missing: missing.length};"
      . "})()"
    );
    self::assertIsArray($result);
    self::assertGreaterThan(0, $result['total'] ?? 0, 'Dots were built.');
    self::assertSame(0, $result['missing'] ?? NULL, 'Every dot carries control-btn.');
  }

}
