<?php

declare(strict_types=1);

namespace Drupal\Tests\your_extension\FunctionalJavascript;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Group;

/**
 * Smoke test validating the WebDriver and screenshot pipeline.
 *
 * @group your_extension
 */
#[Group('your_extension')]
#[RunTestsInSeparateProcesses]
class YourExtensionSmokeJsTest extends YourExtensionJsTestBase {

  /**
   * Tests WebDriver connectivity and screenshot generation.
   */
  public function testSmokeWebDriver(): void {
    $this->drupalGet('/user/login');
    $this->createAutoScreenshot();

    $account = $this->drupalCreateUser(['administer site configuration']);
    $this->assertNotEmpty($account);
    $this->drupalLogin($account);

    $this->drupalGet('<front>');
    $this->createAutoScreenshot();

    $this->assertJsCondition('typeof Drupal !== "undefined" && typeof Drupal.behaviors !== "undefined"');
  }

}
