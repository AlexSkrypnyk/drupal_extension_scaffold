<?php

declare(strict_types=1);

namespace Drupal\Tests\force_crystal\Kernel;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Group;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\force_crystal\ForceCrystalService;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests the ForceCrystalService class.
 *
 * @group force_crystal
 */
#[Group('force_crystal')]
#[RunTestsInSeparateProcesses]
class ForceCrystalServiceKernelTest extends KernelTestBase {

  use ProphecyTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['force_crystal'];

  /**
   * The ForceCrystalService instance.
   *
   * @var \Drupal\force_crystal\ForceCrystalService
   */
  protected $yourExtensionService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $your_config_config = $this->prophesize(ImmutableConfig::class);
    $your_config_config->get('text')
      ->willReturn('<p>This is <strong>bold</strong> text.</p>');

    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    $config_factory->get('force_crystal.settings')
      ->willReturn($your_config_config->reveal());

    $this->yourExtensionService = new ForceCrystalService($config_factory->reveal());
  }

  /**
   * Tests the getText method of ForceCrystalService.
   */
  public function testGetText(): void {
    $text = $this->yourExtensionService->getText();

    // Assert that the text is sanitized and contains no HTML tags.
    $this->assertEquals('This is bold text.', $text);
  }

}
