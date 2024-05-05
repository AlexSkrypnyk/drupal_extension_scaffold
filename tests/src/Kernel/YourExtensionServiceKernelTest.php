<?php

declare(strict_types=1);

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\your_extension\YourExtensionService;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests the YourExtensionService class.
 *
 * @group your_extension
 */
class YourExtensionServiceKernelTest extends KernelTestBase {

  use ProphecyTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['your_extension'];

  /**
   * The YourExtensionService instance.
   *
   * @var \Drupal\your_extension\YourExtensionService
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
    $config_factory->get('your_extension.settings')
      ->willReturn($your_config_config->reveal());

    $this->yourExtensionService = new YourExtensionService($config_factory->reveal());
  }

  /**
   * Tests the getText method of YourExtensionService.
   */
  public function testGetText() {
    // Get the text using the service.
    $text = $this->yourExtensionService->getText();

    // Assert that the text is sanitized and contains no HTML tags.
    $this->assertEquals('This is bold text.', $text);
  }

}
