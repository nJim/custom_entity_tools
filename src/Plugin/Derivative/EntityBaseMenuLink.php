<?php

namespace Drupal\custom_entity_tools\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides default menu link definitions EntityBase entities.
 *
 * @todo: The bundle collection form link is not working. See commented code.
 *
 * This deriver expects all links to have the entity type id specified as an
 * option of the base plugin definition. The mymodule.links.menu.yml file
 * should include code like the sample below:
 *
 * @code
 * entity.entity_id.editorial:
 *   class: Drupal\Core\Menu\MenuLinkDefault
 *   deriver: \Drupal\custom_entity_tools\Plugin\Derivative\EntityBaseMenuLink
 *   menu_name: admin
 *   options:
 *     _entity_type_id: entity_id
 * @endcode
 */
class EntityBaseMenuLink extends DeriverBase implements ContainerDeriverInterface {

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
   * Returns the definition of all derivatives of the menu link plugin.
   *
   * There is a lot of lingo to describe drupal entities and bundles. Use the
   * following comment as a cheat sheet of expected values for the aside entity:
   *   $entityTypeId = 'aside'
   *   $label = 'Aside'
   *   $plural = 'Asides
   *   $bundleEntityTypeId = 'aside_type'
   *   $bundleLabel = 'Badge'
   *   $bundleId = 'badge'
   *
   * @param mixed $base_plugin_definition
   *   The definition of the base plugin from which the derivative plugin
   *   is derived. It is maybe an entire object or just some array, depending
   *   on the discovery mechanism.
   *
   * @return array
   *   The full definition array of the derivative plugin, typically a merge of
   *   $base_plugin_definition with extra derivative-specific information. NULL
   *   if the derivative doesn't exist.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function getDerivativeDefinitions($base_plugin_definition) {

    $links = [];
    if ($entityTypeId = $base_plugin_definition['options']['_entity_type_id']) {
      $entityType = $this->entityTypeManager->getStorage($entityTypeId)->getEntityType();
      $label = $entityType->getLabel();
      $plural = $entityType->getCollectionLabel();

      // The 'Manage Entities" Collection form appears in the 'Content' section.
      $links["entity.$entityTypeId.collection"] = [
        'title' => "Manage $plural",
        'description' => "Manage $plural.",
        'route_name' => "entity.$entityTypeId.collection",
        'parent' => 'system.admin_content',
        'weight' => 100,
      ] + $base_plugin_definition;

      $bundleEntityTypeId = $entityType->getBundleEntityType();
      if (!empty($bundleEntityTypeId)) {
        // Entities with bundles include a collection page for managing bundles.
        // Each bundle has an add form nested under the entity collection form.
        $bundles = $this->entityTypeManager->getStorage($bundleEntityTypeId)->loadMultiple();
        foreach ($bundles as $bundle) {
          $bundleLabel = $bundle->label();
          $bundleId = $bundle->id();
          $links["entity.$bundleId.add"] = [
            'title' => "Add $bundleLabel",
            'description' => "Add a new $bundleLabel.",
            'route_name' => "entity.$entityTypeId.add_form",
            'route_parameters' => [$bundleEntityTypeId => $bundleId],
            'parent' => "entity.$entityTypeId.editorial:entity.$entityTypeId.collection",
          ] + $base_plugin_definition;
        }
      }
      else {
        // Entities without bundles have a settings page and include a single
        // add_form that appears as a child of the entity collection form.
        $links["entity.$entityTypeId.add"] = [
          'title' => "Add $label",
          'description' => "Add a new $label.",
          'route_name' => "entity.$entityTypeId.add_form",
          'parent' => "entity.$entityTypeId.editorial:entity.$entityTypeId.collection",
        ] + $base_plugin_definition;
        $links["entity.$entityTypeId.settings"] = [
          'title' => "$label Settings",
          'description' => 'Create and manage fields, forms, and display settings.',
          'route_name' => "entity.$entityTypeId.settings",
          'parent' => 'system.admin_structure',
          'weight' => 100,
        ] + $base_plugin_definition;
      }
    }

    return $links;
  }

}
