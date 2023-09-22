<?php

namespace Drupal\language_country_negotiation\Service;

/**
 * Provides service to manage country related tasks.
 */
class CountryManager implements CountryManagerInterface {

  /**
   * The country repository.
   *
   * @var \Drupal\language_country_negotiation\Service\CountryRepositoryInterface
   */
  protected $countryRepository;

  /**
   * The current country service.
   *
   * @var \Drupal\language_country_negotiation\Service\CurrentCountryInterface
   */
  protected $currentCountry;

  /**
   * Constructs a new CountryManager service object.
   */
  public function __construct(
    CountryRepositoryInterface $country_repository,
    CurrentCountry $current_country
  ) {
    $this->countryRepository = $country_repository;
    $this->currentCountry = $current_country;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentCountryCode(): ?string {
    return $this->currentCountry->getCurrentCountryCode();
  }

  /**
   * {@inheritdoc}
   */
  public function isCountryAllowed(?string $country_code): bool {
    if ($country_code === NULL) {
      return FALSE;
    }
    $country_list = $this->countryRepository->getCountries();
    return array_key_exists(strtolower($country_code), $country_list);
  }

  /**
   * {@inheritdoc}
   */
  public function isLanguageAvailable(?string $country_code, ?string $langcode): bool {
    if (empty($country_code) && empty($langcode)) {
      return FALSE;
    }
    $country_langcodes = $this->countryRepository->getCountryLanguages();
    return array_key_exists($country_code, $country_langcodes) &&
      in_array($langcode, $country_langcodes[$country_code], TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getPrimaryLangcode(?string $country_code): ?string {
    if ($country_code === NULL) {
      return NULL;
    }
    $country_languages = $this->countryRepository->getCountryLanguages();
    $langcodes = $country_languages[$country_code] ?? [];
    return reset($langcodes) ?: NULL;
  }

}
