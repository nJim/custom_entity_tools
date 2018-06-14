<?php

namespace Drupal\custom_entity_tools;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\custom_entity_tools\Entity\EntityBaseInterface;

/**
 * Defines the storage handler class for EntityBase entities.
 *
 * This extends the base storage class, adding required special handling for
 * revision and editorial displays of entities.
 *
 * @ingroup custom_entity_tools
 */
interface EntityBaseStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of revision IDs for a specific entity.
   *
   * @param \Drupal\custom_entity_tools\Entity\EntityBaseInterface $entity
   *   The EntityBase entity.
   *
   * @return int[]
   *   Entity revision IDs (in ascending order).
   */
  public function revisionIds(EntityBaseInterface $entity): array;

  /**
   * Gets a list of revision IDs having a given user as the entity author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity for a given author.
   *
   * @return int[]
   *   EntityBase revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account): array;

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\custom_entity_tools\Entity\EntityBaseInterface $entity
   *   The EntityBase entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(EntityBaseInterface $entity): int;

  /**
   * Unsets the language for all EntityBase entities with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
