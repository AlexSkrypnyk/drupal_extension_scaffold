<?php

namespace Drupal\Tests\drupal_circleci_example\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Role testing for demo module.
 *
 * @group drupal_circleci_example
 */
class DemoRoleTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['drupal_circleci_example'];

  /**
   * Test that the Demorole role is present.
   */
  public function testRolePresent() {
    $admin = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($admin);

    $this->drupalGet('admin/people/roles');
    $this->assertText('Demorole');
  }

}
