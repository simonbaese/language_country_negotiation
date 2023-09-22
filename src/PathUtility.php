<?php

namespace Drupal\language_country_negotiation;

/**
 * Provides utility methods to parse paths.
 */
class PathUtility {

  /**
   * Prefix country-language pattern.
   */
  public const LANGUAGE_COUNTRY_PATTERN = "/^[a-z]{2}-[a-z]{2}$/";

  /**
   * Gets the country code from a given path.
   *
   * @param string $path
   *   The path to parse.
   *
   * @return string|null
   *   The country code or NULL if not found.
   */
  public static function getCountryCodeFromPath(string $path): ?string {
    if ($prefix = static::getValidPrefix($path)) {
      return substr($prefix, 3, 2);
    }
    return NULL;
  }

  /**
   * Gets the langcode from a given path.
   *
   * @param string $path
   *   The path to parse.
   *
   * @return string|null
   *   The langcode or NULL if not found.
   */
  public static function getLangcodeFromPath(string $path): ?string {
    if ($prefix = static::getValidPrefix($path)) {
      return substr($prefix, 0, 2);
    }
    return NULL;
  }

  /**
   * Removes a valid prefix from the path.
   */
  public static function removeValidPrefixFromPath(string $path): string {
    if (static::hasValidPrefix($path)) {
      $parts = explode('/', trim($path, '/'));
      array_shift($parts);
      $path = '/' . implode('/', $parts);
    }
    return $path;
  }

  /**
   * Checks whether prefix matches pattern.
   */
  public static function hasValidPrefix(string $path): bool {
    return static::getValidPrefix($path) !== FALSE;
  }

  /**
   * Gets the prefix of a given path.
   *
   * Note that the result might be NULL if the path is empty.
   */
  protected static function getValidPrefix(string $path): string|false {
    $path_decoded = urldecode(trim($path, '/'));
    $path_args = explode('/', $path_decoded);
    $prefix = array_shift($path_args);
    if (preg_match(static::LANGUAGE_COUNTRY_PATTERN, $prefix)) {
      return $prefix;
    }
    return FALSE;
  }

}
