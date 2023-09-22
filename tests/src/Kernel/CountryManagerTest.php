<?php

namespace Drupal\Tests\language_country_negotiation\Kernel;

use Drupal\language_country_negotiation\Service\CountryManagerInterface;
use Drupal\language_country_negotiation\Service\CurrentCountry;

/**
 * Tests the language_country_negotiation.country_manager service.
 *
 * @coversDefaultClass \Drupal\language_country_negotiation\Service\CountryManager
 * @group language_country_negotiation
 */
class CountryManagerTest extends CountryTestBase {

  /**
   * The language_country_negotiation.country_manager service.
   *
   * @var \Drupal\language_country_negotiation\Service\CountryManagerInterface
   */
  protected CountryManagerInterface $countryManager;

  /**
   * The language_country_negotiation.current_country service.
   *
   * @var \Drupal\language_country_negotiation\Service\CurrentCountry
   */
  protected CurrentCountry $currentCountry;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->countryManager = $this->container->get('language_country_negotiation.country_manager');
    $this->currentCountry = $this->container->get('language_country_negotiation.current_country');
  }

  /**
   * Tests the get current country code proxy method.
   *
   * @covers ::getCurrentCountryCode
   */
  public function testGetCurrentCountryCode(): void {

    $this->assertNull($this->countryManager->getCurrentCountryCode(), 'Service returns null because the country is not set.');

    $this->currentCountry->setCountryCode('fr');
    $this->assertEquals('fr', $this->countryManager->getCurrentCountryCode(), 'Service returns the country code proxied from the current country service.');

    $this->currentCountry->setCountryCode('de');
    $this->assertEquals('de', $this->currentCountry->getCurrentCountryCode(), 'Service returns the consecutively set country code proxied from the current country service.');

    $this->currentCountry->resetCountryCode();
    $this->assertNull($this->currentCountry->getCurrentCountryCode(), 'Service returns NULL because the current country was reset.');
  }

  /**
   * Tests checking if a country is allowed.
   *
   * @param array $terms
   *   The term data as expected by ::setUpTerms().
   * @param string|null $country_code
   *   The country code to check.
   * @param bool $allowed
   *   The expected result.
   *
   * @covers ::isCountryAllowed
   * @dataProvider isCountryAllowedProvider
   */
  public function testIsCountryAllowed(array $terms, ?string $country_code, bool $allowed): void {
    $this->setUpTerms($terms);
    $this->assertEquals($allowed, $this->countryManager->isCountryAllowed($country_code));
  }

  /**
   * Data provider for testIsCountryAllowed.
   *
   * @return array
   *   A list of test scenarios.
   */
  public function isCountryAllowedProvider(): array {
    $cases['disallowed'] = [
      'terms' => $this->exampleWithContinents,
      'country_code' => 'be',
      'allowed' => TRUE,
    ];

    $cases['unset'] = [
      'terms' => $this->exampleWithContinents,
      'country_code' => NULL,
      'allowed' => FALSE,
    ];

    $cases['allowed'] = [
      'terms' => $this->exampleWithContinents,
      'country_code' => 'es',
      'allowed' => FALSE,
    ];

    $cases['obscure'] = [
      'terms' => $this->exampleWithContinents,
      'country_code' => 'xxy',
      'allowed' => FALSE,
    ];

    return $cases;
  }

  /**
   * Tests getting country languages from the taxonomy.
   *
   * @param array $terms
   *   The term data as expected by ::setUpTerms().
   * @param array $country_codes
   *   An array of country codes.
   * @param array $langcodes
   *   An array of langcodes.
   * @param array $expected
   *   The expected result.
   * @param string $message
   *   A message for the assertion.
   *
   * @covers ::isLanguageAvailable
   * @dataProvider isLanguageAvailableProvider
   */
  public function testIsLanguageAvailable(array $terms, array $country_codes, array $langcodes, array $expected, string $message): void {
    $this->setUpTerms($terms);
    do {
      $results[] = $this->countryManager
        ->isLanguageAvailable(array_shift($country_codes), array_shift($langcodes));
    } while (!empty($country_codes) && !empty($langcodes));
    $this->assertEquals($expected, $results, $message);
  }

  /**
   * Data provider for testIsLanguageAvailable.
   *
   * @return array
   *   A list of test scenarios.
   */
  public function isLanguageAvailableProvider(): array {

    $country_codes = [
      'gbr', 'us', 'de', 'xx', '', 'be', NULL,
      'be', NULL, 'kr', 'eg', 'ma', 'cn', 'und',
    ];
    $langcodes = [
      'xxy', 'en', 'it', 'yy', '', 'nl', NULL,
      NULL, 'en', 'en', 'en', 'fr', 'zh-hans', 'en',
    ];

    $cases['no-terms'] = [
      'terms' => [],
      'country_codes' => $country_codes,
      'langcodes' => $langcodes,
      'expected' => [
        FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE,
        FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE,
      ],
      'message' => 'All combinations are FALSE when no terms exist.',
    ];

    $cases['without-continents'] = [
      'terms' => $this->exampleWithoutContinents,
      'country_codes' => $country_codes,
      'langcodes' => $langcodes,
      'expected' => [
        FALSE, TRUE, FALSE, FALSE, FALSE, TRUE, FALSE,
        FALSE, FALSE, TRUE, FALSE, TRUE, FALSE, FALSE,
      ],
      'message' => 'Language-country combinations are validated correctly even when no continent terms exist.',
    ];

    $cases['with-continents'] = [
      'terms' => $this->exampleWithContinents,
      'country_codes' => $country_codes,
      'langcodes' => $langcodes,
      'expected' => [
        FALSE, TRUE, FALSE, FALSE, FALSE, TRUE, FALSE,
        FALSE, FALSE, TRUE, FALSE, FALSE, FALSE, FALSE,
      ],
      'message' => 'Language-country combinations are validated correctly when continent terms exist.',
    ];

    return $cases;
  }

  /**
   * Tests getting the primary language for a given country.
   *
   * @param array $terms
   *   The term data as expected by ::setUpTerms().
   * @param array $country_codes
   *   An array of country codes.
   * @param array $expected
   *   The expected result.
   * @param string $message
   *   A message for the assertion.
   *
   * @covers ::getPrimaryLangcode
   * @dataProvider getPrimaryLangcodeProvider
   */
  public function testGetPrimaryLangcode(array $terms, array $country_codes, array $expected, string $message): void {
    $this->setUpTerms($terms);
    do {
      $results[] = $this->countryManager
        ->getPrimaryLangcode(array_shift($country_codes));
    } while (!empty($country_codes));
    $this->assertEquals($expected, $results, $message);
  }

  /**
   * Data provider for testGetCountryTaxLabels.
   *
   * @return array
   *   A list of test scenarios.
   */
  public function getPrimaryLangcodeProvider(): array {

    $country_codes = [
      'de', 'be', 'us', 'kr', 'ma', 'eg',
      'cn', 'xx', 'xxy', NULL, '', 'und',
    ];

    $cases['no-terms'] = [
      'terms' => [],
      'country_codes' => $country_codes,
      'expected' => [
        NULL, NULL, NULL, NULL, NULL, NULL,
        NULL, NULL, NULL, NULL, NULL, NULL,
      ],
      'message' => 'Primary language is evaluated correctly when no terms exist.',
    ];

    $cases['without-continents'] = [
      'terms' => $this->exampleWithoutContinents,
      'country_codes' => $country_codes,
      'expected' => [
        'de', 'nl', 'en', 'en', 'fr', NULL,
        NULL, NULL, NULL, NULL, NULL, NULL,
      ],
      'message' => 'Primary language is evaluated correctly even when no continent terms exist.',
    ];

    $cases['with-continents'] = [
      'terms' => $this->exampleWithContinents,
      'country_codes' => $country_codes,
      'expected' => [
        'de', 'nl', 'en', 'en', NULL, NULL,
        NULL, NULL, NULL, NULL, NULL, NULL,
      ],
      'message' => 'Primary language is evaluated correctly when continent terms exist.',
    ];

    return $cases;
  }

}
