<?php

declare(strict_types=1);

namespace Drupal\drupal_extension_scaffold\Tests\Functional;

use Composer\Console\Application;
use Drupal\drupal_extension_scaffold\Tests\Dirs;
use Drupal\drupal_extension_scaffold\Tests\Traits\CmdTrait;
use Drupal\drupal_extension_scaffold\Tests\Traits\ComposerTrait;
use Drupal\drupal_extension_scaffold\Tests\Traits\EnvTrait;
use Drupal\drupal_extension_scaffold\Tests\Traits\JsonAssertTrait;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestStatus\Failure;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Base class for functional tests.
 */
class ScaffoldTestCase extends TestCase {

  use CmdTrait;
  use ComposerTrait;
  use EnvTrait;
  use JsonAssertTrait;

  /**
   * The file system.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected $fs;

  /**
   * The fixture directories used in the test.
   *
   * @var \Drupal\drupal_extension_scaffold\Tests\Dirs
   */
  protected $dirs;

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

  /**
   * Check if the test has failed.
   *
   * @return bool
   *   TRUE if the test has failed, FALSE otherwise.
   */
  public function hasFailed(): bool {
    $status = $this->status();

    return $status instanceof Failure;
  }

  /**
   * Get application tester.
   */
  public function getApplicationTester(): ApplicationTester {
    $application = new Application();
    $application->setAutoExit(FALSE);
    $application->setCatchExceptions(FALSE);
    if (method_exists($application, 'setCatchErrors')) {
      $application->setCatchErrors(FALSE);
    }

    return new ApplicationTester($application);
  }

}
