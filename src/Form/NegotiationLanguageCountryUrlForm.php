<?php

namespace Drupal\language_country_negotiation\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\language_country_negotiation\Service\CountryRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure the URL language-country negotiation method for this site.
 *
 * @internal
 */
class NegotiationLanguageCountryUrlForm extends ConfigFormBase {

  /**
   * The country repository.
   *
   * @var \Drupal\language_country_negotiation\Service\CountryRepositoryInterface
   */
  protected $countryRepository;

  /**
   * Constructs a new NegotiationUrlForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\language_country_negotiation\Service\CountryRepositoryInterface $country_repository
   *   The country repository.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    CountryRepositoryInterface $country_repository
  ) {
    parent::__construct($config_factory);
    $this->countryRepository = $country_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('language_country_negotiation.country_repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'language_country_negotiation_configure_url_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['language_country_negotiation.negotiation'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $config = $this->config('language_country_negotiation.negotiation');

    $form['prefix_pattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prefix pattern'),
      '#description' => $this->t('Not used, yet.'),
      '#default_value' => '',
      '#disabled' => TRUE,
    ];

    $form['prefixes'] = [
      '#type' => 'details',
      '#tree' => TRUE,
      '#title' => $this->t('Language-country path prefixes'),
      '#open' => TRUE,
      '#description' => $this->t('The path prefixes are computed based on the provided country taxonomy terms, taking into account the combination of the country code and the specific language codes allowed for each individual country. The following lists shows the computed prefixes for each country. Please edit the taxonomy terms in the country vocabulary to adjust the allowed language-country combinations.'),
    ];

    $countries = $this->countryRepository->getCountries();
    $country_languages = $this->countryRepository->getCountryLanguages();

    foreach ($country_languages as $country_code => $languages) {
      $prefixes = [];
      foreach ($languages as $langcode) {
        $prefixes[] = $langcode . '-' . $country_code;
      }
      $form['prefixes'][$country_code] = [
        '#type' => 'markup',
        '#markup' => '<h6>' . $countries[$country_code] . '</h6><p>' . implode(', ', $prefixes) . '</p>',
      ];
    }

    $form['exclude_admin_pages'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude admin pages'),
      '#description' => $this->t('Activate to exclude all admin pages from the language-country negotiation. Not used, yet.'),
      '#default_value' => $config->get('exclude_admin_pages') ?? TRUE,
      '#disabled' => TRUE,
    ];

    $form['strict_negotiation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Strict negotiation'),
      '#description' => $this->t('Activate to enable strict negotiation which does not allow an international state (without country). Not used, yet.'),
      '#default_value' => $config->get('strict_negotiation') ?? FALSE,
      '#disabled' => TRUE,
    ];

    $form_state->setRedirect('language.negotiation');

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {

    $this->config('language_country_negotiation.negotiation')
      ->set('prefix_pattern', $form_state->getValue('prefix_pattern'))
      ->set('exclude_admin_pages', $form_state->getValue('exclude_admin_pages'))
      ->set('strict_negotiation', $form_state->getValue('strict_negotiation'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
