<?php

declare(strict_types=1);

namespace Drupal\force_crystal;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class to manage insertion of text into <noscript> tag.
 */
class ForceCrystalService {

  /**
   * Constructs a new ForceCrystalService instance.
   */
  public function __construct(
    /**
     * The config factory.
     */
    protected ConfigFactoryInterface $configFactory,
  ) {
  }

  /**
   * Inserts text into <noscript> tag.
   *
   * @return string
   *   The text to be inserted.
   */
  public function getText(): string {
    $text = $this->configFactory->get('force_crystal.settings')->get('text');

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
