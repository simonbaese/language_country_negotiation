<?php

namespace Drupal\language_country_negotiation;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\language_country_negotiation\Service\FallbackAliasManager;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Modifies the alias manager service.
 */
class LanguageCountryNegotiationServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {

    if ($container->hasDefinition('path_alias.manager')) {
      $definition = $container->getDefinition('path_alias.manager');
      $definition->setClass(FallbackAliasManager::class);
      $definition->addArgument(new Reference('config.factory'));
    }
  }

}
