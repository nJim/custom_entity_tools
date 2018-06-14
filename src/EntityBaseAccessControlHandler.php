<?php

namespace Drupal\custom_entity_tools;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\custom_entity_tools\Entity\EntityBaseInterface;

/**
 * Access controller for the EntityBase entity.
 *
 * @see \Drupal\custom_entity_tools\Entity\EntityBase.
 */
class EntityBaseAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    if ($entity instanceof EntityBaseInterface) {

      $is_owner = ($account->id() && $account->id() === $entity->getOwnerId());
      $permKey = $entity->getPermissionsKey();

      switch ($operation) {
        case 'view':
          if (!$entity->isPublished() && $account->hasPermission("view unpublished $permKey entities")) {
            return AccessResult::allowed()->cachePerPermissions()->addCacheableDependency($entity);
          }
          if ($entity->isPublished() && $account->hasPermission("view any $permKey entities")) {
            return AccessResult::allowed()->cachePerPermissions();
          }
          if ($entity->isPublished() && $account->hasPermission("view own $permKey entities") && $is_owner) {
            return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
          }
          break;

        case 'update':
          if ($account->hasPermission("edit any $permKey entities")) {
            return AccessResult::allowed()->cachePerPermissions();
          }
          if ($account->hasPermission("edit own $permKey entities") && $is_owner) {
            return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
          }
          break;

        case 'delete':
          if ($account->hasPermission("delete any $permKey entities")) {
            return AccessResult::allowed()->cachePerPermissions();
          }
          if ($account->hasPermission("delete own $permKey entities") && $is_owner) {
            return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
          }
          break;

      }
    }

    // Unknown operation, follow parent logic.
    return parent::checkAccess($entity, $operation, $account);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $permKey = $context['entity_type_id'];
    if (!empty($entity_bundle)) {
      $permKey .= ' ' . $entity_bundle;
    }
    return AccessResult::allowedIfHasPermission($account, "create $permKey entities")->cachePerPermissions();
  }

}
