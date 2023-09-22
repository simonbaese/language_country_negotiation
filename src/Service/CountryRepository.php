<?php

namespace Drupal\language_country_negotiation\Service;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Utility\Error;
use Drupal\taxonomy\TermInterface;

/**
 * Provides a repository of country-language pairs.
 */
class CountryRepository implements CountryRepositoryInterface {

  /**
   * The full continent data or NULL if none was set yet.
   *
   * @var array|null
   */
  protected $continentData;

  /**
   * The full country data or FALSE if none was set yet.
   *
   * @var array|null
   */
  protected $countryData;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new CountryRepository service object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LanguageManagerInterface $language_manager,
    LoggerChannelInterface $logger
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountries(): array {
    foreach ($this->getCountryData() as $country_code => $data) {
      $list[$country_code] = $data['label'];
    }
    return $list ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCountriesByContinent(): array {
    if ($continent_data = $this->getContinentData()) {
      foreach ($continent_data as $continent_id => $continent_data) {
        $list[$continent_id]['label'] = $continent_data['label'];
      }
      foreach ($this->getCountryData() as $country_code => $country_data) {
        // Skip unpublished continents.
        if (!isset($list[$country_data['parent_id']])) {
          continue;
        }
        $list[$country_data['parent_id']]['countries'][$country_code] = $country_data['label'];
      }
    }
    return $list ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCountryLanguages(): array {
    foreach ($this->getCountryData() as $country_code => $data) {
      $list[$country_code] = $data['languages'];
    }
    return $list ?? [];
  }

  /**
   * Retrieves all of the continent data.
   *
   * @return array
   *   The continent data as an array where the keys are continent IDs and
   *   values are arrays which currently only contain the label.
   */
  protected function getContinentData(): array {

    if (isset($this->continentData)) {
      return $this->continentData;
    }

    try {
      $storage = $this->entityTypeManager
        ->getStorage('taxonomy_term');
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      $variables = Error::decodeException($e);
      $this->logger->error($e->getMessage(), $variables);
      return $this->continentData = [];
    }

    $term_ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('vid', 'country')
      ->condition('status', TRUE)
      ->exists('field_code')
      ->condition('field_code', 'und')
      ->condition('parent', '0', '=')
      ->execute();

    if (empty($term_ids)) {
      return $this->continentData = [];
    }

    $current_langcode = $this->languageManager
      ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();

    foreach ($storage->loadMultiple($term_ids) as $term_id => $country) {
      if ($country->hasTranslation($current_langcode)) {
        $country = $country->getTranslation($current_langcode);
      }
      $this->continentData[$term_id]['label'] = $country->label();
    }

    return $this->continentData;
  }

  /**
   * Retrieves all of the country data.
   *
   * @return array
   *   The country data as an array where the keys are country codes and values
   *   are the label, tax label, parent ID and list of langcodes.
   */
  protected function getCountryData(): array {

    if (isset($this->countryData)) {
      return $this->countryData;
    }

    try {
      $storage = $this->entityTypeManager
        ->getStorage('taxonomy_term');
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      $variables = Error::decodeException($e);
      $this->logger->error($e->getMessage(), $variables);
      return $this->continentData = [];
    }

    $term_ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('vid', 'country')
      ->exists('field_code')
      ->condition('field_code', 'und', '!=')
      ->condition('status', TRUE)
      ->execute();

    if (empty($term_ids)) {
      return $this->countryData = [];
    }

    $current_langcode = $this->languageManager
      ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();

    /** @var \Drupal\language_country_negotiation\Entity\CountryTerm $country */
    foreach ($storage->loadMultiple($term_ids) as $country) {

      $continent = $country->get('parent')->entity;
      if ($continent instanceof TermInterface && !$continent->isPublished()) {
        continue;
      }

      if ($country_code = $country->getCountryCode()) {

        if ($country->hasTranslation($current_langcode)) {
          $country = $country->getTranslation($current_langcode);
        }

        $this->countryData[$country_code]['label'] = $country->label();
        $this->countryData[$country_code]['parent_id'] = $country->getParentId();

        $this->countryData[$country_code]['languages'] = [];
        foreach ($country->getReferencedLanguages() as $language) {
          $this->countryData[$country_code]['languages'][] = $language->getId();
        }
      }
    }

    return $this->countryData;
  }

}
