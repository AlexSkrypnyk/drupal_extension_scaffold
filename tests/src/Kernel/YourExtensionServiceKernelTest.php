<?php

declare(strict_types=1);

namespace Drupal\Tests\your_extension\Kernel;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Group;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the YourExtensionService class.
 *
 * @group your_extension
 */
#[Group('your_extension')]
#[RunTestsInSeparateProcesses]
class YourExtensionServiceKernelTest extends KernelTestBase {

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

    $this->installConfig(['your_extension']);

    $this->config('your_extension.settings')
      ->set('text', '<p>This is <strong>bold</strong> text.</p>')
      ->save();

    $this->yourExtensionService = $this->container->get('your_extension.service');
  }

  /**
   * Tests the getText method of YourExtensionService.
   */
  public function testGetText(): void {
    $text = $this->yourExtensionService->getText();

    // Assert that the text is sanitized and contains no HTML tags.
    $this->assertEquals('This is bold text.', $text);
  }

}
