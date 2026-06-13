<?php

declare(strict_types=1);

namespace Drupal\force_crystal;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class to manage insertion of text into <noscript> tag.
 */
class ForceCrystalService {

  /**
   * The extension configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $yourExtensionConfig;

  /**
   * Constructs a new ForceCrystalService instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->yourExtensionConfig = $config_factory->get('force_crystal.settings');
  }

  /**
   * Inserts text into <noscript> tag.
   *
   * @return string
   *   The text to be inserted.
   */
  public function getText(): string {
    $text = $this->yourExtensionConfig->get('text');

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
