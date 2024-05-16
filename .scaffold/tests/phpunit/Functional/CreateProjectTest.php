<?php

declare(strict_types=1);

namespace Scaffold\Tests\Functional;

use PHPUnit\Framework\Attributes\CoversClass;
use Scaffold\CustomizeCommand;

/**
 * Test the scaffold create-project command with no-install.
 */
#[CoversClass(CustomizeCommand::class)]
class CreateProjectTest extends ScaffoldTestCase {

  public function testCreateProjectNoInstall(): void {
    $this->setAnswers([
      self::ANSWER_NOTHING,
      self::ANSWER_NOTHING,
      self::ANSWER_NOTHING,
      self::ANSWER_NOTHING,
      self::ANSWER_NOTHING,
    ]);

    $this->tester->run($this->getDefaultOptions() + ['--no-install' => TRUE]);

    $this->assertSuccessOutput('Project was customized');
  }

  protected function getDefaultOptions(): array {
    return [
      'command' => 'create-project',
      'package' => 'alexskrypnyk/drupal_extension_scaffold',
      'version' => '@dev',
      '--repository' => [
        json_encode([
          'type' => 'path',
          'url' => $this->dirs->repo,
          'options' => ['symlink' => FALSE],
        ]),
      ],
    ];
  }

}
