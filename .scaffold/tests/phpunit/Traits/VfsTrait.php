<?php

declare(strict_types=1);

namespace Scaffold\Tests\Traits;

use org\bovigo\vfs\vfsStream;

/**
 * Trait VfsTrait.
 *
 * Provides methods for working with the virtual file system.
 */
trait VfsTrait {

  /**
   * The root directory for the virtual file system.
   *
   * @var \org\bovigo\vfs\vfsStreamDirectory
   */
  protected static $vfsRootDirectory;

  /**
   * Set up the root directory for the virtual file system.
   *
   * @param string $name
   *   The name of the root directory.
   */
  public static function vfsSetRoot(string $name = 'root'): void {
    self::$vfsRootDirectory = vfsStream::setup($name);
  }

  /**
   * Create a directory.
   *
   * @param string $path
   *   The path to the directory.
   *
   * @return string
   *   The path to the created directory.
   */
  public static function vfsCreateDirectory(string $path): string {
    $path = static::vfsNormalizePath($path);

    if (!static::$vfsRootDirectory) {
      static::vfsSetRoot();
    }

    return vfsStream::newDirectory($path)->at(static::$vfsRootDirectory)->url();
  }

  /**
   * Create a file.
   *
   * @param string $path
   *   The path to the file.
   * @param string|null $contents
   *   The contents of the file.
   * @param int|null $permissions
   *   The permissions of the file.
   *
   * @return string
   *   The path to the created file.
   */
  public static function vfsCreateFile($path, $contents = NULL, $permissions = NULL) {
    $path = static::vfsNormalizePath($path);

    if (!static::$vfsRootDirectory) {
      static::vfsSetRoot();
    }

    $file = vfsStream::newFile($path, $permissions)->at(static::$vfsRootDirectory);

    if ($contents) {
      $file->withContent($contents);
    }

    return $file->url();
  }

  /**
   * Normalize a path to be used with the virtual file system.
   *
   * @param string $path
   *   The path to normalize.
   *
   * @return string
   *   The normalized path.
   *
   * @throws \Exception
   *   If the path does not start with 'vfs://root/'.
   */
  protected static function vfsNormalizePath(string $path): string {
    $prefix = 'vfs://root/';

    if (!str_starts_with($path, $prefix)) {
      throw new \Exception('Fixture path must start with ' . $prefix);
    }

    return substr($path, strlen($prefix));
  }

}
