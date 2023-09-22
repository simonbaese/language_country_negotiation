<?php

namespace Drupal\language_country_negotiation\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\language_country_negotiation\Service\CurrentCountry;

/**
 * Defines the CountryCacheContext service, for "per country" caching.
 */
class CountryCacheContext implements CacheContextInterface {

  /**
   * The current country service.
   *
   * @var \Drupal\language_country_negotiation\Service\CurrentCountryInterface
   */
  protected $currentCountry;

  /**
   * Constructs a new CountryCacheContext object.
   */
  public function __construct(
    CurrentCountry $current_country
  ) {
    $this->currentCountry = $current_country;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel(): string {
    return t('Country');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext(): string {
    return $this->currentCountry->getCurrentCountryCode() ?? 'int';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata(): CacheableMetadata {
    return new CacheableMetadata();
  }

}
