<?php

declare(strict_types=1);

namespace Scaffold\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Scaffold\Customizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Customizer unit test.
 */
#[CoversClass(Customizer::class)]
class CustomizerTest extends TestCase {

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
  public static function convertStringProvider() {
    return [
      'test convert file_name' => ['This is-File_name TEST', 'file_name', 'this_is-file_name_test', TRUE],
      'test convert package_namespace' => ['This_is-Package_NAMESPACE TEST', 'package_namespace', 'this_is_package_namespace_test', TRUE],
      'test convert namespace' => ['This is-Namespace-_TEST', 'namespace', 'ThisIsNamespaceTest', TRUE],
      'test convert class_name' => ['This is-CLassName-_TEST', 'class_name', 'ThisIsClassnameTest', TRUE],
      'test convert class_name' => ['This is-CLassName-_TEST', 'dummy', NULL, FALSE],
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
  public function testReplaceStringInFile(string $string, $string_search, $string_replace, string $string_expected): void {
    $file_path = tempnam(sys_get_temp_dir(), 'test-replace-string-');
    if ($file_path) {
      file_put_contents($file_path, $string);
      Customizer::replaceStringInFile($string_search, $string_replace, $file_path);
      $file_content = file_get_contents($file_path);
      $this->assertEquals($string_expected, $file_content);
      unlink($file_path);
    }
  }

  /**
   * Data provider for test replace string in a file.
   */
  public static function replaceStringInFileProvider() {
    return [
      ['this text contains your-namespace-package', 'your-namespace-package', 'foo-package', 'this text contains foo-package'],
      ['this text contains your-namespace-package', ['your-namespace-package'], ['foo-package'], 'this text contains foo-package'],
      ['this text contains your-namespace-package', ['your-namespace'], ['foo-package'], 'this text contains foo-package-package'],
      ['this text contains your-namespace-package', ['foo-your-namespace'], ['foo-package'], 'this text contains your-namespace-package']
    ];
  }
}
