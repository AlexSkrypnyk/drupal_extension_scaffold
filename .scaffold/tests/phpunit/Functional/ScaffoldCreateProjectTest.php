<?php

declare(strict_types=1);

namespace Scaffold\Tests\Functional;

class ScaffoldCreateProjectTest extends ScaffoldTestCase {

  public function testCreateProjectNoInstall(): void {
    $this->assertEquals('b', 'b');
  }

  public function testCreateProjectInstall(): void {
    $this->assertEquals('b', 'b');
  }
}
