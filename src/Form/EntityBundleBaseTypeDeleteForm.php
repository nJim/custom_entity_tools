<?php

namespace Drupal\custom_entity_tools\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to delete EntityBundleBase type entities.
 */
class EntityBundleBaseTypeDeleteForm extends EntityConfirmFormBase {

  /**
   * Instance of the messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $label = $this->entity->label();
    return "Are you sure you want to delete $label?";
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $bundleTypeId = $this->entity->getEntityTypeId();
    return new Url("entity.$bundleTypeId.collection");
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();

    $this->messenger->addMessage($this->t('Content %type: deleted %label.', [
      '%type' => $this->entity->bundle(),
      '%label' => $this->entity->label(),
    ]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /* @var \Drupal\Core\Messenger\MessengerInterface $messenger */
    $messenger = $container->get('messenger');
    return new static(
      $messenger
    );
  }

}
