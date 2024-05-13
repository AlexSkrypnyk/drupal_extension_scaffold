<?php

declare(strict_types=1);

namespace Scaffold\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Scaffold\Customizer;

/**
 * Customizer unit test.
 */
#[CoversClass(Customizer::class)]
class CustomizerTest extends TestCase {

  public function testConvertString() {
    $namespace = Customizer::convertString('Hello World', 'namespace');
    $this->assertEquals('HelloWorld', $namespace);
  }
}
