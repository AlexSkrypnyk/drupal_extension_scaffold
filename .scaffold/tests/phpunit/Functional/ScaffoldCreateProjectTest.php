<?php

declare(strict_types=1);

namespace Scaffold\Tests\Functional;

class ScaffoldCreateProjectTest extends ScaffoldTestCase {
  public function testCreateProjectNoInstall(): void {
    $output = $this->composerRun($this->composerCreateProject('--no-install'));

    $this->assertStringContainsString('Initialised project from DrevOps Scaffold', $output);
    $this->assertStringContainsString('Run `composer install` to further customise the project', $output);

    $this->assertJsonValueEquals($this->composerReadJson(), '$.name', ScaffoldGeneralizer::PROJECT_NAME);
    $this->assertJsonValueEquals($this->composerReadJson(), '$.require-dev."drevops/scaffold"', "@dev");
    $this->assertJsonValueEquals($this->composerReadJson(), '$.scripts."pre-update-cmd"[1]', ScaffoldScriptHandler::class . '::preUpdateCmd');
    $this->assertJsonHasNoKey($this->composerReadJson(), '$.scripts."post-root-package-install"[1]');
    $this->assertJsonValueEquals($this->composerReadJson(), '$.extra."drupal-scaffold"."allowed-packages"[0]', ScaffoldGeneralizer::DREVOPS_SCAFFOLD_NAME);
    $this->assertJsonValueEquals($this->composerReadJson(), '$.autoload.classmap[0]', 'scripts/composer/ScaffoldScriptHandler.php');
    $this->assertJsonValueEquals($this->composerReadJson(), '$.autoload.classmap[1]', 'scripts/composer/ScriptHandler.php');
  }

  public function testCreateProjectInstall(): void {
    $output = $this->composerRun($this->composerCreateProject());

    $this->assertStringContainsString('Initialised project from DrevOps Scaffold', $output);
    $this->assertStringNotContainsString('Run `composer install` to further customise the project', $output);

    $this->assertJsonValueEquals($this->composerReadJson(), '$.name', ScaffoldGeneralizer::PROJECT_NAME);
    $this->assertJsonValueEquals($this->composerReadJson(), '$.require-dev."drevops/scaffold"', "@dev");
    $this->assertJsonValueEquals($this->composerReadJson(), '$.scripts."pre-update-cmd"[1]', ScaffoldScriptHandler::class . '::preUpdateCmd');
    $this->assertJsonHasNoKey($this->composerReadJson(), '$.scripts."post-root-package-install"[1]');
    $this->assertJsonValueEquals($this->composerReadJson(), '$.extra."drupal-scaffold"."allowed-packages"[0]', ScaffoldGeneralizer::DREVOPS_SCAFFOLD_NAME);
    $this->assertJsonValueEquals($this->composerReadJson(), '$.autoload.classmap[0]', 'scripts/composer/ScaffoldScriptHandler.php');
    $this->assertJsonValueEquals($this->composerReadJson(), '$.autoload.classmap[1]', 'scripts/composer/ScriptHandler.php');
  }
}
