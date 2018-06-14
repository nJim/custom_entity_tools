<?php

namespace Drupal\custom_entity_tools\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting an EntityBase revision.
 *
 * This form is an updated version of the one implemented by the Node type.
 * But to abstract this out, the concrete entity type id is padded through the
 * defined page route as route option '_entity_type_id'.
 *
 * @ingroup custom_entity_tools
 */
class EntityBaseRevisionDeleteForm extends ConfirmFormBase {

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRoute;

  /**
   * The EntityBase revision.
   *
   * @var \Drupal\custom_entity_tools\Entity\EntityBaseInterface
   */
  protected $revision;

  /**
   * The machine-name of the current entity type.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The definition of the current entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Instance of the messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new EntityBaseRevisionDeleteForm.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The current route match service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(RouteMatchInterface $current_route_match, EntityTypeManagerInterface $entity_type_manager, Connection $connection, MessengerInterface $messenger) {
    $this->currentRoute = $current_route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
    $this->messenger = $messenger;
    $this->entityTypeId = $this->currentRoute->getRouteObject()->getOption('_entity_type_id');
    $this->entityStorage = $this->entityTypeManager->getStorage($this->entityTypeId);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public static function create(ContainerInterface $container) {
    /* @var \Drupal\Core\Routing\RouteMatchInterface $currentRouteMatch */
    $currentRouteMatch = $container->get('current_route_match');
    /* @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
    $entityTypeManager = $container->get('entity_type.manager');
    /* @var \Drupal\Core\Database\Connection $connection */
    $connection = $container->get('database');
    /* @var \Drupal\Core\Messenger\MessengerInterface $messenger */
    $messenger = $container->get('messenger');
    return new static(
      $currentRouteMatch,
      $entityTypeManager,
      $connection,
      $messenger
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return "{$this->entityTypeId}_revision_delete_confirm";
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): string {
    return $this->t(
      'Are you sure you want to delete the revision from %revisionDate?',
      ['%revisionDate' => format_date($this->revision->getRevisionCreationTime())]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url(
      "entity.{$this->entityTypeId}.version_history",
      [$this->entityTypeId => $this->revision->id()]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText(): string {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $revision = NULL) {
    $this->revision = $this->entityStorage->loadRevision($revision);
    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entityStorage->deleteRevision($this->revision->getRevisionId());

    // Set a log about the deletion of a revision.
    $this->logger('custom_entity_tools')->notice(
      '%entity: deleted %title revision %revision.',
      [
        '%entity' => $this->entityStorage->getEntityType()->getLabel(),
        '%title' => $this->revision->label(),
        '%revision' => $this->revision->getRevisionId(),
      ]
    );

    // Set a message about the deletion of a revision.
    $this->messenger->addMessage($this->t(
      '%entity: revision from %revision-date of %title has been deleted.',
      [
        '%entity' => $this->entityStorage->getEntityType()->getLabel(),
        '%revision-date' => format_date($this->revision->getRevisionCreationTime()),
        '%title' => $this->revision->label(),
      ]
    ));

    // Redirect to the revision history page if there is at least 2 revisions.
    // Otherwise redirect to the canonical entity-view page.
    $entityTypeId = $this->entityTypeId;
    if ($this->countRevisionsForEntity($this->revision->id()) > 1) {
      $form_state->setRedirect(
        "entity.$entityTypeId.version_history",
        [$this->entityTypeId => $this->revision->id()]
      );
    }
    else {
      $form_state->setRedirect(
        "entity.$entityTypeId.canonical",
        [$this->entityTypeId => $this->revision->id()]
      );
    }
  }

  /**
   * Count the number of revisions for an entity by id.
   *
   * @param int $id
   *   The id for the entity.
   *
   * @return int
   *   The count of revisions for a given entity.
   */
  protected function countRevisionsForEntity(int $id): int {
    $count = 0;
    if ($revisionDataTable = $this->entityStorage->getEntityType()->getRevisionDataTable()) {
      $query = $this->connection->select($revisionDataTable, 'r');
      $query->addField('r', 'vid');
      $query->condition('id', $id, '=');
      $count = $query->countQuery()->execute()->fetchField();
    }
    return (int) $count;
  }

}
