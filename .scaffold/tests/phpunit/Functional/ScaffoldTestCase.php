<?php

declare(strict_types=1);

namespace Scaffold\Tests\Functional;

use Composer\Console\Application;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestStatus\Failure;
use Scaffold\Tests\Dirs;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Base class for functional tests.
 */
class ScaffoldTestCase extends TestCase {

  const ANSWER_NOTHING = 'NOTHING';

  /**
   * The file system.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected $fs;

  /**
   * The fixture directories used in the test.
   *
   * @var \Scaffold\Tests\Dirs
   */
  protected $dirs;

  /**
   * The application tester.
   */
  protected ApplicationTester $tester;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->fs = new Filesystem();

    $this->dirs = new Dirs();
    $this->dirs->initLocations();

    $this->tester = $this->getApplicationTester();

    $cwd = $this->dirs->sut;
    chdir($cwd);
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

  protected function setAnswers(array $answers): void {
    foreach ($answers as $key => $answer) {
      if ($answer === self::ANSWER_NOTHING) {
        $answers[$key] = "\n";
      }
    }

    putenv('CUSTOMIZER_ANSWERS=' . json_encode($answers));
  }

  protected function assertSuccessOutput(string|array $strings): void {
    $strings = is_array($strings) ? $strings : [$strings];

    $this->assertSame(0, $this->tester->getStatusCode());

    $output = $this->tester->getDisplay(TRUE);
    foreach ($strings as $string) {
      $this->assertStringContainsString($string, $output);
    }
  }

}
