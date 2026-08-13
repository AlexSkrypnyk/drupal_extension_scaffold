<?php

declare(strict_types=1);

namespace AlexSkrypnyk\drupal_extension_scaffold\Tests\Unit;

use AlexSkrypnyk\drupal_extension_scaffold\Tests\Traits\MockTrait;
use AlexSkrypnyk\File\Testing\DirectoryAssertionsTrait;
use AlexSkrypnyk\File\Testing\FileAssertionsTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\EnvTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\SerializableClosureTrait;
use AlexSkrypnyk\PhpunitHelpers\UnitTestCase as UpstreamUnitTestCase;

/**
 * UnitTestCase fixture class.
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
abstract class UnitTestCase extends UpstreamUnitTestCase {

  use DirectoryAssertionsTrait;
  use EnvTrait;
  use FileAssertionsTrait;
  use MockTrait;
  use SerializableClosureTrait;

  protected function tearDown(): void {
    // Reset before the mock assertions so a failed mock expectation cannot
    // leave environment variables set for the next test.
    self::envReset();
    $this->mockTearDown();
    parent::tearDown();
  }

}
