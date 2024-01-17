<?php

declare(strict_types = 1);

namespace Drupal\Tests\your_extension\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Class DemoRoleFunctionalTest.
 *
 * Role testing for demo module.
 *
 * @group your_extension
 */
class DemoRoleFunctionalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['pathauto', 'your_extension'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test that the Demorole role is present.
   */
  public function testRolePresent(): void {
    $admin = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($admin);

    $this->drupalGet('admin/people/roles');
    $this->assertSession()->responseContains('Demorole');
  }

}
