<?php

declare(strict_types=1);

namespace Drupal\drupal_extension_scaffold\Tests\Functional;

use PHPUnit\Framework\Attributes\CoversClass;
use Scaffold\CustomizeCommand;

/**
 * Test the scaffold create-project command with no-install.
 */
#[CoversClass(CustomizeCommand::class)]
class ScaffoldCreateProjectTest extends ScaffoldTestCase {

  public function testCustomComposerCommand(): void {
    $cwd = $this->dirs->sut;
    chdir($cwd);

    $repository = json_encode([
      'type' => 'path',
      'url' => $this->dirs->repo,
      'options' => ['symlink' => FALSE],
    ]);

    $options = [
      'command' => 'create-project',
      'package' => 'alexskrypnyk/drupal_extension_scaffold',
      'version' => '@dev',
      '--repository' => [$repository],
      '--no-install' => TRUE,
    ];

    $appTester = $this->getApplicationTester();
    $appTester->run($options);

    $this->assertSame(0, $appTester->getStatusCode());
    $output = $appTester->getDisplay(TRUE);

    $this->assertStringContainsString('Started customize command', $output);
  }

}
