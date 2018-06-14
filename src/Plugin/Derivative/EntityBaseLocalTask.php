<?php

namespace Drupal\custom_entity_tools\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides default local task definitions EntityBase entities.
 *
 * This deriver expects all links to have the entity type id specified as an
 * option of the base plugin definition. The mymodule.links.tasks.yml file
 * should include code like the sample below:
 *
 * @code
 * entity.entity_id.tasks:
 *   class: Drupal\Core\Menu\LocalTaskDefault
 *   deriver: \Drupal\custom_entity_tools\Plugin\Derivative\EntityBaseLocalTask
 *   options:
 *     _entity_type_id: entity_id
 * @endcode
 */
class EntityBaseLocalTask extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a new class instance.
   *
   * @param string $base_plugin_id
   *   The base plugin ID for the plugin ID.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct($base_plugin_id, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    /* @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
    $entityTypeManager = $container->get('entity_type.manager');
    return new static(
      $base_plugin_id,
      $entityTypeManager
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function getDerivativeDefinitions($base_plugin_definition) {

    $this->derivatives = [];
    if ($entityTypeId = $base_plugin_definition['options']['_entity_type_id']) {
      $entityType = $this->entityTypeManager->getStorage($entityTypeId)->getEntityType();
      $this->derivatives["entity.$entityTypeId.canonical"] = [
        'route_name' => "entity.$entityTypeId.canonical",
        'base_route' => "entity.$entityTypeId.canonical",
        'title' => 'View',
        'weight' => 10,
      ] + $base_plugin_definition;
      $this->derivatives["entity.$entityTypeId.edit_form"] = [
        'route_name' => "entity.$entityTypeId.edit_form",
        'base_route' => "entity.$entityTypeId.canonical",
        'title' => 'Edit',
        'weight' => 20,
      ] + $base_plugin_definition;
      $this->derivatives["entity.$entityTypeId.version_history"] = [
        'route_name' => "entity.$entityTypeId.version_history",
        'base_route' => "entity.$entityTypeId.canonical",
        'title' => 'Revisions',
        'weight' => 30,
      ] + $base_plugin_definition;

      // The collection tab appears when viewing other entity collections.
      $this->derivatives["entity.$entityTypeId.collection"] = [
        'route_name' => "entity.$entityTypeId.collection",
        'base_route' => "system.admin_content",
        'title' => $entityType->getCollectionLabel(),
        'weight' => 40,
      ] + $base_plugin_definition;

      // The settings tab appears with the field_ui tabs. This route does not
      // exist for bundles as each bundle has its own settings.
      if (empty($entityType->getBundleEntityType())) {
        $this->derivatives["$entityTypeId.settings_tab"] = [
          'route_name' => "entity.$entityTypeId.settings",
          'base_route' => "entity.$entityTypeId.settings",
          'title' => 'Settings',
        ] + $base_plugin_definition;
      }
    }

    return $this->derivatives;
  }

}
