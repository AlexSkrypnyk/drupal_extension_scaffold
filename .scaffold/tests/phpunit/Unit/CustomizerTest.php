<?php

declare(strict_types=1);

namespace Scaffold\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Scaffold\Customizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Customizer unit test.
 */
#[CoversClass(Customizer::class)]
class CustomizerTest extends TestCase {

  /**
   * File system.
   */
  protected Filesystem $filesystem;

  protected function setUp(): void {
    parent::setUp();

    $this->filesystem = new Filesystem();
  }

  /**
   * Test conver string.
   *
   * @param string $string_input
   *   String as input.
   * @param string $convert_type
   *   Convert type.
   * @param string|null $expected_string
   *   Expected string.
   * @param bool $expected_sucess
   *   Expected fail or success.
   */
  #[DataProvider('convertStringProvider')]
  public function testConvertString(string $string_input, string $convert_type, string|null $expected_string = NULL, bool $expected_sucess = TRUE): void {
    if (!$expected_sucess) {
      $this->expectException(\Exception::class);
    }
    $string_output = Customizer::convertString($string_input, $convert_type);
    if ($expected_sucess) {
      $this->assertEquals($expected_string, $string_output);
    }
  }

  /**
   * Data provider for convert string test.
   */
  public static function convertStringProvider(): array {
    return [
      'test convert file_name' => ['This is-File_name TEST', 'file_name', 'this_is-file_name_test', TRUE],
      'test convert package_namespace' => ['This_is-Package_NAMESPACE TEST', 'package_namespace', 'this_is_package_namespace_test', TRUE],
      'test convert namespace' => ['This is-Namespace-_test', 'namespace', 'ThisIsNamespaceTest', TRUE],
      'test convert class_name' => ['This is-ClassName-_test', 'class_name', 'ThisIsClassNameTest', TRUE],
      'test convert dummy' => ['This is-CLassName-_TEST', 'dummy', NULL, FALSE],
    ];
  }

  /**
   * Test replace string in a file.
   *
   * @param string $string
   *   String, content in file before doing searching & replacment.
   * @param string|string[] $string_search
   *   String to search.
   * @param string|string[] $string_replace
   *   String to replace.
   * @param string $string_expected
   *   Expected string after searching & replacment.
   */
  #[DataProvider('replaceStringInFileProvider')]
  public function testReplaceStringInFile(string $string, string|array $string_search, string|array $string_replace, string $string_expected): void {
    $file_path = tempnam(sys_get_temp_dir(), 'test-replace-string-');
    if (!$file_path) {
      throw new \Exception('Could not create test file: ' . $file_path);
    }
    $this->filesystem->dumpFile($file_path, $string);
    $file_content = file_get_contents($file_path);
    $this->assertEquals($string, $file_content);
    Customizer::replaceStringInFile($string_search, $string_replace, $file_path);
    $file_content = file_get_contents($file_path);
    $this->assertEquals($string_expected, $file_content);
    $this->filesystem->remove($file_path);
  }

  /**
   * Data provider for test replace string in a file.
   */
  public static function replaceStringInFileProvider(): array {
    return [
      ['this text contains your-namespace-package', 'your-namespace-package', 'foo-package', 'this text contains foo-package'],
      ['this text contains your-namespace-package', ['your-namespace-package'], ['foo-package'], 'this text contains foo-package'],
      ['this text contains your-namespace-package', ['your-namespace'], ['foo-package'], 'this text contains foo-package-package'],
      ['this text contains your-namespace-package', ['foo-your-namespace'], ['foo-package'], 'this text contains your-namespace-package']
    ];
  }

  /**
   * Test replace string in dir.
   *
   * @param string|string[] $string_search
   *   String to search.
   * @param string|string[] $string_replace
   *   String to replace.
   * @param string $directory
   *   Directory to search.
   * @param array<mixed> $files
   *   Files in above dir.
   */
  #[DataProvider('replaceStringInFilesInDirectoryProvider')]
  public function testReplaceStringInFilesInDirectory(string|array $string_search, string|array $string_replace, string $directory, array $files): void {
    $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $directory;

    foreach ($files as $file) {
      $file_path = $dir . DIRECTORY_SEPARATOR . $file['path'];
      $this->filesystem->dumpFile($file_path, $file['content']);
      $file_content = file_get_contents($file_path);
      $this->assertEquals($file['content'], $file_content);
    }

    Customizer::replaceStringInFilesInDirectory($string_search, $string_replace, $dir);

    foreach ($files as $file) {
      $file_path = $dir . DIRECTORY_SEPARATOR . $file['path'];
      $file_content = file_get_contents($file_path);
      $this->assertEquals($file['expected_content'], $file_content);
    }

    $this->filesystem->remove($dir);
  }

  /**
   * Data provider for test replace string in dir.
   */
  public static function replaceStringInFilesInDirectoryProvider(): array {
    return [
      [
        'search-string',
        'replace-string',
        'dir-1',
        [
          [
            'path' => 'foo/file-1.txt',
            'content' => 'Foo file 1 search-string content',
            'expected_content' => 'Foo file 1 replace-string content'
          ],
          [
            'path' => 'foo/file-2.txt',
            'content' => 'Foo file 2 search-string content',
            'expected_content' => 'Foo file 2 replace-string content'
          ],
          [
            'path' => 'foo/bar/file-1.txt',
            'content' => 'Foo/Bar file 1 content',
            'expected_content' => 'Foo/Bar file 1 content',
          ],
        ]
      ],
    ];
  }

}
