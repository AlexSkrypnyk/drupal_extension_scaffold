<?php

declare(strict_types=1);

namespace Drupal\Tests\force_crystal\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the functionality of ForceCrystalService.
 *
 * @coversDefaultClass \Drupal\force_crystal\Form\ForceCrystalForm
 *
 * @group force_crystal
 */
class ForceCrystalFunctionalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['force_crystal'];

  /**
   * Tests the functionality of the getText method.
   */
  public function testGetText() {
    $user = $this->createUser(['administer site configuration']);
    if (!$user instanceof AccountInterface) {
      throw new \Exception('User could not be created.');
    }
    $this->drupalLogin($user);

    $this->drupalGet('admin/config/development/force_crystal');

    $edit = [
      'text' => '<p>This is test content.</p>',
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->statusMessageContains('The configuration options have been saved.');

    $this->drupalGet('<front>');
    $this->assertSession()->responseContains('<noscript>This is test content.</noscript>');
  }

}
