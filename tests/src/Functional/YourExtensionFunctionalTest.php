<?php

declare(strict_types=1);

namespace Drupal\Tests\your_extension\Functional;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Group;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the functionality of YourExtensionForm.
 *
 * @coversDefaultClass \Drupal\your_extension\Form\YourExtensionForm
 *
 * @group your_extension
 */
#[Group('your_extension')]
#[RunTestsInSeparateProcesses]
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
   * Tests that text saved through the settings form renders in <noscript>.
   */
  public function testGetText(): void {
    $account = $this->drupalCreateUser(['administer site configuration']);
    $this->assertNotEmpty($account);
    $this->drupalLogin($account);

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
