<?php

declare(strict_types=1);

namespace Drupal\drupal_extension_scaffold\Tests;

use Drupal\drupal_extension_scaffold\Tests\Traits\FileTrait;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class to work with directories.
 */
class Dirs {

  use FileTrait;

  /**
   * Root project directory.
   *
   * @var string
   */
  public $root;

  /**
   * Root build directory where the rest of the directories located.
   *
   * The "build" in this context is a place to store assets produced by a single
   * test run.
   *
   * @var string
   */
  public $build;

  /**
   * Directory where a copy of the DrevOps Scaffold repository is located.
   *
   * This allows to isolate the test from this repository files and prevent
   * their accidental removal.
   *
   * @var string
   */
  public $repo;

  /**
   * Directory where the test will run.
   *
   * @var string
   */
  public $sut;

  /**
   * The file system.
   */
  protected Filesystem $fs;

  /**
   * Dirs constructor.
   */
  public function __construct() {
    $this->fs = new Filesystem();
  }

  /**
   * Initialize locations.
   */
  public function initLocations(): void {
    $this->root = $this->fileFindDir('composer.json', dirname(__FILE__) . '/../../..');
    $this->build = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'drupal_extension_scaffold-' . microtime(TRUE);
    $this->sut = $this->build . '/sut';
    $this->repo = $this->build . '/local_repo';

    $this->fs->mkdir($this->build);
    $this->fs->mkdir($this->sut);
    $this->fs->mkdir($this->repo);

    $this->prepareLocalRepo();
  }

  /**
   * Delete locations.
   */
  public function deleteLocations(): void {
    $this->fs->remove($this->build);
  }

  /**
   * Print information about locations.
   */
  public function printInfo(): void {
    $lines[] = '-- LOCATIONS --';
    $lines[] = 'Build      : ' . $this->build;
    $lines[] = 'SUT        : ' . $this->sut;
    $lines[] = 'Local repo : ' . $this->repo;

    fwrite(STDERR, PHP_EOL . implode(PHP_EOL, $lines) . PHP_EOL);
  }

  /**
   * Prepare local repository.
   */
  protected function prepareLocalRepo(): void {
    $this->fs->copy($this->root . '/composer.json', $this->repo . '/composer.json');

    $composerjson = file_get_contents($this->repo . '/composer.json');
    if ($composerjson === FALSE) {
      throw new \Exception('Failed to read the local composer.json file.');
    }

    /** @var array $dst_json */
    $dst_json = json_decode($composerjson, TRUE);
    if (!$dst_json) {
      throw new \Exception('Failed to decode the local composer.json file.');
    }
    $dst_json['autoload']['psr-4']['Scaffold\\'] = $this->root . DIRECTORY_SEPARATOR . ltrim($dst_json['autoload']['psr-4']['Scaffold\\'], DIRECTORY_SEPARATOR);
    file_put_contents($this->repo . '/composer.json', json_encode($dst_json, JSON_PRETTY_PRINT));
  }

}
