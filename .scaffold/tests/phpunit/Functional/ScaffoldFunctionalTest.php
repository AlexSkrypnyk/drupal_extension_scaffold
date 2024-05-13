<?php

namespace Scaffold\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Process;

class ScaffoldFunctionalTest extends TestCase {
  protected function setUp(): void {
    parent::setUp();

    $this->filesystem = new Filesystem();
    $this->testDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test-' . time();
    $this->filesystem->mkdir($this->testDir);
  }

  public function testBasic() {
    $working_dir = Path::makeAbsolute('../../../..', __DIR__);
    $process = new Process([
      'composer',
      'create-project',
      '--prefer-dist',
      '--no-interaction',
      'alexskrypnyk/drupal_extension_scaffold=@dev',
      '--repository',
      '{"type": "path", "url": "'. $working_dir .'", "options": {"symlink": false}}',
      $this->testDir,
    ]);
    $process->setEnv([
      'DRUPAL_EXTENSION_SCAFFOLD_NAME' => 'Hello Extension',
    ]);
    $process->run();

    $process = new Process(['ls', '-al'], $this->testDir);
    $process->run();
    echo $process->getOutput();
  }

  protected function tearDown(): void {
    parent::tearDown();
    $this->filesystem->remove($this->testDir);
  }
}
