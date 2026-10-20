<?php

declare(strict_types=1);

namespace Drupal\Tests\vvjb\FunctionalJavascript;

use Drupal\node\Entity\Node;
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
    // The display's nested options are untyped config; assert the shape of
    // each level before writing into it. The shipped view stores only the
    // style `type`, so `options` is absent until the first write and starts
    // as an empty array.
    $display_options = $display['display_options'];
    $this->assertIsArray($display_options);
    $style = $display_options['style'];
    $this->assertIsArray($style);
    $options = $style['options'] ?? [];
    $this->assertIsArray($options);
    $options['enable_deeplink'] = TRUE;
    $options['deeplink_identifier'] = 'probe';
    $style['options'] = $options;
    $display_options['style'] = $style;
    $display['display_options'] = $display_options;
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

  /**
   * Paging stays aligned when the theme puts a margin on the items.
   *
   * Found on a live site whose theme gave every item a 1.25rem right margin
   * while the view's gap setting was 0: positions were added up from item
   * widths and the gap setting only, so each page landed one margin per item
   * further off and the last items could never be reached. Every page must
   * start where page one starts, whatever spacing the theme adds.
   */
  public function testPagingStaysAlignedWithThemeItemMargins(): void {
    foreach (range(4, 6) as $i) {
      Node::create([
        'type' => 'article',
        'title' => 'VVJ browser item ' . (string) $i,
        'status' => 1,
      ])->save();
    }

    $view = View::load('vvjb_example');
    $this->assertNotNull($view);
    $default = &$view->getDisplay('default');
    $display_options = $default['display_options'];
    $this->assertIsArray($display_options);
    $style = $display_options['style'];
    $this->assertIsArray($style);
    $options = $style['options'] ?? [];
    $this->assertIsArray($options);
    // Two items per page at every width, arrows, no autoplay.
    $options['items_big'] = 2;
    $options['items_small'] = 2;
    $options['gap'] = 0;
    $options['navigation'] = 'arrows';
    $options['slide_time'] = 0;
    $style['options'] = $options;
    $display_options['style'] = $style;
    $default['display_options'] = $display_options;
    // The block display uses the default pager (10 items), so all six show.
    $view->save();

    $this->drupalGet('<front>');
    $this->assertJsCondition(
      "document.querySelectorAll('vvjb-carousel .vvjb-item').length === 6",
    );

    $this->assertPagingAlignedWithItemMargins(
      'vvjb-carousel .vvjb-item',
      "!item.classList.contains('non-active-slide')",
      'vvjb-carousel .vvjb-next',
    );
  }

  /**
   * Adds a theme-style item margin, pages once, and checks the alignment.
   *
   * @param string $item
   *   CSS selector for the carousel items.
   * @param string $isActive
   *   JavaScript expression, true when `item` is on the current page.
   * @param string $next
   *   CSS selector for the Next button.
   */
  private function assertPagingAlignedWithItemMargins(string $item, string $isActive, string $next): void {
    // Fixed item width plus a right margin, as the theme on the live site
    // did, then a resize so the carousel lays itself out again.
    $this->getSession()->executeScript(
      "const style = document.createElement('style');"
      . " style.textContent = '" . $item . " { flex: 0 0 100px !important;"
      . " width: 100px !important; max-width: 100px !important;"
      . " margin-inline-end: 30px !important; }';"
      . " document.head.appendChild(style);"
      . " window.dispatchEvent(new Event('resize'));"
      . " window.vvjMarginProbe = null;"
      . " setTimeout(() => {"
      . "   const items = document.querySelectorAll('" . $item . "');"
      . "   const frame = items[0].parentElement.parentElement.getBoundingClientRect();"
      . "   window.vvjMarginProbe = Math.round(items[0].getBoundingClientRect().left - frame.left);"
      . " }, 1000);"
    );
    $this->assertJsCondition('window.vvjMarginProbe !== null', 5000);

    $this->getSession()->getPage()->find('css', $next)->click();

    // The first item of page two must sit where item one sat on page one.
    // Before the fix it sat two margins (60px) further right.
    $this->assertJsCondition(
      "(function () {"
      . " const items = [...document.querySelectorAll('" . $item . "')];"
      . " const frame = items[0].parentElement.parentElement.getBoundingClientRect();"
      . " const first = items.findIndex((item) => " . $isActive . ");"
      . " if (first < 1) { return false; }"
      . " const left = Math.round(items[first].getBoundingClientRect().left - frame.left);"
      . " return Math.abs(left - window.vvjMarginProbe) <= 1;"
      . "})()",
      5000,
    );
  }

}
