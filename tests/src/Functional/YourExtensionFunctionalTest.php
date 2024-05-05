<?php

declare(strict_types=1);

namespace Drupal\Tests\your_extension\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the functionality of YourExtensionService.
 *
 * @coversDefaultClass \Drupal\your_extension\Form\YourExtensionForm
 *
 * @group your_extension
 */
class YourExtensionFunctionalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['your_extension'];

  /**
   * Tests the functionality of the getText method.
   */
  public function testGetText() {
    $user = $this->createUser(['administer site configuration']);
    $this->drupalLogin($user);

    $this->drupalGet('admin/config/development/your-extension');

    $edit = [
      'text' => '<p>This is test content.</p>',
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->statusMessageContains('The configuration options have been saved.');

    $this->drupalGet('<front>');
    $this->assertSession()->responseContains('<noscript>This is test content.</noscript>');
  }

}
