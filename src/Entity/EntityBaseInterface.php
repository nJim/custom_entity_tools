<?php

namespace Drupal\custom_entity_tools\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining an abstract EntityBase.
 *
 * @ingroup custom_entity_tools
 */
interface EntityBaseInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface, EntityPublishedInterface {

  /**
   * Gets the entity name.
   *
   * @return string
   *   Name of the entity.
   */
  public function getName(): string;

  /**
   * Sets the entity name.
   *
   * @param string $name
   *   The entity name.
   *
   * @return \Drupal\custom_entity_tools\Entity\EntityBaseInterface
   *   The called entity entity.
   */
  public function setName($name): EntityBaseInterface;

  /**
   * Gets the entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the entity.
   */
  public function getCreatedTime(): int;

  /**
   * Sets the entity creation timestamp.
   *
   * @param int $timestamp
   *   The entity creation timestamp.
   *
   * @return \Drupal\custom_entity_tools\Entity\EntityBaseInterface
   *   The called entity entity.
   */
  public function setCreatedTime($timestamp): EntityBaseInterface;

  /**
   * Returns the entity published status indicator.
   *
   * Unpublished entities are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the entity is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a entity.
   *
   * @param bool $published
   *   TRUE to set this entity to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\custom_entity_tools\Entity\EntityBaseInterface
   *   The called entity.
   */
  public function setPublished($published = NULL): EntityBaseInterface;

  /**
   * Gets the entity revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the entity revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\custom_entity_tools\Entity\EntityBaseInterface
   *   The called entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the entity revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the entity revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\custom_entity_tools\Entity\EntityBaseInterface
   *   The called entity.
   */
  public function setRevisionUserId($uid);

  /**
   * Check if the entity is a bundle of an entity type.
   *
   * @return bool
   *   TRUE if the entity is a bundle of an entity type.
   */
  public function isBundle(): bool;

  /**
   * Build a string to represent the entity in permission keys.
   *
   * For entities without bundles, the permissions key is the entityTypeId.
   * For entities with bundles, the permissions key is the concatenation of
   * the entityTypeId and the bundle name.
   *
   * Example: 'user' entities return 'user' since there is no bundles.
   * Example: 'article' entities are a bundle of 'node' and return
   *   the permissions key 'node entities article'.
   *
   * @return string
   *   A string that follows the permissions naming convention for the entity.
   */
  public function getPermissionsKey(): string;

}
