<?php

declare(strict_types=1);

namespace Drupal\Tests\vvjb\Kernel;

use Drupal\Component\Utility\Html;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\views\Views;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Every carousel control button carries the `control-btn` class.
 *
 * WHY THIS EXISTS. A theme that styles every `<button>` globally repaints the
 * carousel's arrows and play/pause control. `control-btn` is the agreed
 * opt-out hook (owner decision 2026-10-07): such a theme excludes
 * `.control-btn`, so the controls keep this module's own design. Losing the
 * class from one control reverts that control to the theme's button look, and
 * nothing else would notice. The dots are built in JavaScript and are asserted
 * by the browser smoke test instead.
 *
 * @license SPDX-License-Identifier: GPL-2.0-or-later
 */
#[Group('vvjb')]
#[RunTestsInSeparateProcesses]
final class VvjbControlButtonClassTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var list<string>
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'filter',
    'text',
    'node',
    'views',
    'vvj_core',
    'vvjb',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    // `system` supplies the date formats the node teaser's timestamp needs.
    $this->installConfig(['system', 'filter', 'node']);
    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
      'display_submitted' => FALSE,
    ])->save();
    $this->installConfig(['vvjb']);
    foreach (range(1, 3) as $i) {
      Node::create([
        'type' => 'article',
        'title' => 'Control probe ' . $i,
        'status' => 1,
      ])->save();
    }
  }

  /**
   * Arrows and play/pause render, and each one carries `control-btn`.
   */
  public function testControlButtonsCarryControlBtn(): void {
    $html = $this->renderExample([
      'navigation' => 'both',
      'show_play_pause' => TRUE,
    ]);
    $xpath = new \DOMXPath(Html::load($html));

    // Each control is located by its own class first, so an empty match fails
    // as "not rendered" rather than passing as "nothing lacks the class".
    foreach (['vvjb-prev', 'vvjb-next', 'vvjb-play-pause-button'] as $class) {
      $nodes = $xpath->query('//button[contains(concat(" ", normalize-space(@class), " "), " ' . $class . ' ")]');
      $this->assertNotFalse($nodes);
      $this->assertGreaterThan(0, $nodes->length, "The $class control rendered.");
      foreach ($nodes as $node) {
        assert($node instanceof \DOMElement);
        $classes = preg_split('/\s+/', trim($node->getAttribute('class'))) ?: [];
        $this->assertContains('control-btn', $classes, "The $class control carries control-btn.");
        $this->assertContains($class, $classes, "The $class control keeps its own class.");
      }
    }

    // Every server-rendered button in this module is a control, so a new
    // button added without the class goes red here too.
    $buttons = $xpath->query('//button');
    $this->assertNotFalse($buttons);
    foreach ($buttons as $button) {
      assert($button instanceof \DOMElement);
      $classes = preg_split('/\s+/', trim($button->getAttribute('class'))) ?: [];
      $this->assertContains('control-btn', $classes, 'Button "' . $button->getAttribute('class') . '" carries control-btn.');
    }
  }

  /**
   * Renders the example view's default display with style option overrides.
   *
   * @param array<string, mixed> $overrides
   *   Style options to set before rendering.
   *
   * @return string
   *   The rendered HTML.
   */
  private function renderExample(array $overrides): string {
    $view = Views::getView('vvjb_example');
    $this->assertNotNull($view, 'The example view ships as optional config.');
    $view->setDisplay('default');
    $style = $view->display_handler->getOption('style');
    $this->assertIsArray($style);
    $options = is_array($style['options'] ?? NULL) ? $style['options'] : [];
    $style['options'] = $overrides + $options;
    $view->display_handler->setOption('style', $style);

    $build = $view->preview('default');
    $this->assertIsArray($build);
    $this->assertNotEmpty($view->result, 'The view returned rows.');
    return (string) $this->container->get('renderer')->renderInIsolation($build);
  }

}
