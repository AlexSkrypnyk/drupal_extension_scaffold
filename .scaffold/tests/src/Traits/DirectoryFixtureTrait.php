<?php

declare(strict_types=1);

namespace AlexSkrypnyk\drupal_extension_scaffold\Tests\Traits;

trait DirectoryFixtureTrait {

  /**
   * Create a directory tree from a nested array description.
   *
   * @param string $base_path
   *   Directory to create the structure in. Created if it does not exist.
   * @param array $structure
   *   Map of entry name to contents. A string value writes a file with that
   *   content; an array value creates a directory and recurses into it.
   */
  protected function createDirectoryStructure(string $base_path, array $structure): void {
    if (!is_dir($base_path)) {
      mkdir($base_path, 0755, TRUE);
    }

    foreach ($structure as $name => $content) {
      $path = $base_path . '/' . $name;
      if (is_array($content)) {
        mkdir($path, 0755, TRUE);
        $this->createDirectoryStructure($path, $content);
      }
      else {
        file_put_contents($path, $content);
      }
    }
  }

}
