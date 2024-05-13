<?php

namespace Scaffold\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class ScaffoldFunctionalTest extends TestCase {
  protected function setUp(): void {
    parent::setUp();

    $this->filesystem = new Filesystem();
    $this->testDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test-' . time();
    $this->filesystem->mkdir($this->testDir);
  }

  public function testBasic() {
    $working_dir = Path::makeAbsolute('../../../..', __DIR__);
    var_dump($working_dir);
  }

  protected function tearDown(): void {
    parent::tearDown();
    $this->filesystem->remove($this->testDir);
  }
}
