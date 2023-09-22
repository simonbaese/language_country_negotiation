<?php

namespace Drupal\Tests\language_country_negotiation\Kernel;

use Drupal\language_country_negotiation\Service\CountryRepositoryInterface;

/**
 * Tests the language_country_negotiation.country_repository service.
 *
 * @coversDefaultClass \Drupal\language_country_negotiation\Service\CountryRepository
 * @group language_country_negotiation
 */
class CountryRepositoryTest extends CountryTestBase {

  /**
   * The language_country_negotiation.country_repository service.
   *
   * @var \Drupal\language_country_negotiation\Service\CountryRepositoryInterface
   */
  protected CountryRepositoryInterface $countryRepository;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->countryRepository = $this->container->get('language_country_negotiation.country_repository');
  }

  /**
   * Tests getting countries from the taxonomy.
   *
   * @param array $terms
   *   The term data as expected by ::setUpTerms().
   * @param array $expected
   *   The expected result.
   * @param string $message
   *   A message for the assertion.
   *
   * @covers ::getCountries
   * @dataProvider getCountriesProvider
   */
  public function testGetCountries(array $terms, array $expected, string $message): void {
    $this->setUpTerms($terms);
    $this->assertEquals($expected, $this->countryRepository->getCountries(), $message);
  }

  /**
   * Data provider for testGetCountries.
   *
   * @return array
   *   A list of test scenarios.
   */
  public function getCountriesProvider(): array {
    $cases['no-terms'] = [
      'terms' => [],
      'expected' => [],
      'message' => 'Nothing is returned when no terms exist.',
    ];

    $cases['without-continents'] = [
      'terms' => $this->exampleWithoutContinents,
      'expected' => [
        'de' => 'Germany',
        'be' => 'Belgium',
        'us' => 'United States of America',
        'kr' => 'South Korea',
        'ma' => 'Morocco',
      ],
      'message' => 'Countries are returned even when no continent terms exist.',
    ];

    $cases['with-continents'] = [
      'terms' => $this->exampleWithContinents,
      'expected' => [
        'de' => 'Germany',
        'be' => 'Belgium',
        'us' => 'United States of America',
        'kr' => 'South Korea',
      ],
      'message' => 'Countries are returned when continent terms exist.',
    ];

    return $cases;
  }

  /**
   * Tests getting countries from the taxonomy by continent.
   *
   * @param array $terms
   *   The term data as expected by ::setUpTerms().
   * @param array $expected
   *   The expected result.
   * @param string $message
   *   A message for the assertion.
   *
   * @covers ::getCountriesByContinent
   * @dataProvider getCountriesByContinentProvider
   */
  public function testGetCountriesByContinent(array $terms, array $expected, string $message): void {
    $this->setUpTerms($terms);
    $this->assertEquals($expected, $this->countryRepository->getCountriesByContinent(), $message);
  }

  /**
   * Data provider for testGetCountriesByContinent.
   *
   * @return array
   *   A list of test scenarios.
   */
  public function getCountriesByContinentProvider(): array {
    $cases['no-terms'] = [
      'terms' => [],
      'expected' => [],
      'message' => 'Nothing is returned when no terms exist.',
    ];

    $cases['without-continents'] = [
      'terms' => $this->exampleWithoutContinents,
      'expected' => [],
      'message' => 'Nothing is returned when only country terms exist.',
    ];

    $cases['with-continents'] = [
      'terms' => $this->exampleWithContinents,
      'expected' => [
        1 => [
          'label' => 'Europe',
          'countries' => [
            'de' => 'Germany',
            'be' => 'Belgium',
          ],
        ],
        4 => [
          'label' => 'North America',
          'countries' => [
            'us' => 'United States of America',
          ],
        ],
        6 => [
          'label' => 'Asia',
          'countries' => [
            'kr' => 'South Korea',
          ],
        ],
      ],
      'message' => 'Countries and continents are returned when continent terms exist.',
    ];

    return $cases;
  }

  /**
   * Tests getting country languages from the taxonomy.
   *
   * @param array $terms
   *   The term data as expected by ::setUpTerms().
   * @param array $expected
   *   The expected result.
   * @param string $message
   *   A message for the assertion.
   *
   * @covers ::getCountryLanguages
   * @dataProvider getCountryLanguagesProvider
   */
  public function testGetCountryLanguages(array $terms, array $expected, string $message): void {
    $this->setUpTerms($terms);
    $this->assertSame($expected, $this->countryRepository->getCountryLanguages(), $message);
  }

  /**
   * Data provider for testGetCountryLanguages.
   *
   * @return array
   *   A list of test scenarios.
   */
  public function getCountryLanguagesProvider(): array {
    $cases['no-terms'] = [
      'terms' => [],
      'expected' => [],
      'message' => 'Nothing is returned when no terms exist.',
    ];

    $cases['without-continents'] = [
      'terms' => $this->exampleWithoutContinents,
      'expected' => [
        'de' => ['de'],
        'be' => ['nl', 'fr', 'de'],
        'us' => ['en'],
        'kr' => ['en'],
        'ma' => ['fr'],
      ],
      'message' => 'Country languages are returned even when no continent terms exist.',
    ];

    $cases['with-continents'] = [
      'terms' => $this->exampleWithContinents,
      'expected' => [
        'de' => ['de'],
        'be' => ['nl', 'fr', 'de'],
        'us' => ['en'],
        'kr' => ['en'],
      ],
      'message' => 'Country languages are returned when continent terms exist.',
    ];

    return $cases;
  }

}
