<?php

namespace Drupal\language_country_negotiation\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure the language-country fallbacks for this site.
 */
class LanguageCountryFallbacksForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'language_country_fallbacks_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['language_country_negotiation.fallbacks'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $config = $this->config('language_country_negotiation.fallbacks');

    $form['fallback_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Fallback options'),
      '#open' => TRUE,
      'use_fallback_alias_manager' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Fallback alias manager'),
        '#description' => $this->t('Use the fallback alias manager to resolve correct path for the current language even if the user navigates to a path that relates to another language.'),
        '#default_value' => $config->get('use_fallback_alias_manager') ?? TRUE,
      ],
      'use_request_fallback' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Request fallback'),
        '#description' => $this->t('Attempt to redirect the user to a valid language-country prefix if the language-country combination does not exist or is not valid.'),
        '#default_value' => $config->get('use_request_fallback') ?? TRUE,
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('language_country_negotiation.fallbacks')
      ->set('use_fallback_alias_manager', $form_state->getValue('use_fallback_alias_manager'))
      ->set('use_request_fallback', $form_state->getValue('use_request_fallback'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
