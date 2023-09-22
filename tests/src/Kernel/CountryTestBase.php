<?php

namespace Drupal\Tests\language_country_negotiation\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Test base for country services.
 */
abstract class CountryTestBase extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'language_country_negotiation',
    'taxonomy',
  ];

  /**
   * Example term data when there are no continents.
   *
   * @var array
   */
  protected array $exampleWithoutContinents = [
    'germany' => [
      'name' => 'Germany',
      'field_code' => 'de',
      'field_languages' => ['de'],
      'status' => 1,
    ],
    'belgium' => [
      'name' => 'Belgium',
      'field_code' => 'be',
      'field_languages' => ['nl', 'fr', 'de'],
      'status' => 1,
    ],
    'usa' => [
      'name' => 'United States of America',
      'field_code' => 'us',
      'field_languages' => ['en'],
      'status' => 1,
    ],
    'south-korea' => [
      'name' => 'South Korea',
      'field_code' => 'kr',
      'field_languages' => ['en'],
      'status' => 1,
    ],
    'china' => [
      'name' => 'China',
      'field_code' => 'cn',
      'field_languages' => ['zh-hans'],
      'status' => 0,
    ],
    'egypt' => [
      'name' => 'Egypt',
      'field_code' => 'eg',
      'field_languages' => ['en'],
      'status' => 0,
    ],
    'morocco' => [
      'name' => 'Morocco',
      'field_code' => 'ma',
      'field_languages' => ['fr'],
      'status' => 1,
    ],
  ];

  /**
   * Example term data when there are continents.
   *
   * @var array
   */
  protected array $exampleWithContinents = [
    'europe' => [
      'tid' => 1,
      'name' => 'Europe',
      'field_code' => 'und',
      'status' => 1,
    ],
    'germany' => [
      'name' => 'Germany',
      'field_code' => 'de',
      'field_languages' => ['de'],
      'parent' => 1,
      'status' => 1,
    ],
    'belgium' => [
      'name' => 'Belgium',
      'field_code' => 'be',
      'field_languages' => ['nl', 'fr', 'de'],
      'parent' => 1,
      'status' => 1,
    ],
    'north-america' => [
      'tid' => 4,
      'name' => 'North America',
      'field_code' => 'und',
      'status' => 1,
    ],
    'usa' => [
      'name' => 'United States of America',
      'field_code' => 'us',
      'field_languages' => ['en'],
      'parent' => 4,
      'status' => 1,
    ],
    'asia' => [
      'tid' => 6,
      'name' => 'Asia',
      'field_code' => 'und',
      'status' => 1,
    ],
    'south-korea' => [
      'name' => 'South Korea',
      'field_code' => 'kr',
      'field_languages' => ['en'],
      'parent' => 6,
      'status' => 1,
    ],
    'china' => [
      'name' => 'China',
      'field_code' => 'cn',
      'field_languages' => ['zh-hans'],
      'parent' => 6,
      'status' => 0,
    ],
    'africa' => [
      'tid' => 9,
      'name' => 'Africa',
      'field_code' => 'und',
      'status' => 0,
    ],
    'egypt' => [
      'name' => 'Egypt',
      'field_code' => 'eg',
      'field_languages' => ['en'],
      'parent' => 9,
      'status' => 0,
    ],
    'morocco' => [
      'name' => 'Morocco',
      'field_code' => 'ma',
      'field_languages' => ['fr'],
      'parent' => 9,
      'status' => 1,
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->installConfig([
      'language_country_negotiation',
      'language',
      'taxonomy',
    ]);
    $this->installEntitySchema('taxonomy_term');

    // Add additional language for translation. English default language.
    ConfigurableLanguage::createFromLangcode('de')->save();
    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('nl')->save();
    ConfigurableLanguage::createFromLangcode('zh-hans')->save();
  }

  /**
   * Sets up the taxonomy terms.
   *
   * @param array $terms
   *   The taxonomy term values.
   */
  protected function setUpTerms(array $terms): void {
    $storage = $this->entityTypeManager->getStorage('taxonomy_term');
    foreach ($terms as $term) {
      $storage->save($storage->create(['vid' => 'country'] + $term));
    }
  }

}
