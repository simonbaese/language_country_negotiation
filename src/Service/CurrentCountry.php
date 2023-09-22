<?php

namespace Drupal\language_country_negotiation\Service;

/**
 * Provides service for country detection.
 */
final class CurrentCountry implements CurrentCountryInterface {

  /**
   * The current country code of the current request.
   *
   * @var string|null
   */
  protected ?string $countryCode = NULL;

  /**
   * {@inheritdoc}
   */
  public function getCurrentCountryCode(): ?string {
    return $this->countryCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setCountryCode(string $country_code): static {
    $this->countryCode = $country_code;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function resetCountryCode(): static {
    $this->countryCode = NULL;
    return $this;
  }

}
