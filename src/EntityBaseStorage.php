<?php

namespace Drupal\custom_entity_tools;

use Drupal\Core\Database\Query\Update;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\custom_entity_tools\Entity\EntityBaseInterface;

/**
 * Defines the storage handler class for EntityBase entities.
 *
 * This extends the base storage class, adding required special handling for
 * entities.
 *
 * @ingroup custom_entity_tools
 */
class EntityBaseStorage extends SqlContentEntityStorage implements EntityBaseStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(EntityBaseInterface $entity): array {
    if ($revisionTable = $this->getRevisionTable()) {
      $query = $this->database->select($revisionTable, 'r');
      $query->addField('r', 'vid');
      $query->condition('id', $entity->id(), '=');
      $query->orderBy('vid', 'ASC');
      return $query->execute()->fetchCol();
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account): array {
    if ($revisionDataTable = $this->getRevisionDataTable()) {
      $query = $this->database->select($revisionDataTable, 'r');
      $query->addField('r', 'vid');
      $query->condition('uid', $account->id(), '=');
      $query->orderBy('vid', 'ASC');
      return $query->execute()->fetchCol();
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(EntityBaseInterface $entity): int {
    if ($revisionDataTable = $this->getRevisionDataTable()) {
      $query = $this->database->select($revisionDataTable, 'r');
      $query->addField('r', 'vid');
      $query->condition('id', $entity->id(), '=');
      $query->condition('default_langcode', 1, '=');
      return $query->countQuery()->execute()->fetchField();
    }
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language): Update {
    return $this->database->update($this->getRevisionTable())
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
