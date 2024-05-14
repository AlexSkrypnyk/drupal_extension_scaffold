<?php

declare(strict_types=1);

namespace Scaffold\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Scaffold\Tests\Dirs;
use Scaffold\Tests\Traits\CmdTrait;
use Scaffold\Tests\Traits\ComposerTrait;
use Scaffold\Tests\Traits\EnvTrait;
use Scaffold\Tests\Traits\JsonAssertTrait;
use Symfony\Component\Filesystem\Filesystem;
use PHPUnit\Framework\TestStatus\Failure;

class ScaffoldTestCase extends TestCase {
  use CmdTrait;
  use ComposerTrait;
  use EnvTrait;
  use JsonAssertTrait;

  protected Filesystem $fs;
  protected Dirs $dirs;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->fs = new Filesystem();

    $this->dirs = new Dirs();
    $this->dirs->initLocations();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if (!$this->hasFailed()) {
      $this->dirs->deleteLocations();
    }

    parent::tearDown();
  }

  /**
   * {@inheritdoc}
   */
  protected function onNotSuccessfulTest(\Throwable $t): never {
    $this->dirs->printInfo();

    // Rethrow the exception to allow the test to fail normally.
    parent::onNotSuccessfulTest($t);
  }

  public function hasFailed(): bool {
    $status = $this->status();

    return $status instanceof Failure;
  }
}
