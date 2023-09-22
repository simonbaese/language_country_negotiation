<?php

namespace Drupal\language_country_negotiation\Service;

/**
 * Interface for the service country.
 */
interface CountryManagerInterface {

  /**
   * Proxies the current country code by calling the current country service.
   *
   * @return string|null
   *   The current country code or NULL if not set.
   */
  public function getCurrentCountryCode(): ?string;

  /**
   * Checks whether a country code exists in the country list.
   */
  public function isCountryAllowed(?string $country_code): bool;

  /**
   * Checks whether the given country language combination is valid.
   */
  public function isLanguageAvailable(?string $country_code, ?string $langcode): bool;

  /**
   * Gets the primary langcode of a given country.
   */
  public function getPrimaryLangcode(?string $country_code): ?string;

}
