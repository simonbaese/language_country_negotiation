<?php

namespace Drupal\language_country_negotiation\Service;

/**
 * Interface for current country service.
 */
interface CurrentCountryInterface {

  /**
   * Gets the current country code by calling a country provider.
   *
   * @return string|null
   *   The current country code or NULL if not set.
   */
  public function getCurrentCountryCode(): ?string;

  /**
   * Sets the country code.
   *
   * Note that the country code is required to be valid. The surface of this
   * service is lean by design. The country code should be validated with the
   * country manager before using this method.
   *
   * @param string $country_code
   *   A valid country code.
   *
   * @return $this
   *   This instance.
   */
  public function setCountryCode(string $country_code): static;

  /**
   * Resets the country code.
   *
   * @return $this
   *   This instance.
   */
  public function resetCountryCode(): static;

}
