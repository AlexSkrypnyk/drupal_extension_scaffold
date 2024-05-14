<?php

declare(strict_types=1);

namespace Scaffold\Tests;

use Scaffold\Tests\Traits\FileTrait;
use Symfony\Component\Filesystem\Filesystem;

class Dirs {

  use FileTrait;

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
   * Root build directory where the rest of the directories located.
   *
   * The "build" in this context is a place to store assets produced by a single
   * test run.
   *
   * @var string
   */
  public $build;

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

  public function __construct() {
    $this->fs = new Filesystem();
  }

  public function initLocations(): void {
    $this->build = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'drevops-scaffold-' . microtime(TRUE);
    $this->sut = $this->build . '/sut';
    $this->repo = $this->build . '/local_repo';

    $this->fs->mkdir($this->build);

    $this->prepareLocalRepo();
  }

  public function deleteLocations(): void {
    $this->fs->remove($this->build);
  }

  public function printInfo(): void {
    $lines[] = '-- LOCATIONS --';
    $lines[] = 'Build      : ' . $this->build;
    $lines[] = 'SUT        : ' . $this->sut;
    $lines[] = 'Local repo : ' . $this->repo;

    fwrite(STDERR, PHP_EOL . implode(PHP_EOL, $lines) . PHP_EOL);
  }

  protected function prepareLocalRepo(): void {
    $root = $this->fileFindDir('composer.json');

    $this->fs->copy($root . '/composer.json', $this->repo . '/composer.json');
    $this->fs->mirror($root . '/.devtool', $this->repo . '/.devtool');
    $this->fs->mirror($root . '/.circleci', $this->repo . '/.circleci');

    // Add the local repository to the composer.json file.
    $composerjson = file_get_contents($this->repo . '/composer.json');
    if ($composerjson === FALSE) {
      throw new \Exception('Failed to read the local composer.json file.');
    }

    /** @var array $dst_json */
    $dst_json = json_decode($composerjson, TRUE);
    if (!$dst_json) {
      throw new \Exception('Failed to decode the local composer.json file.');
    }

    $dst_json['repositories'][] = [
      'type' => 'path',
      'url' => $this->repo,
      'options' => [
        'symlink' => FALSE,
      ],
    ];
    file_put_contents($this->repo . '/composer.json', json_encode($dst_json, JSON_PRETTY_PRINT));
  }
}
