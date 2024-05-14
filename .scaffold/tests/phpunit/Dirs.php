<?php

declare(strict_types=1);

namespace Scaffold\Tests;

use Scaffold\Tests\Traits\FileTrait;
use Symfony\Component\Filesystem\Filesystem;

class Dirs {
  use FileTrait;

  public string $repo;
  public string $build;
  public string $sut;
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
    $this->fs->mirror($root . '/.devtools', $this->repo . '/.devtools');
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
