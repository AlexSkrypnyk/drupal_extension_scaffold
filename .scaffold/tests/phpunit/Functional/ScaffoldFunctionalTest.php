<?php

declare(strict_types=1);

namespace Scaffold\Tests\Functional;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Scaffold\Customizer;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Process;

#[CoversClass(Customizer::class)]
class ScaffoldFunctionalTest extends TestCase {

  protected Filesystem $filesystem;
  protected string $testDir;
  protected string $sourceDir;

  protected function setUp(): void {
    parent::setUp();

    $this->filesystem = new Filesystem();
    $this->testDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'drupal-extension-scaffold-' . time();
    $this->sourceDir = Path::makeAbsolute('../../../..', __DIR__);
    $this->filesystem->mkdir($this->testDir);
  }

  public function testBasic(): void {
    $process = new Process([
      'composer',
      'create-project',
      '--prefer-dist',
      '--no-interaction',
      'alexskrypnyk/drupal_extension_scaffold=@dev',
      '--repository',
      '{"type": "path", "url": "' . $this->sourceDir . '", "options": {"symlink": false}}',
      $this->testDir,
    ]);
    $process->setEnv([
      'DRUPAL_EXTENSION_SCAFFOLD_NAME' => 'Hello Extension',
    ]);
    $status = $process->run();
    $this->assertEquals(0, $status);
    $process = new Process(['./.devtools/assemble.sh'], $this->testDir);
    $status = $process->run();
    $this->assertEquals(0, $status);
    $process = new Process(['./.devtools/start.sh'], $this->testDir);
    $status = $process->run();
    $this->assertEquals(0, $status);
    $process = new Process(['./.devtools/provision.sh'], $this->testDir);
    $status = $process->run();
    $this->assertEquals(0, $status);

    $this->assertFileExists($this->testDir . DIRECTORY_SEPARATOR . 'hello_extension.info.yml');
  }

  protected function tearDown(): void {
    parent::tearDown();

    $this->filesystem->chmod($this->testDir, 0700, 0000, true);
    $this->filesystem->remove($this->testDir);
  }

}
