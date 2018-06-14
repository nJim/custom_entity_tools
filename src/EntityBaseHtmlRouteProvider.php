<?php

namespace Drupal\custom_entity_tools;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Provides HTML routes for entities with administrative add/edit/delete pages.
 *
 * @todo: Refactor: This class can be better organized.
 *
 * @see \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class EntityBaseHtmlRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {

    $collection = parent::getRoutes($entity_type);

    $entity_type_id = $entity_type->id();

    if ($settings_form_route = $this->getSettingsFormRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.settings", $settings_form_route);
    }

    if ($revisions_overview_route = $this->getRevisionsOverviewRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.version_history", $revisions_overview_route);
    }

    if ($view_revision_route = $this->getViewRevisionRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.revision", $view_revision_route);
    }

    if ($revert_revision_route = $this->getRevertRevisionRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.revision_revert_confirm", $revert_revision_route);
    }

    if ($delete_revision_route = $this->getDeleteRevisionRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.revision_delete_confirm", $delete_revision_route);
    }

    return $collection;
  }

  /**
   * Gets the settings form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getSettingsFormRoute(EntityTypeInterface $entity_type) {

    // Entities without bundles have a single settings form.
    // Documentation for getHandlerClass is not helpful. Must use comma
    // separated strings to drill down to second level of handlers.
    if (!$entity_type->getBundleEntityType()) {
      $route = new Route("/admin/structure/{$entity_type->id()}/settings");
      $route
        ->setDefaults([
          '_form' => $entity_type->getHandlerClass('form', 'settings'),
          '_title' => "{$entity_type->getLabel()} Settings",
        ])
        ->setRequirement('_permission', $entity_type->getAdminPermission())
        ->setOption('_admin_route', TRUE);

      return $route;
    }
    return NULL;
  }

  /**
   * Gets the revisions overview (version history) route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getRevisionsOverviewRoute(EntityTypeInterface $entityType) {
    if ($entityType->hasLinkTemplate('version-history')) {
      $entityTypeId = $entityType->id();
      $route = new Route($entityType->getLinkTemplate('version-history'));
      $route
        ->setDefaults([
          '_controller' => '\Drupal\custom_entity_tools\Controller\EntityBaseController::revisionOverview',
          '_title' => 'Revisions',
        ])
        ->setRequirements([
          '_permission' => "view $entityTypeId revisions",
        ])
        ->setOptions([
          '_admin_route' => TRUE,
          '_entity_type_id' => $entityTypeId,
        ]);
      if ($this->getEntityTypeIdKeyType($entityType) === 'integer') {
        $route->setRequirement($entityTypeId, '\d+');
      }
      $route->setOption('parameters', [
        $entityTypeId => ['type' => "entity:$entityTypeId"],
      ]);

      return $route;
    }
    return NULL;
  }

  /**
   * Gets the route to view a revision.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getViewRevisionRoute(EntityTypeInterface $entityType) {
    if ($entityType->hasLinkTemplate('revision')) {
      $entityTypeId = $entityType->id();
      $route = new Route($entityType->getLinkTemplate('revision'));
      $route
        ->setDefaults([
          '_controller' => '\Drupal\custom_entity_tools\Controller\EntityBaseController::revisionShow',
          '_title_callback' => '\Drupal\custom_entity_tools\Controller\EntityBaseController::revisionPageTitle',
        ])
        ->setRequirements([
          '_permission' => "view $entityTypeId revisions",
        ])
        ->setOptions([
          '_admin_route' => TRUE,
          '_entity_type_id' => $entityTypeId,
        ]);

      if ($this->getEntityTypeIdKeyType($entityType) === 'integer') {
        $route->setRequirement($entityTypeId, '\d+');
      }

      return $route;
    }
    return NULL;
  }

  /**
   * Get the Revert Revision Route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getRevertRevisionRoute(EntityTypeInterface $entityType) {
    if ($entityType->hasLinkTemplate('revision-revert')) {
      $entityTypeId = $entityType->id();
      $route = new Route($entityType->getLinkTemplate('revision-revert'));
      $route
        ->setDefaults([
          '_form' => '\Drupal\custom_entity_tools\Form\EntityBaseRevisionRevertForm',
          '_title' => 'Revert to earlier revision',
        ])
        ->setRequirements([
          '_permission' => "revert $entityTypeId revisions",
        ])
        ->setOptions([
          '_admin_route' => TRUE,
          '_entity_type_id' => $entityTypeId,
        ]);

      if ($this->getEntityTypeIdKeyType($entityType) === 'integer') {
        $route->setRequirement($entityTypeId, '\d+');
      }

      return $route;
    }
    return NULL;
  }

  /**
   * Get the Delete Revision Route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getDeleteRevisionRoute(EntityTypeInterface $entityType) {
    if ($entityType->hasLinkTemplate('revision-delete')) {
      $entityTypeId = $entityType->id();
      $route = new Route($entityType->getLinkTemplate('revision-delete'));
      $route
        ->setDefaults([
          '_form' => '\Drupal\custom_entity_tools\Form\EntityBaseRevisionDeleteForm',
          '_title' => 'Delete earlier revision',
        ])
        ->setRequirements([
          '_permission' => "delete $entityTypeId revisions",
        ])
        ->setOptions([
          '_admin_route' => TRUE,
          '_entity_type_id' => $entityTypeId,
        ]);

      if ($this->getEntityTypeIdKeyType($entityType) === 'integer') {
        $route->setRequirement($entityTypeId, '\d+');
      }

      return $route;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function getCanonicalRoute(EntityTypeInterface $entity_type) {
    $route = parent::getCanonicalRoute($entity_type);
    return $route;
  }

}
