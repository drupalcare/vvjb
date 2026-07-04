<?php

declare(strict_types=1);

namespace Drupal\Tests\vvjb\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;
use Drupal\views\Views;
use PHPUnit\Framework\Attributes\Group;

/**
 * Confirms vvjb installs, ships its example view, and appears in Views UI.
 */
#[Group('vvjb')]
final class VvjbInstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   *
   * @var list<string>
   */
  protected static $modules = [
    'node',
    'views',
    'views_ui',
    'vvjb',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The module installs and ships its example view as optional config.
   */
  public function testModuleInstallsAndShipsExampleView(): void {
    $this->assertTrue(
      $this->container->get('module_handler')->moduleExists('vvjb'),
    );
    $view = Views::getView('vvjb_example');
    $this->assertNotNull($view, 'Example view installs as optional config.');
  }

  /**
   * The style is reachable through the Views UI edit form.
   */
  public function testStyleAppearsInViewsUi(): void {
    $admin = $this->drupalCreateUser([
      'administer views',
      'access administration pages',
    ]);
    $this->assertInstanceOf(User::class, $admin);
    $this->drupalLogin($admin);

    $this->drupalGet('admin/structure/views/view/vvjb_example/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('VVJB Example');
  }

  /**
   * The module help page renders with the expected summary text.
   */
  public function testHelpPageRenders(): void {
    $admin = $this->drupalCreateUser([
      'access administration pages',
    ]);
    $this->assertInstanceOf(User::class, $admin);
    $this->drupalLogin($admin);

    $this->drupalGet('admin/help/vvjb');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('Views Vanilla JavaScript Basic Carousel');
  }

}
