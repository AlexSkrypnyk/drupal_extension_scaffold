<?php

declare(strict_types=1);

namespace Scaffold\Tests\Traits;

use Symfony\Component\Filesystem\Filesystem;

trait FileTrait {
  public function fileFindDir(string $file, ?string $start = NULL): string {
    if (empty($start)) {
      $start = dirname(__FILE__);
    }

    $fs = new Filesystem();

    $current = $start;

    while (!empty($current) && $current !== DIRECTORY_SEPARATOR) {
      $path = $current . DIRECTORY_SEPARATOR . $file;
      if ($fs->exists($path)) {
        return $current;
      }
      $current = dirname($current);
    }

    throw new \RuntimeException('File not found: ' . $file);
  }
}
