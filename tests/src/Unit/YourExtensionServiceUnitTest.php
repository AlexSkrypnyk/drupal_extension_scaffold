<?php

declare(strict_types=1);

namespace Drupal\Tests\your_extension\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\your_extension\YourExtensionService;

/**
 * Tests the YourExtensionService class.
 *
 * @group your_extension
 */
class YourExtensionServiceUnitTest extends UnitTestCase {

  /**
   * Tests the sanitize method of YourExtensionService.
   *
   * @covers \Drupal\your_extension\YourExtensionService::sanitize
   * @dataProvider dataProviderSanitize
   */
  public function testSanitize(string $input, string $expected) {
    $this->assertEquals($expected, YourExtensionService::sanitize($input));
  }

  /**
   * Provides data for testing the sanitize method.
   */
  public static function dataProviderSanitize(): array {
    return [
      ['', ''],
      ['<p>This is <strong>bold</strong> text.</p>', 'This is bold text.'],
      ['<div><span>This is some <em>italic</em> text.</span></div>', 'This is some italic text.'],
      ['<script>alert("Hello!");</script>', 'alert("Hello!");'],
    ];
  }

}
