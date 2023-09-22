<?php

namespace Drupal\language_country_negotiation\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\language_country_negotiation\PathUtility;
use Drupal\language_country_negotiation\Service\CountryManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Provides fallbacks for the language-country prefix.
 *
 * This is an early manipulation of the request path. It should run before
 * CountryEventSubscriber::setCountry().
 *
 * 1. Valid language-country prefixes are send through.
 * 2. If the country is not resolvable from the language-country prefix,
 *    redirect to the 'international' site without prefix.
 * 3. If the country is resolvable, but the language is not allowed for the
 *    given country, the prefix is 'repaired' with the primary language.
 */
class RequestFallbackEventSubscriber implements EventSubscriberInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The country manager.
   *
   * @var \Drupal\language_country_negotiation\Service\CountryManagerInterface
   */
  protected $countryManager;

  /**
   * The page cache kill switch.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $killSwitch;

  /**
   * Constructs a new RequestFallbackEventSubscriber object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    CountryManagerInterface $country_manager,
    KillSwitch $kill_switch
  ) {
    $this->configFactory = $config_factory;
    $this->countryManager = $country_manager;
    $this->killSwitch = $kill_switch;
  }

  /**
   * Negotiates fallback for the language-country prefix.
   */
  public function negotiateRequestFallback(RequestEvent $event): void {

    if (!$event->isMainRequest()) {
      return;
    }

    $config = $this->configFactory
      ->get('language_country_negotiation.fallbacks');

    if (!$config->get('use_request_fallback')) {
      return;
    }

    $path = $event->getRequest()->getPathInfo();

    // The fallback is only negotiated on paths with valid language-country
    // prefix. Technically, this also includes the negotiated prefix for the
    // admin pages because it is based on the langcode. Other permanent
    // redirects should be handled in the server configuration.
    if (!PathUtility::hasValidPrefix($path)) {
      return;
    }

    $langcode = PathUtility::getLangcodeFromPath($path);
    $country_code = PathUtility::getCountryCodeFromPath($path);

    // If this is a valid language-country combination there is nothing to do.
    if ($this->countryManager->isLanguageAvailable($country_code, $langcode)) {
      return;
    }

    // If the country is not allowed the prefix is removed because it is not
    // possible to resolve a valid language-country combination. Technically,
    // the fallback is no prefix.
    if (!$this->countryManager->isCountryAllowed($country_code)) {
      $this->killSwitch->trigger();
      $path = PathUtility::removeValidPrefixFromPath($path);
      $response = new RedirectResponse($path, 301);
      $event->setResponse($response);
      return;
    }

    // If the country is allowed and the primary language can be resolved, the
    // prefix can be repaired to a valid language-country combination.
    if ($langcode = $this->countryManager->getPrimaryLangcode($country_code)) {
      $this->killSwitch->trigger();
      $new_prefix = '/' . $langcode . '-' . $country_code;
      $path = $new_prefix . rtrim(PathUtility::removeValidPrefixFromPath($path), '/');
      $response = new RedirectResponse($path, 301);
      $event->setResponse($response);
      return;
    }

    // All other cases eventually lead to a 404 response.
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::REQUEST => ['negotiateRequestFallback', 310],
    ];
  }

}
