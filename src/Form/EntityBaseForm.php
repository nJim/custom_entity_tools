<?php

namespace Drupal\custom_entity_tools\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;

/**
 * Form controller for EntityBase add and edit forms.
 *
 * @ingroup custom_entity_tools
 */
class EntityBaseForm extends ContentEntityForm {

  /**
   * Instance of the messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $status = parent::save($form, $form_state);
    $entity = $this->entity;
    $entityTypeId = $entity->getEntityTypeId();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger->addMessage($this->t('Created the %label %type.', [
          '%label' => $entity->label(),
          '%type' => $entityTypeId,
        ]));
        break;

      default:
        $this->messenger->addMessage($this->t('Saved the %label %type.', [
          '%label' => $entity->label(),
          '%type' => $entityTypeId,
        ]));
    }

    $form_state->setRedirect("entity.$entityTypeId.canonical", [$entityTypeId => $entity->id()]);
  }

  /**
   * Class constructor.
   *
   * Note EntityManagerInterface is deprecated but required by parent class.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time interface service.
   */
  public function __construct(MessengerInterface $messenger, EntityManagerInterface $entity_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL) {
    $this->messenger = $messenger;
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /* @var \Drupal\Core\Messenger\MessengerInterface $messenger */
    $messenger = $container->get('messenger');
    /* @var \Drupal\Core\Entity\EntityManagerInterface $entityManager */
    $entityManager = $container->get('entity.manager');
    return new static(
      $messenger,
      $entityManager,
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time')
    );
  }

}
