<?php

namespace Drupal\language_country_negotiation\Entity;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * A bundle class for country terms.
 */
final class CountryTerm extends Term {

  public const VOCABULARY = 'country';
  public const FIELD_COUNTRY_CODE = 'field_code';
  public const FIELD_LANGUAGES = 'field_languages';

  /**
   * Gets the country code of the country term.
   */
  public function getCountryCode(): string {
    if ($this->hasField(self::FIELD_COUNTRY_CODE)) {
      return strtolower($this->get(self::FIELD_COUNTRY_CODE)->value);
    }
    return '';
  }

  /**
   * Gets the languages referenced by the country term.
   *
   * @return \Drupal\Core\Language\LanguageInterface[]
   *   An array of languages.
   */
  public function getReferencedLanguages(): array {
    if ($this->hasField(self::FIELD_LANGUAGES)) {
      $field_languages = $this->get(self::FIELD_LANGUAGES);
      if ($field_languages instanceof EntityReferenceFieldItemListInterface) {
        /** @var \Drupal\Core\Language\LanguageInterface[] $languages */
        $languages = $field_languages->referencedEntities();
        return $languages;
      }
    }
    return [];
  }

  /**
   * Gets the ID of the parent if applicable.
   */
  public function getParentId(): ?string {
    return $this->get('parent')->target_id;
  }

}
