<?php

namespace Drupal\language_country_negotiation\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\path_alias\AliasManager;
use Drupal\path_alias\AliasRepositoryInterface;
use Drupal\path_alias\AliasWhitelistInterface;

/**
 * Extends the alias manager to consider fallback languages.
 */
class FallbackAliasManager extends AliasManager {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs n FallbackAliasManager service object.
   */
  public function __construct(
    AliasRepositoryInterface $alias_repository,
    AliasWhitelistInterface $whitelist,
    LanguageManagerInterface $language_manager,
    CacheBackendInterface $cache,
    ConfigFactoryInterface $config_factory
  ) {
    parent::__construct($alias_repository, $whitelist, $language_manager, $cache);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getPathByAlias($alias, $langcode = NULL): string {

    $config = $this->configFactory
      ->get('language_country_negotiation.fallbacks');

    if (!$config->get('use_fallback_alias_manager')) {
      return parent::getPathByAlias($alias, $langcode);
    }

    $langcode = $langcode ?: $this->languageManager
      ->getCurrentLanguage(LanguageInterface::TYPE_URL)->getId();

    // Resolve the path via the alias manager. If the noPath property is empty
    // a matching path was found.
    $parent_path = parent::getPathByAlias($alias, $langcode);
    if (empty($this->noPath[$langcode][$alias])) {
      return $parent_path;
    }

    // Search for a matching path for all fallback languages.
    foreach ($this->getFallbackLangcodes($langcode) as $fallback_langcode) {
      if ($path_alias = $this->pathAliasRepository->lookupByAlias($alias, $fallback_langcode)) {
        $this->lookupMap[$fallback_langcode][$path_alias['path']] = $alias;
        unset($this->noPath[$fallback_langcode][$alias]);
        return $path_alias['path'];
      }
    }

    return $parent_path;
  }

  /**
   * {@inheritdoc}
   */
  public function getAliasByPath($path, $langcode = NULL): string {

    $config = $this->configFactory
      ->get('language_country_negotiation.fallbacks');

    if (!$config->get('use_fallback_alias_manager')) {
      return parent::getAliasByPath($path, $langcode);
    }

    $langcode = $langcode ?: $this->languageManager
      ->getCurrentLanguage(LanguageInterface::TYPE_URL)->getId();

    // Resolve the alias via the alias manager. If the noAlias property is empty
    // a matching alias was found.
    $parent_alias = parent::getAliasByPath($path, $langcode);
    if (empty($this->noAlias[$langcode][$path])) {
      return $parent_alias;
    }

    // Search for a matching alias for all fallback languages.
    foreach ($this->getFallbackLangcodes($langcode) as $fallback_langcode) {
      if ($alias = $this->pathAliasRepository->lookupBySystemPath($path, $fallback_langcode)) {
        $this->lookupMap[$fallback_langcode][$path] = $alias['alias'];
        unset($this->noAlias[$fallback_langcode][$path]);
        return $alias['alias'];
      }
    }

    return $parent_alias;
  }

  /**
   * Gets the fallback langcodes list for a given langcode.
   *
   * @param string $langcode
   *   The current langcode.
   *
   * @return array
   *   An array of fallback langcodes.
   */
  protected function getFallbackLangcodes($langcode): array {
    $context = ['langcode' => $langcode, 'operation' => 'path_alias'];
    $fallbacks = $this->languageManager->getFallbackCandidates($context);
    unset($fallbacks[LanguageInterface::LANGCODE_NOT_SPECIFIED]);
    return $fallbacks;
  }

}
