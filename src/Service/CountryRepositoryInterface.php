<?php

namespace Drupal\language_country_negotiation\Service;

/**
 * Interface for a country-language repository.
 */
interface CountryRepositoryInterface {

  /**
   * Gets all of the configured countries.
   *
   * @return array
   *   A list of available country names, keyed per country code.
   */
  public function getCountries(): array;

  /**
   * Gets all of the configured countries, grouped by continent.
   *
   * @return array[]
   *   A list of available continents, keyed by ID, where the values are arrays
   *   with the following keys:
   *   - label: The name of the continent.
   *   - countries: The same return value as getCountries.
   */
  public function getCountriesByContinent(): array;

  /**
   * Gets all of the configured languages by country.
   *
   * @return array[]
   *   A list of available language codes, keyed per country code.
   */
  public function getCountryLanguages(): array;

}
