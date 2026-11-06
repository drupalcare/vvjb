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
   * 2026-10-07). Dots are always `<button>` (VVJB 2.0), with deep linking
   * off and on; with it on, the click handler writes the hash.
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
    $this->assertDotsCarryControlBtn('button');
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
   * Without looping, the arrow at an end stays in place but is disabled.
   *
   * Owner decision 2026-10-30: never hide it (a hidden arrow left a gap and
   * shifted the controls). The arrow keeps focus, a click on it does nothing,
   * and it carries `aria-disabled="true"` plus `vvjb-disabled`.
   */
  public function testArrowsDisabledAtTheEndsWithoutLooping(): void {
    $this->setExampleStyleOptions(['looping' => FALSE]);
    $this->drupalGet('<front>');
    $this->assertJsCondition("document.querySelector('vvjb-carousel')._isHydrated === true", 10000);
    $page = $this->getSession()->getPage();

    $this->assertArrowDisabled('.vvjb-prev', TRUE);
    $this->assertArrowDisabled('.vvjb-next', FALSE);
    $page->find('css', 'vvjb-carousel .vvjb-prev')->click();
    $this->assertJsCondition("document.querySelector('vvjb-carousel')._pageIndex === 0");

    // Three items, one per page: two steps reach the end.
    $next = $page->find('css', 'vvjb-carousel .vvjb-next');
    $next->click();
    $next->click();
    $this->assertJsCondition("document.querySelector('vvjb-carousel')._pageIndex === 2");
    $this->assertArrowDisabled('.vvjb-next', TRUE);
    $this->assertArrowDisabled('.vvjb-prev', FALSE);
    $this->assertJsCondition("document.activeElement === document.querySelector('vvjb-carousel .vvjb-next')");
    $next->click();
    $this->assertJsCondition("document.querySelector('vvjb-carousel')._pageIndex === 2");
  }

  /**
   * A looping carousel never disables either arrow.
   */
  public function testLoopingNeverDisablesArrows(): void {
    $this->setExampleStyleOptions(['looping' => TRUE]);
    $this->drupalGet('<front>');
    $this->assertJsCondition("document.querySelector('vvjb-carousel')._isHydrated === true", 10000);
    $this->assertArrowDisabled('.vvjb-prev', FALSE);
    $this->getSession()->getPage()->find('css', 'vvjb-carousel .vvjb-prev')->click();
    $this->assertJsCondition("document.querySelector('vvjb-carousel')._pageIndex === 2");
    $this->assertArrowDisabled('.vvjb-prev', FALSE);
    $this->assertArrowDisabled('.vvjb-next', FALSE);
  }

  /**
   * Sets one item per page, arrows only and no autoplay, plus the overrides.
   *
   * @param array<string, mixed> $overrides
   *   Style options to set on the example view's default display.
   */
  private function setExampleStyleOptions(array $overrides): void {
    $view = View::load('vvjb_example');
    $this->assertNotNull($view);
    $default = &$view->getDisplay('default');
    $display_options = $default['display_options'];
    $this->assertIsArray($display_options);
    $style = $display_options['style'];
    $this->assertIsArray($style);
    $options = $style['options'] ?? [];
    $this->assertIsArray($options);
    $options = $overrides + [
      'items_big' => 1,
      'items_small' => 1,
      'navigation' => 'arrows',
      'slide_time' => 0,
    ] + $options;
    $style['options'] = $options;
    $display_options['style'] = $style;
    $default['display_options'] = $display_options;
    $view->save();
  }

  /**
   * Asserts an arrow's disabled state, by class and by aria-disabled.
   *
   * @param string $selector
   *   The arrow selector inside the carousel.
   * @param bool $disabled
   *   The expected state.
   */
  private function assertArrowDisabled(string $selector, bool $disabled): void {
    $element = "document.querySelector('vvjb-carousel " . $selector . "')";
    $this->assertJsCondition($disabled
      ? $element . ".getAttribute('aria-disabled') === 'true' && " . $element . ".classList.contains('vvjb-disabled')"
      : "!" . $element . ".hasAttribute('aria-disabled') && !" . $element . ".classList.contains('vvjb-disabled')");
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
