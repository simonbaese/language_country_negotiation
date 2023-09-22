<?php

namespace Drupal\language_country_negotiation\EventSubscriber;

use Drupal\language_country_negotiation\PathUtility;
use Drupal\language_country_negotiation\Service\CountryManagerInterface;
use Drupal\language_country_negotiation\Service\CurrentCountry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Sets the current country based on the language-country prefix.
 *
 * The current country in the current country service is set, if the resolved
 * country is allowed. This can be understood as an initialization for the
 * current country service. This happens early in the request handling because
 * other services, language negotiation plugins and alias management depend on
 * the current country.
 *
 * This should be the only place where the current country is set.
 */
class CountryEventSubscriber implements EventSubscriberInterface {

  /**
   * The country manager.
   *
   * @var \Drupal\language_country_negotiation\Service\CountryManagerInterface
   */
  protected $countryManager;

  /**
   * The current country service.
   *
   * @var \Drupal\language_country_negotiation\Service\CurrentCountryInterface
   */
  protected $currentCountry;

  /**
   * Constructs a new CountryEventSubscriber object.
   */
  public function __construct(
    CountryManagerInterface $country_manager,
    CurrentCountry $current_country
  ) {
    $this->countryManager = $country_manager;
    $this->currentCountry = $current_country;
  }

  /**
   * Sets the a valid current country code in the current country service.
   */
  public function setCountryCode(RequestEvent $event): void {

    if (!$event->isMainRequest()) {
      return;
    }

    $path = $event->getRequest()->getPathInfo();
    $country_code = PathUtility::getCountryCodeFromPath($path);
    if ($this->countryManager->isCountryAllowed($country_code)) {
      $this->currentCountry->setCountryCode($country_code);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::REQUEST => ['setCountryCode', 305],
    ];
  }

}
