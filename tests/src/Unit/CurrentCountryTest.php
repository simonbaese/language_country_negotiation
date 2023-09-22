<?php

namespace Drupal\Tests\language_country_negotiation\Unit;

use Drupal\language_country_negotiation\Service\CurrentCountry;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the language_country_negotiation.current_country service.
 *
 * @coversDefaultClass \Drupal\language_country_negotiation\Service\CurrentCountry
 * @group language_country_negotiation
 */
class CurrentCountryTest extends UnitTestCase {

  /**
   * The language_country_negotiation.curent_country service.
   *
   * @var \Drupal\language_country_negotiation\Service\CurrentCountry
   */
  protected CurrentCountry $currentCountry;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->currentCountry = new CurrentCountry();
  }

  /**
   * Tests the get current country code method.
   *
   * @covers ::getCurrentCountryCode
   */
  public function testGetCurrentCountryCode(): void {

    $this->assertNull($this->currentCountry->getCurrentCountryCode(), 'Service returns NULL because the country is not set.');

    $this->currentCountry->setCountryCode('fr');
    $this->assertEquals('fr', $this->currentCountry->getCurrentCountryCode(), 'Service returns the set country code.');

    $this->currentCountry->setCountryCode('de');
    $this->assertEquals('de', $this->currentCountry->getCurrentCountryCode(), 'Service returns the consecutively set country code.');

    $this->currentCountry->resetCountryCode();
    $this->assertNull($this->currentCountry->getCurrentCountryCode(), 'Service returns NULL because the country was reset.');
  }

}
