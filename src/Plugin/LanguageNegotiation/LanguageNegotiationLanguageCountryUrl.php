<?php

namespace Drupal\language_country_negotiation\Plugin\LanguageNegotiation;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\language_country_negotiation\PathUtility;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\NoConfigurationException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Class for identifying language via URL language-country prefix.
 *
 * @LanguageNegotiation(
 *   id = \Drupal\language_country_negotiation\Plugin\LanguageNegotiation\LanguageNegotiationLanguageCountryUrl::METHOD_ID,
 *   types = {
 *     \Drupal\Core\Language\LanguageInterface::TYPE_INTERFACE,
 *     \Drupal\Core\Language\LanguageInterface::TYPE_CONTENT,
 *     \Drupal\Core\Language\LanguageInterface::TYPE_URL
 *   },
 *   weight = -7,
 *   name = @Translation("Language-country URL"),
 *   description = @Translation("Language from the Language-Country URL (Path prefix)."),
 *   config_route_name = "language_country_negotiation.language_country_url"
 * )
 */
class LanguageNegotiationLanguageCountryUrl extends LanguageNegotiationUrl implements ContainerFactoryPluginInterface {

  /**
   * The language negotiation method id.
   */
  public const METHOD_ID = 'language-country-url';

  /**
   * The country repository.
   *
   * @var \Drupal\language_country_negotiation\Service\CountryRepositoryInterface
   */
  protected $countryRepository;

  /**
   * The country repository.
   *
   * @var \Drupal\language_country_negotiation\Service\CountryManagerInterface
   */
  protected $countryManager;

  /**
   * The router used as a URL matcher.
   *
   * @var \Symfony\Component\Routing\Matcher\UrlMatcherInterface
   */
  protected $urlMatcher;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, ...$defaults) {
    $instance = new static(...$defaults);
    $instance->countryRepository = $container->get('language_country_negotiation.country_repository');
    $instance->countryManager = $container->get('language_country_negotiation.country_manager');
    $instance->urlMatcher = $container->get('router.no_access_checks');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL): ?string {

    $negotiated_langcode = NULL;

    // @phpstan-ignore-next-line
    if ($request && $this->languageManager) {
      $languages = $this->languageManager->getLanguages();
      $config = $this->config->get('language.negotiation')->get('url');

      $path = $request->getPathInfo();
      $langcode = PathUtility::getLangcodeFromPath($path);

      // End search prematurely if langcode can not be resolved from path. The
      // language may be negotiated by subsequent negotiation plugins.
      if ($langcode === NULL) {
        return NULL;
      }

      // Search prefix within activated languages.
      $negotiated_language = FALSE;
      foreach ($languages as $language) {
        if (isset($config['prefixes'][$language->getId()]) && $language->getId() === $langcode) {
          $negotiated_language = $language;
          break;
        }
      }

      if ($negotiated_language) {
        $negotiated_langcode = $negotiated_language->getId();
      }
    }

    return $negotiated_langcode;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request): string {

    $langcode = PathUtility::getLangcodeFromPath($path);
    $country_code = PathUtility::getCountryCodeFromPath($path);

    // Rebuild path with the language-country prefix removed.
    if ($this->countryManager->isLanguageAvailable($country_code, $langcode)) {
      $path = PathUtility::removeValidPrefixFromPath($path);
    }

    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL): string {

    // Admin paths shall use the standard language path prefix without country
    // information.
    if ($this->isAdminPath($path)) {
      return $path;
    }

    $languages = array_flip(array_keys($this->languageManager->getLanguages()));

    // Language can be passed as an option, or we go for current URL language.
    if (!isset($options['language'])) {
      $language_url = $this->languageManager
        ->getCurrentLanguage(LanguageInterface::TYPE_URL);
      $options['language'] = $language_url;
    }

    // Allow only added languages here.
    elseif (!is_object($options['language']) || !isset($languages[$options['language']->getId()])) {
      return $path;
    }

    $config = $this->config->get('language.negotiation')->get('url');
    if (is_object($options['language']) && isset($config['prefixes'][$options['language']->getId()])) {

      // The country code may be passed as an option. Otherwise, the current
      // country is used.
      $country_code = $options['country_code'] ??
        $this->countryManager->getCurrentCountryCode();
      $langcode = $options['language']->getId();

      if (!empty($country_code) && $this->countryManager->isLanguageAvailable($country_code, $langcode)) {
        $options['prefix'] = $langcode . '-' . $country_code . '/';
      }

      if ($bubbleable_metadata) {
        $bubbleable_metadata->addCacheContexts([
          'languages:' . LanguageInterface::TYPE_URL,
          'current_country',
        ]);
      }
    }

    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguageSwitchLinks(Request $request, $type, Url $url): array {

    $links = parent::getLanguageSwitchLinks($request, $type, $url);

    // Only resolve links, when language-country links are relevant.
    if (!$country_code = $this->countryManager->getCurrentCountryCode()) {
      return $links;
    }

    // The languages are sorted by the weight set in the country taxonomy. This
    // should be reflected when getting the language switcher links.
    if ($country_languages = $this->countryRepository->getCountryLanguages()[$country_code] ?? NULL) {
      foreach ($country_languages as $langcode) {
        if ($link = $links[$langcode] ?? NULL) {
          $sorted_links[$langcode] = $link;
        }
      }
    }

    return $sorted_links ?? [];
  }

  /**
   * Determines whether the given path is an admin one.
   *
   * @param string $path
   *   The path to check.
   *
   * @return bool
   *   Returns TRUE if the path is an admin one, otherwise FALSE.
   */
  protected function isAdminPath(string $path): bool {
    try {
      $route = $this->urlMatcher->match($path);
      return isset($route['_route_object']) &&
        $route['_route_object']->getOption('_admin_route');
    }
    catch (NoConfigurationException | MethodNotAllowedException | ResourceNotFoundException) {
      return FALSE;
    }
  }

}
