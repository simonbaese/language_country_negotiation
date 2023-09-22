<?php

namespace Drupal\Tests\language_country_negotiation\Unit;

use Drupal\language_country_negotiation\PathUtility;
use Drupal\Tests\UnitTestCase;

/**
 * Test coverage for the path utility class.
 *
 * @coversDefaultClass \Drupal\language_country_negotiation\PathUtility
 * @group language_country_negotiation
 */
class PathUtilityTest extends UnitTestCase {

  /**
   * A collection of paths with valid prefixes to test.
   *
   * @var array
   */
  protected $pathsWithValidPrefix = [
    'xx-yy',
    '/xx-yy',
    '/xx-yy/',
    'xx-yy/test',
    '/xx-yy/test',
    '/xx-yy/test/',
    'xx-yy/test/sample?foo=1&bar=baz',
    '/xx-yy/test/sample?foo=1&bar=baz',
    'xx-yy/does%&§not!!!matter?foo=bar',
    '/xx-yy/does%&§not!!!matter?foo=bar',
  ];

  /**
   * A collection of paths with invalid prefixes to test.
   *
   * @var array
   */
  protected $pathsWithInvalidPrefix = [
    '',
    '/',
    'xx~yy',
    '/xx~yy',
    '/xx~yy/',
    'xx%00-yy',
    '/xx%00-yy',
    '/xx%00-yy/',
    'xx&ndash;yy',
    '/xx&ndash;yy',
    '/xx&ndash;yy/',
    'foo-yy',
    '/foo-yy',
    '/foo-yy/',
    'xx-bar',
    '/xx-bar',
    '/xx-bar/',
    'foo-bar',
    '/foo-bar',
    '/foo-bar/',
    'foo%bar',
    '/foo%bar',
    '/foo%bar/',
    'foo/bar',
    '/foo/bar',
    '/foo/bar/',
    'foo/bar?baz=1',
    '/foo/bar?baz=1',
    ' xx-yy',
    ' /xx-yy',
    '%20/xx-yy',
    '%20xx-yy',
    '/%20xx-yy',
    '\xx-yy',
    '\xx-yy\foo/bar',
    'x%-yy',
    '/x%-yy',
    '/x%-yy/',
    'xx-%y',
    '/xx-%y',
    '/xx-%y/',
    'x-y',
    '/x-y',
    '/x-y/',
    'foo.bar/xx-yy',
    'www.foo.bar/xx-yy',
    'https://www.foo.bar/xx-yy',
    'https://www.foo.bar/xx-yy?foo=bar',
  ];

  /**
   * Tests the get country code from path method.
   *
   * @covers ::getCountryCodeFromPath
   */
  public function testGetCountryCodeFromPath(): void {
    foreach ($this->pathsWithValidPrefix as $path) {
      $this->assertEquals('yy', PathUtility::getCountryCodeFromPath($path));
    }
    foreach ($this->pathsWithInvalidPrefix as $path) {
      $this->assertNull(PathUtility::getCountryCodeFromPath($path));
    }
  }

  /**
   * Tests the get langcode from path method.
   *
   * @covers ::getLangcodeFromPath
   */
  public function testGetLangcodeFromPath(): void {
    foreach ($this->pathsWithValidPrefix as $path) {
      $this->assertEquals('xx', PathUtility::getLangcodeFromPath($path));
    }
    foreach ($this->pathsWithInvalidPrefix as $path) {
      $this->assertNull(PathUtility::getLangcodeFromPath($path));
    }
  }

  /**
   * Tests the remove valid prefix country code from path method.
   *
   * @covers ::removeValidPrefixFromPath
   */
  public function testRemoveValidPrefixFromPath(): void {
    foreach ($this->pathsWithValidPrefix as $path) {
      $expected = '/' . trim(str_replace('xx-yy', '', $path), '/');
      $this->assertEquals($expected, PathUtility::removeValidPrefixFromPath($path));
    }
    foreach ($this->pathsWithInvalidPrefix as $path) {
      $this->assertEquals($path, PathUtility::removeValidPrefixFromPath($path));
    }
  }

  /**
   * Tests the has valid prefix method.
   *
   * @covers ::hasValidPrefix
   */
  public function testHasValidPrefix(): void {
    foreach ($this->pathsWithValidPrefix as $path) {
      $this->assertTrue(PathUtility::hasValidPrefix($path));
    }
    foreach ($this->pathsWithInvalidPrefix as $path) {
      $this->assertFalse(PathUtility::hasValidPrefix($path));
    }
  }

}
