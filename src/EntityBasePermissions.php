<?php

namespace Drupal\custom_entity_tools;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides dynamic permissions for entities of different types.
 *
 * Entities that extend this class must reference the concrete class in the
 * annotation block for that entity. This class may be extended for entities
 * with and without bundles.
 */
abstract class EntityBasePermissions {

  /**
   * Get the entity type id.
   *
   * @return string
   *   The machine-readable id of the entity.
   */
  abstract protected function getEntityTypeId(): string;

  /**
   * Returns an array of node type permissions.
   *
   * Returns a standard set of type permissions for any entity based on the
   * abstract EntityBase type. This method includes different handling of
   * entities with bundles (though configuration entity interfaces) and without
   * bundles (though content entity interfaces).
   *
   * For example: The 'User' entity type has no bundles, so permissions would
   * be set only for the 'User' entity. But the 'Node' entity type has a
   * bundle type of 'NodeType'. All bundles would be loaded for this bundle
   * type and permissions set for each one (page, article, story, etc.)
   *
   * @return array
   *   The entity permissions.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function getTypePermissions(): array {
    $perms = [];
    // Load an instance of the entity type specified in the concrete class.
    $entityType = \Drupal::entityTypeManager()->getStorage($this->getEntityTypeId())->getEntityType();
    $perms += $this->buildEntityAdminPermission($entityType);

    // Returns the name of the bundle type or NULL.
    $entityTypeBundle = $entityType->getBundleEntityType();
    if (!empty($entityTypeBundle)) {
      /* @var \Drupal\custom_entity_tools\Entity\EntityBundleBase $bundleType */
      $bundleType = \Drupal::entityTypeManager()->getStorage($entityTypeBundle)->getEntityType()->getOriginalClass();
      /* @var \Drupal\custom_entity_tools\Entity\EntityBundleBaseType $entityType */
      foreach ($bundleType::loadMultiple() as $entityType) {
        $bundleOf = $entityType->getEntityType()->getBundleOf();
        $key = $bundleOf . " " . $entityType->id();
        $perms += $this->buildEntityPermissions($key, $entityType->label());
      }
    }
    else {
      $perms += $this->buildEntityPermissions($entityType->id(), $entityType->getLabel());
    }

    return $perms;
  }

  /**
   * Returns the the admin permission for the given entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   The entity type interface for the given entity.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildEntityAdminPermission(EntityTypeInterface $entityType): array {
    $entityTypeId = $entityType->id();
    $entityLabel = $entityType->getLabel();
    return [
      "administer $entityTypeId entities" => [
        'title' => "$entityLabel: Administer content",
        'description' => "Allow to access the administration form to configure entities.",
        'restrict access' => TRUE,
      ],
    ];
  }

  /**
   * Returns a list of permissions for a given entity type or bundle.
   *
   * @param string $key
   *   Part of the machine-readable key for each entity permission.
   * @param string $label
   *   Part of the human-readable label for each entity permission.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildEntityPermissions(string $key, string $label): array {
    return [
      "create $key entities" => [
        'title' => "$label: Create new content",
      ],
      "view unpublished $key entities" => [
        'title' => "$label: View unpublished content",
      ],
      "view any $key entities" => [
        'title' => "$label: View any content",
      ],
      "view own $key entities" => [
        'title' => "$label: View own content",
      ],
      "edit any $key entities" => [
        'title' => "$label: Edit any content",
      ],
      "edit own $key entities" => [
        'title' => "$label: Edit own content",
      ],
      "delete any $key entities" => [
        'title' => "$label: Delete any content",
      ],
      "delete own $key entities" => [
        'title' => "$label: Delete own content",
      ],
      "view $key revisions" => [
        'title' => "$label: View revisions",
        'description' => "To view a revision, you also need permission to view the content item.",
      ],
      "revert $key revisions" => [
        'title' => "$label: Revert revisions",
        'description' => "To revert a revision, you also need permission to edit the content item.",
      ],
      "delete $key revisions" => [
        'title' => "$label: Delete revisions",
        'description' => "To delete a revision, you also need permission to delete the content item.",
      ],
    ];

  }

}
