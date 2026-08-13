<?php

declare(strict_types=1);

namespace Drupal\your_extension;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides the sanitized text configured for the your_extension module.
 */
class YourExtensionService {

  /**
   * Constructs a new YourExtensionService instance.
   */
  public function __construct(
    /**
     * The config factory.
     */
    protected ConfigFactoryInterface $configFactory,
  ) {
  }

  /**
   * Returns the configured text with HTML tags stripped.
   *
   * @return string
   *   The 'text' value of 'your_extension.settings', sanitized.
   */
  public function getText(): string {
    $text = $this->configFactory->get('your_extension.settings')->get('text');

    return static::sanitize($text);
  }

  /**
   * Sanitizes text.
   *
   * @param string $string
   *   The string to be cleaned.
   *
   * @return string
   *   The sanitized string.
   */
  public static function sanitize(string $string): string {
    return strip_tags($string);
  }

}
