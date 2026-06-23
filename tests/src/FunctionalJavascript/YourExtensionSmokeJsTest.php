<?php

declare(strict_types=1);

namespace Drupal\Tests\your_extension\FunctionalJavascript;

use PHPUnit\Framework\Attributes\Group;

/**
 * Smoke test validating the WebDriver and screenshot pipeline.
 *
 * @group your_extension
 */
#[Group('your_extension')]
class YourExtensionSmokeJsTest extends YourExtensionJsTestBase {

  /**
   * Tests WebDriver connectivity and screenshot generation.
   */
  public function testSmokeWebDriver(): void {
    // Verify unauthenticated page renders.
    $this->drupalGet('/user/login');
    $this->createAutoScreenshot();

    // Create user and log in.
    $account = $this->drupalCreateUser(['administer site configuration']);
    $this->assertNotEmpty($account);
    $this->drupalLogin($account);

    // Verify authenticated page renders.
    $this->drupalGet('<front>');
    $this->createAutoScreenshot();

    // Verify Drupal JavaScript API is available.
    $this->assertJsCondition('typeof Drupal !== "undefined" && typeof Drupal.behaviors !== "undefined"');
  }

}
