<?php

namespace Drupal\custom_entity_tools\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Provides an abstract EntityBundleBaseType for defining entity bundles.
 *
 * Review implementations of this abstract for boilerplate annotations:
 *
 * @ingroup custom_entity_tools
 */
abstract class EntityBundleBaseType extends ConfigEntityBundleBase implements EntityBundleBaseTypeInterface {

  /**
   * The entity type ID.
   *
   * The type ID is a primary key for the bundle type. Make sure to set this
   * as an entity_key of "id" = "id" in the type annotation.
   *
   * @var string
   */
  protected $id;

  /**
   * The entity type label.
   *
   * The type label is a primary key for the bundle type. Make sure to set this
   * as an entity_key of "label" = "label" in the type annotation.
   *
   * @var string
   */
  protected $label;

}
