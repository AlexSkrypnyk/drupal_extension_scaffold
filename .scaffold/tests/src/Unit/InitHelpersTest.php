<?php

declare(strict_types=1);

namespace AlexSkrypnyk\drupal_extension_scaffold\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

use function ci_provider_options;
use function command_wrapper_options;
use function convert_string;
use function drupal_version_default;
use function drupal_version_options;
use function extension_type_options;
use function get_files;
use function is_binary_file;
use function normalize_cspell_words;
use function print_help;
use function process;
use function remove_dir;
use function remove_special_comments;
use function remove_string_content;
use function remove_tokens_with_content;
use function replace_string_content;
use function tool_options;
use function tool_specs;
use function uncomment_line;

/**
 * Unit tests for helper functions in init.php.
 */
#[Group('p0')]
final class InitHelpersTest extends UnitTestCase {

  protected string $originalCwd;

  public static function setUpBeforeClass(): void {
    putenv('SCRIPT_RUN_SKIP=1');
    require_once dirname(__DIR__, 4) . '/init.php';
    parent::setUpBeforeClass();
  }

  protected function setUp(): void {
    parent::setUp();
    $this->originalCwd = getcwd() ?: '/';
  }

  protected function tearDown(): void {
    chdir($this->originalCwd);
    parent::tearDown();
  }

  #[DataProvider('dataProviderConvertString')]
  public function testConvertString(string $input, string $type, string $expected): void {
    $this->assertSame($expected, convert_string($input, $type));
  }

  public static function dataProviderConvertString(): \Iterator {
    $input = 'I am a_string-With spaces 13';

    yield 'file_name' => [$input, 'file_name', 'i_am_a_string-with_spaces_13'];
    yield 'route_path' => [$input, 'route_path', 'i_am_a_string-with_spaces_13'];
    yield 'deployment_id' => [$input, 'deployment_id', 'i_am_a_string-with_spaces_13'];
    yield 'function_name' => [$input, 'function_name', 'i_am_a_string-with_spaces_13'];
    yield 'ui_id' => [$input, 'ui_id', 'i_am_a_string-with_spaces_13'];
    yield 'cli_command' => [$input, 'cli_command', 'i_am_a_string-with_spaces_13'];
    yield 'domain_name' => [$input, 'domain_name', 'i_am_a_stringwith_spaces_13'];
    yield 'package_namespace' => [$input, 'package_namespace', 'i_am_a_stringwith_spaces_13'];
    yield 'namespace' => [$input, 'namespace', 'IAmAStringWithSpaces13'];
    yield 'class_name' => [$input, 'class_name', 'IAmAStringWithSpaces13'];
    yield 'package_name' => [$input, 'package_name', 'i-am-a_string-with-spaces-13'];
    yield 'log_entry' => [$input, 'log_entry', $input];
    yield 'code_comment_title' => [$input, 'code_comment_title', $input];
  }

  public function testConvertStringInvalidType(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid conversion type: dummy_type');
    convert_string('test', 'dummy_type');
  }

  #[DataProvider('dataProviderIsBinaryFile')]
  public function testIsBinaryFile(string $content, bool $expected): void {
    $file = self::$sut . '/test_file';
    file_put_contents($file, $content);
    $this->assertSame($expected, is_binary_file($file));
  }

  public static function dataProviderIsBinaryFile(): \Iterator {
    yield 'text content' => ['Hello World', FALSE];
    yield 'empty file' => ['', FALSE];
    yield 'binary with null byte' => ["Hello\0World", TRUE];
    yield 'only null bytes' => ["\0\0\0", TRUE];
    yield 'multiline text' => ["line1\nline2\nline3", FALSE];
    yield 'binary at start' => ["\0text after null", TRUE];
    yield 'php content' => ['<?php echo "hello"; ?>', FALSE];
  }

  public function testIsBinaryFileMissing(): void {
    // The defensive 'fopen() === FALSE' branch is unreachable from production
    // code (callers always pass paths returned by 'get_files()'), so 'fopen()'
    // is allowed to warn. Suppress 'E_WARNING' so PHPUnit's
    // 'failOnWarning' does not abort the test.
    set_error_handler(static fn(): bool => TRUE, E_WARNING);
    try {
      $this->assertTrue(is_binary_file(self::$sut . '/nonexistent_file'));
    }
    finally {
      restore_error_handler();
    }
  }

  public function testPrintHelp(): void {
    ob_start();
    print_help();
    $output = (string) ob_get_clean();

    $this->assertStringContainsString('Drupal Extension Scaffold', $output);
    $this->assertStringContainsString('Usage:', $output);
    $this->assertStringContainsString('init.php', $output);
    $this->assertStringContainsString('--help', $output);
    $this->assertStringContainsString('DEX_NAME', $output);
    $this->assertStringContainsString('DEX_MACHINE_NAME', $output);
    $this->assertStringContainsString('DEX_TYPE', $output);
    $this->assertStringContainsString('DEX_CI_PROVIDER', $output);
    $this->assertStringContainsString('DEX_COMMAND_WRAPPER', $output);
    $this->assertStringContainsString('DEX_EXAMPLES', $output);
    $this->assertStringContainsString('DEX_REMOVE_SELF', $output);
    $this->assertStringContainsString('DEX_PROCEED', $output);
  }

  /**
   * The Drupal version prompt starts with the latest major checked.
   *
   * Mirrors how the multiselect decides which options render checked: it
   * compares each option key against the default list with strict equality,
   * after casting the key to a string. PHP casts the numeric-string keys of
   * 'drupal_version_options()' to integers, so a default carrying those keys
   * verbatim matches nothing and every option renders unchecked.
   */
  public function testDrupalVersionDefault(): void {
    $default = drupal_version_default();

    $checked = [];
    foreach (array_keys(drupal_version_options()) as $key) {
      $checked[(string) $key] = in_array((string) $key, $default, TRUE);
    }

    $this->assertSame(['10' => FALSE, '11' => TRUE], $checked);
  }

  #[DataProvider('dataProviderRemoveDir')]
  public function testRemoveDir(callable $setup, string $dir_name): void {
    $dir = self::$sut . '/' . $dir_name;
    $setup($dir);
    $this->assertDirectoryExists($dir);
    remove_dir($dir);
    $this->assertDirectoryDoesNotExist($dir);
  }

  public static function dataProviderRemoveDir(): \Iterator {
    yield 'empty directory' => [
      function (string $dir): void {
        mkdir($dir, 0755, TRUE);
      },
      'empty_dir',
    ];

    yield 'directory with files' => [
      function (string $dir): void {
        mkdir($dir, 0755, TRUE);
        file_put_contents($dir . '/file1.txt', 'content1');
        file_put_contents($dir . '/file2.txt', 'content2');
      },
      'dir_with_files',
    ];

    yield 'nested directories' => [
      function (string $dir): void {
        mkdir($dir . '/sub1/sub2', 0755, TRUE);
        file_put_contents($dir . '/file.txt', 'content');
        file_put_contents($dir . '/sub1/file.txt', 'content');
        file_put_contents($dir . '/sub1/sub2/file.txt', 'content');
      },
      'nested_dir',
    ];
  }

  public function testRemoveDirMissing(): void {
    remove_dir(self::$sut . '/nonexistent');
    $this->addToAssertionCount(1);
  }

  #[DataProvider('dataProviderGetFiles')]
  public function testGetFiles(callable $setup, array $expected_filenames, array $not_expected_filenames = []): void {
    chdir(self::$sut);
    $setup(self::$sut);
    $files = get_files();
    $basenames = array_map(static fn(string $f): string => str_replace(self::$sut . '/', '', $f), $files);

    foreach ($expected_filenames as $expected_filename) {
      $this->assertContains($expected_filename, $basenames, 'Expected file not found: ' . $expected_filename);
    }
    foreach ($not_expected_filenames as $not_expected_filename) {
      $this->assertNotContains($not_expected_filename, $basenames, 'Unexpected file found: ' . $not_expected_filename);
    }
  }

  public static function dataProviderGetFiles(): \Iterator {
    yield 'text files included' => [
      function (string $dir): void {
        file_put_contents($dir . '/file1.txt', 'hello');
        file_put_contents($dir . '/file2.php', '<?php echo 1;');
      },
      ['file1.txt', 'file2.php'],
    ];

    yield 'binary files excluded' => [
      function (string $dir): void {
        file_put_contents($dir . '/text.txt', 'hello');
        file_put_contents($dir . '/binary.bin', "hello\0world");
      },
      ['text.txt'],
      ['binary.bin'],
    ];

    yield 'excluded directories' => [
      function (string $dir): void {
        file_put_contents($dir . '/root.txt', 'root');
        mkdir($dir . '/.git', 0755, TRUE);
        file_put_contents($dir . '/.git/config', 'git config');
        mkdir($dir . '/vendor', 0755, TRUE);
        file_put_contents($dir . '/vendor/autoload.php', 'vendor');
        mkdir($dir . '/node_modules', 0755, TRUE);
        file_put_contents($dir . '/node_modules/pkg.js', 'pkg');
        mkdir($dir . '/.idea', 0755, TRUE);
        file_put_contents($dir . '/.idea/workspace.xml', 'idea');
      },
      ['root.txt'],
      ['.git/config', 'vendor/autoload.php', 'node_modules/pkg.js', '.idea/workspace.xml'],
    ];

    yield 'nested text files included' => [
      function (string $dir): void {
        mkdir($dir . '/src/Sub', 0755, TRUE);
        file_put_contents($dir . '/src/file.php', 'php');
        file_put_contents($dir . '/src/Sub/deep.php', 'deep');
      },
      ['src/file.php', 'src/Sub/deep.php'],
    ];
  }

  #[DataProvider('dataProviderReplaceStringContent')]
  public function testReplaceStringContent(array $files, string $needle, string $replacement, array $expected_contents): void {
    chdir(self::$sut);
    foreach ($files as $name => $content) {
      $path = self::$sut . '/' . $name;
      $dir = dirname($path);
      if (!is_dir($dir)) {
        mkdir($dir, 0755, TRUE);
      }
      file_put_contents($path, $content);
    }

    replace_string_content($needle, $replacement);

    foreach ($expected_contents as $name => $expected) {
      $this->assertSame($expected, file_get_contents(self::$sut . '/' . $name));
    }
  }

  public static function dataProviderReplaceStringContent(): \Iterator {
    yield 'simple replacement' => [
      ['file.txt' => 'Hello your_extension world'],
      'your_extension',
      'my_module',
      ['file.txt' => 'Hello my_module world'],
    ];

    yield 'multiple occurrences in file' => [
      ['file.txt' => "your_extension line1\nyour_extension line2"],
      'your_extension',
      'my_module',
      ['file.txt' => "my_module line1\nmy_module line2"],
    ];

    yield 'multiple files' => [
      ['a.txt' => 'has your_extension here', 'b.txt' => 'also your_extension here'],
      'your_extension',
      'my_module',
      ['a.txt' => 'has my_module here', 'b.txt' => 'also my_module here'],
    ];

    yield 'no match leaves file unchanged' => [
      ['file.txt' => 'no match here'],
      'your_extension',
      'my_module',
      ['file.txt' => 'no match here'],
    ];
  }

  #[DataProvider('dataProviderRemoveStringContent')]
  public function testRemoveStringContent(string $content, string $token, string $expected): void {
    chdir(self::$sut);
    file_put_contents(self::$sut . '/file.txt', $content);

    remove_string_content($token);

    $this->assertSame($expected, file_get_contents(self::$sut . '/file.txt'));
  }

  public static function dataProviderRemoveStringContent(): \Iterator {
    yield 'remove line starting with token' => [
      "keep this\n# Remove me\nkeep this too",
      '# Remove me',
      "keep this\nkeep this too",
    ];

    yield 'remove multiple matching lines' => [
      "# Remove\nkeep\n# Remove again",
      '# Remove',
      'keep',
    ];

    yield 'no match leaves content unchanged' => [
      "line1\nline2\nline3",
      'no_match',
      "line1\nline2\nline3",
    ];

    yield 'remove comment instruction line' => [
      "# Uncomment the lines below in your project.\n.ahoy.yml export-ignore\n.circleci export-ignore",
      '# Uncomment the lines below in your project.',
      ".ahoy.yml export-ignore\n.circleci export-ignore",
    ];
  }

  #[DataProvider('dataProviderRemoveTokensWithContent')]
  public function testRemoveTokensWithContent(string $content, string $token, string $expected): void {
    chdir(self::$sut);
    file_put_contents(self::$sut . '/file.txt', $content);

    remove_tokens_with_content($token);

    $this->assertSame($expected, file_get_contents(self::$sut . '/file.txt'));
  }

  public static function dataProviderRemoveTokensWithContent(): \Iterator {
    yield 'remove single block' => [
      "before\n#;< META\nremove this\n#;> META\nafter",
      'META',
      "before\nafter",
    ];

    yield 'remove multiple blocks' => [
      "before\n#;< META\nblock1\n#;> META\nmiddle\n#;< META\nblock2\n#;> META\nafter",
      'META',
      "before\nmiddle\nafter",
    ];

    yield 'no markers leaves content unchanged' => [
      "line1\nline2\nline3",
      'META',
      "line1\nline2\nline3",
    ];

    yield 'nested content removed' => [
      "keep\n#;< TOKEN\nline1\nline2\nline3\n#;> TOKEN\nkeep",
      'TOKEN',
      "keep\nkeep",
    ];

    yield 'block at start of file' => [
      "#;< META\nremoved\n#;> META\nkept",
      'META',
      'kept',
    ];

    yield 'block at end of file' => [
      "kept\n#;< META\nremoved\n#;> META",
      'META',
      'kept',
    ];
  }

  #[DataProvider('dataProviderUncommentLine')]
  public function testUncommentLine(string $content, string $start_string, string $expected): void {
    $file = self::$sut . '/testfile';
    file_put_contents($file, $content);

    uncomment_line($file, $start_string);

    $this->assertSame($expected, file_get_contents($file));
  }

  public static function dataProviderUncommentLine(): \Iterator {
    yield 'uncomment matching line' => [
      "# .ahoy.yml export-ignore\nother line",
      '.ahoy.yml',
      ".ahoy.yml export-ignore\nother line",
    ];

    yield 'only uncomments matching line' => [
      "# .ahoy.yml export-ignore\n# .circleci export-ignore\n# .devtools export-ignore",
      '.circleci',
      "# .ahoy.yml export-ignore\n.circleci export-ignore\n# .devtools export-ignore",
    ];

    yield 'no match leaves content unchanged' => [
      "# .ahoy.yml export-ignore\nother line",
      '.missing',
      "# .ahoy.yml export-ignore\nother line",
    ];

    yield 'multiple matching lines' => [
      "# tests line1\n# tests line2\nother",
      'tests',
      "tests line1\ntests line2\nother",
    ];

    yield 'line without hash prefix unchanged' => [
      ".ahoy.yml export-ignore\n# .ahoy.yml other",
      '.ahoy.yml',
      ".ahoy.yml export-ignore\n.ahoy.yml other",
    ];
  }

  public function testUncommentLineMissingFile(): void {
    uncomment_line(self::$sut . '/nonexistent', 'test');
    $this->addToAssertionCount(1);
  }

  #[DataProvider('dataProviderRemoveSpecialComments')]
  public function testRemoveSpecialComments(string $content, string $expected): void {
    chdir(self::$sut);
    file_put_contents(self::$sut . '/file.txt', $content);

    remove_special_comments();

    $this->assertSame($expected, file_get_contents(self::$sut . '/file.txt'));
  }

  public static function dataProviderRemoveSpecialComments(): \Iterator {
    yield 'remove lines with #;' => [
      "normal line\n#; special comment\nanother normal",
      "normal line\nanother normal",
    ];

    yield 'remove multiple special comments' => [
      "#; first\nnormal\n#; second\n#; third",
      'normal',
    ];

    yield 'no special comments unchanged' => [
      "line1\nline2\nline3",
      "line1\nline2\nline3",
    ];

    yield 'remove marker lines' => [
      "before\n#;< META\n#;> META\nafter",
      "before\nafter",
    ];

    yield 'mixed content' => [
      "keep\n#; remove\nkeep too\n  #; also remove\nand keep",
      "keep\nkeep too\nand keep",
    ];
  }

  public function testNormaliseCspellWordsDeduplicatesAndSorts(): void {
    chdir(self::$sut);
    $input = [
      'dictionaries' => ['php'],
      'words' => ['force_crystal', 'Ahoy', 'force_crystal', 'Force_Crystal', 'yoyodyne', 'ahoy'],
      'ignorePaths' => ['build/'],
    ];
    file_put_contents(self::$sut . '/.cspell.json', json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    normalize_cspell_words();

    $result = json_decode((string) file_get_contents(self::$sut . '/.cspell.json'), TRUE);
    $this->assertIsArray($result);
    /** @var array<string, mixed> $result */
    // Mixed-case duplicates collapse to the first-seen form: 'Ahoy'/'ahoy'
    // -> 'Ahoy'; 'force_crystal'/'Force_Crystal' -> 'force_crystal'.
    $this->assertSame(['Ahoy', 'force_crystal', 'yoyodyne'], $result['words']);
    $this->assertSame(['php'], $result['dictionaries']);
    $this->assertSame(['build/'], $result['ignorePaths']);
  }

  public function testNormaliseCspellWordsMissingFile(): void {
    chdir(self::$sut);

    normalize_cspell_words();

    $this->assertFileDoesNotExist(self::$sut . '/.cspell.json');
  }

  public function testNormaliseCspellWordsInvalidJson(): void {
    chdir(self::$sut);
    file_put_contents(self::$sut . '/.cspell.json', '{not valid json');

    $this->expectException(\JsonException::class);
    normalize_cspell_words();
  }

  public function testNormaliseCspellWordsMissingWordsKey(): void {
    chdir(self::$sut);
    $input = ['dictionaries' => ['php']];
    file_put_contents(self::$sut . '/.cspell.json', json_encode($input));

    normalize_cspell_words();

    $this->assertSame(json_encode($input), file_get_contents(self::$sut . '/.cspell.json'));
  }

  /**
   * @param array<string> $drupal_versions
   * @param array<string> $wrapper
   */
  #[DataProvider('dataProviderProcessValidation')]
  public function testProcessValidation(string $extension_name, string $machine_name, string $type, string $ci, array $drupal_versions, array $wrapper, string $expected_message): void {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage($expected_message);
    process($extension_name, $machine_name, $type, $ci, $drupal_versions, $wrapper, [], FALSE, FALSE, FALSE);
  }

  public static function dataProviderProcessValidation(): \Iterator {
    yield 'empty name' => ['', 'machine', 'module', 'gha', ['10', '11'], ['ahoy'], 'Name is required.'];
    yield 'empty machine name' => ['Name', '', 'module', 'gha', ['10', '11'], ['ahoy'], 'Machine name is required.'];
    yield 'machine name with hyphen' => ['Name', 'my-name', 'module', 'gha', ['10', '11'], ['ahoy'], 'Machine name must start with a lowercase letter'];
    yield 'machine name with uppercase' => ['Name', 'MyName', 'module', 'gha', ['10', '11'], ['ahoy'], 'Machine name must start with a lowercase letter'];
    yield 'machine name starting with digit' => ['Name', '1name', 'module', 'gha', ['10', '11'], ['ahoy'], 'Machine name must start with a lowercase letter'];
    yield 'machine name with special char' => ['Name', 'name!', 'module', 'gha', ['10', '11'], ['ahoy'], 'Machine name must start with a lowercase letter'];
    yield 'empty type' => ['Name', 'machine', '', 'gha', ['10', '11'], ['ahoy'], 'Type is required.'];
    yield 'empty ci provider' => ['Name', 'machine', 'module', '', ['10', '11'], ['ahoy'], 'CI provider is required.'];
    yield 'empty drupal versions' => ['Name', 'machine', 'module', 'gha', [], ['ahoy'], 'At least one Drupal version is required.'];
  }

  /**
   * The Drupal majors survive the round trip through option validation.
   *
   * PHP casts the numeric-string keys of 'drupal_version_options()' to
   * integers, so the majors are the one option set whose key type differs from
   * the strings the prompts are given. Pinning both outcomes here keeps a
   * re-embed of the prompt library from silently accepting an unsupported
   * major, which would prune every CI matrix corner from the built project.
   */
  public function testDrupalVersionOptionsValidateDiscoveredMajors(): void {
    ob_start();
    $accepted = \Prompty::multiselect('Target Drupal versions', options: drupal_version_options(), discovered: ['10', '11']);
    ob_end_clean();

    $this->assertSame(['10', '11'], array_map(strval(...), (array) $accepted));

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Available options: 10, 11.');

    \Prompty::multiselect('Target Drupal versions', options: drupal_version_options(), discovered: ['12']);
  }

  public function testToolOptionsMatchToolSpecs(): void {
    $this->assertSame(array_keys(tool_specs()), array_keys(tool_options()));
  }

  /**
   * The option keys 'process()' acts on must stay in the option sets.
   *
   * 'process()' compares against these values directly: 'circleci' and 'theme'
   * as explicit conditions, 'ahoy' and 'makefile' to decide which runner files
   * survive, with 'gha' and 'module' as the complementary branches. Renaming a
   * key without updating the branch would stop it matching without any error.
   */
  #[DataProvider('dataProviderOptionKeysUsedByProcess')]
  public function testOptionKeysUsedByProcess(string $option_set, string $key): void {
    $options = match ($option_set) {
      'extension_type' => extension_type_options(),
      'ci_provider' => ci_provider_options(),
      default => command_wrapper_options(),
    };

    $this->assertArrayHasKey($key, $options);
  }

  public static function dataProviderOptionKeysUsedByProcess(): \Iterator {
    yield 'theme layout' => ['extension_type', 'theme'];
    yield 'module layout' => ['extension_type', 'module'];
    yield 'circleci directory' => ['ci_provider', 'circleci'];
    yield 'github actions directory' => ['ci_provider', 'gha'];
    yield 'ahoy wrapper' => ['command_wrapper', 'ahoy'];
    yield 'makefile wrapper' => ['command_wrapper', 'makefile'];
  }

}
