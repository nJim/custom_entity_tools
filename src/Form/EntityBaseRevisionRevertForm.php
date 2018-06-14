<?php

namespace Drupal\custom_entity_tools\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\custom_entity_tools\Entity\EntityBaseInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting an EntityBase revision.
 *
 * @ingroup custom_entity_tools
 */
class EntityBaseRevisionRevertForm extends ConfirmFormBase {

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
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Instance of the messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new EntityBaseRevisionRevertForm.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The current route match service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity storage.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(RouteMatchInterface $current_route_match, EntityTypeManagerInterface $entity_type_manager, DateFormatterInterface $date_formatter, MessengerInterface $messenger) {
    $this->currentRoute = $current_route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $this->entityTypeId = $this->currentRoute->getRouteObject()->getOption('_entity_type_id');
    $this->entityStorage = $this->entityTypeManager->getStorage($this->entityTypeId);
    $this->dateFormatter = $date_formatter;
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
    /* @var \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter */
    $dateFormatter = $container->get('date.formatter');
    /* @var \Drupal\Core\Messenger\MessengerInterface $messenger */
    $messenger = $container->get('messenger');
    return new static(
      $currentRouteMatch,
      $entityTypeManager,
      $dateFormatter,
      $messenger
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return "{$this->entityTypeId}_revision_revert_confirm";
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): string {
    return $this->t('Are you sure you want to revert to the revision from %revisionDate?',
      ['%revisionDate' => $this->dateFormatter->format($this->revision->getRevisionCreationTime())]
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
    return $this->t('Revert');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $revision = NULL): array {
    $this->revision = $this->entityStorage->loadRevision($revision);
    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // The revision timestamp will be updated when the revision is saved. Keep
    // the original one for the confirmation message.
    $originalRevisionTimestamp = $this->revision->getRevisionCreationTime();
    $originalRevisionDate = $this->dateFormatter->format($originalRevisionTimestamp);

    $this->revision = $this->prepareRevertedRevision($this->revision, $form_state);
    $this->revision->revision_log = "Copy of the revision from {$originalRevisionDate}.";

    $this->revision->save();

    // Set a log about the reverting of a revision.
    $this->logger('custom_entity_tools')->notice(
      '%entity: reverted %title revision %revision.',
      [
        '%entity' => $this->entityStorage->getEntityType()->getLabel(),
        '%title' => $this->revision->label(),
        '%revision' => $this->revision->getRevisionId(),
      ]
    );

    // Set a message about the reverting of a revision.
    $this->messenger->addMessage($this->t(
      '%entity %title has been reverted to the revision from %revision-date.',
      [
        '%entity' => $this->entityStorage->getEntityType()->getLabel(),
        '%title' => $this->revision->label(),
        '%revision-date' => $this->dateFormatter->format($originalRevisionTimestamp),
      ]
    ));

    // Redirect to the canonical page to view the newly reverted entity.
    $form_state->setRedirect(
      "entity.{$this->entityTypeId}.version_history",
      [$this->entityTypeId => $this->revision->id()]
    );
  }

  /**
   * Prepares a revision to be reverted.
   *
   * @param \Drupal\custom_entity_tools\Entity\EntityBaseInterface $revision
   *   The revision to be reverted.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\custom_entity_tools\Entity\EntityBaseInterface
   *   The prepared revision ready to be stored.
   */
  protected function prepareRevertedRevision(EntityBaseInterface $revision, FormStateInterface $form_state): EntityBaseInterface {
    $revision->setNewRevision();
    $revision->isDefaultRevision(TRUE);
    $revision->setRevisionCreationTime(REQUEST_TIME);
    return $revision;
  }

}
