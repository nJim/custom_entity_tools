<?php

namespace Drupal\custom_entity_tools\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides default local actions definitions EntityBase entities.
 *
 * This deriver expects all links to have the entity type id specified as an
 * option of the base plugin definition. The mymodule.links.action.yml file
 * should include code like the sample below:
 *
 * @code
 * entity.entity_id.actions:
 *   class: Drupal\Core\Menu\LocalActionDefault
 *   deriver: \Drupal\custom_entity_tools\Plugin\Derivative\EntityBaseLocalAction
 *   options:
 *     _entity_type_id: entity_id
 * @endcode
 */
class EntityBaseLocalAction extends DeriverBase implements ContainerDeriverInterface {

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
   * Create action links (buttons) to appear on top of select admin pages.
   */
  public function getDerivativeDefinitions($base_plugin_definition) {

    $links = [];
    if ($entityTypeId = $base_plugin_definition['options']['_entity_type_id']) {
      $entityType = $this->entityTypeManager->getStorage($entityTypeId)->getEntityType();
      $label = $entityType->getLabel();

      // Place a button on top of the 'Add Bundle Type' form. This page does
      // not exist for entities without bundles.
      $bundleEntityTypeId = $entityType->getBundleEntityType();
      if (!empty($bundleEntityTypeId)) {
        $links["entity.$bundleEntityTypeId.add_form"] = [
          'title' => "Add $label Type",
          'appears_on' => ["entity.$bundleEntityTypeId.collection"],
          'route_name' => "entity.$bundleEntityTypeId.add_form",
        ] + $base_plugin_definition;
      }

      // Place a button on top of the 'Add Entity' form. This route is called
      // the 'add_page' for entities with bundles or the 'add_form' when the
      // entity does not have bundles.
      $routeKey = !empty($bundleEntityTypeId) ? 'add_page' : 'add_form';
      $links["entity.$entityTypeId.add_form"] = [
        'title' => "Add $label",
        'appears_on' => ["entity.$entityTypeId.collection"],
        'route_name' => "entity.$entityTypeId.$routeKey",
      ] + $base_plugin_definition;
    }

    return $links;
  }

}
