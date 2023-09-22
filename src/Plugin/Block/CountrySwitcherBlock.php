<?php

namespace Drupal\language_country_negotiation\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Http\RequestStack;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\language_country_negotiation\Service\CountryManagerInterface;
use Drupal\language_country_negotiation\Service\CountryRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a country switcher block.
 *
 * @Block(
 *   id = "language_country_negotiation_country_switcher",
 *   admin_label= @Translation("Country Switcher")
 * )
 */
class CountrySwitcherBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The country manager.
   *
   * @var \Drupal\language_country_negotiation\Service\CountryManagerInterface
   */
  protected $countryManager;

  /**
   * The country repository.
   *
   * @var \Drupal\language_country_negotiation\Service\CountryRepositoryInterface
   */
  protected $countryRepository;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The request stack.
   *
   * @var \Drupal\Core\Http\RequestStack
   */
  protected $requestStack;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * Constructs a new CountrySwitcherBlock object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    CountryManagerInterface $country_manager,
    CountryRepositoryInterface $country_repository,
    LanguageManagerInterface $language_manager,
    RequestStack $request_stack,
    PathMatcherInterface $path_matcher,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->countryManager = $country_manager;
    $this->countryRepository = $country_repository;
    $this->languageManager = $language_manager;
    $this->requestStack = $request_stack;
    $this->pathMatcher = $path_matcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_country_negotiation.country_manager'),
      $container->get('language_country_negotiation.country_repository'),
      $container->get('language_manager'),
      $container->get('request_stack'),
      $container->get('path.matcher'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {

    $current_url = $this->pathMatcher->isFrontPage() ? Url::fromRoute('<front>') : Url::fromRoute('<current>');
    $query = $this->requestStack->getCurrentRequest()->query->all();

    $current_langcode = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_URL)->getId();
    $native_languages = $this->languageManager->getNativeLanguages();

    $current_country_code = $this->countryManager->getCurrentCountryCode();
    $countries = $this->countryRepository->getCountries();

    // Maybe move to negotiation plugin.
    // Build list of country links.
    $links = [];
    foreach ($countries as $country_code => $country_name) {

      // Remember current country name.
      if ($country_code === $current_country_code) {
        $current_country_name = $country_name;
      }

      // Resolve target langcode. Either the current language is allowed in
      // the target country or the primary language in the target is used.
      if ($this->countryManager->isLanguageAvailable($country_code, $current_langcode)) {
        $langcode = $current_langcode;
      }
      else {
        $langcode = $this->countryManager->getPrimaryLangcode($country_code);
      }

      // Select the target language object from the native languages.
      if (!$language = $native_languages[$langcode] ?? NULL) {
        continue;
      }

      // Resolve the target URL for this country by passing the country code
      // as an option. The country code option is resolved in the outbound
      // path processing of the language country negotiation plugin.
      $url = clone $current_url;
      $url->setOptions([
        'language' => $language,
        'country_code' => $country_code,
        'query' => $query,
        'absolute' => TRUE,
      ]);
      $country_url = $url->toString();

      // Generate country url based on current route.
      $links['countries'][$country_code] = [
        'url' => $country_url,
        'name' => $country_name,
        'code' => $country_code,
        'icon' => [
          'name' => $country_code,
        ],
      ];
    }

    $current_country = [
      'code' => $current_country_code,
      'country' => $current_country_name ?? $this->t('International'),
    ];

    return [
      '#theme' => 'country_switcher_block',
      '#links' => $links,
      '#current_country' => $current_country,
      '#id' => HTML::getUniqueId('country-switcher-block'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    return Cache::mergeTags(parent::getCacheTags(), [
      'taxonomy_term_list:country',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return Cache::mergeContexts(parent::getCacheContexts(), [
      'url.path',
      'current_country',
    ]);
  }

}
