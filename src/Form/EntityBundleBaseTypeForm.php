<?php

namespace Drupal\custom_entity_tools\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityBundleBaseTypeForm.
 */
class EntityBundleBaseTypeForm extends EntityForm {

  /**
   * Instance of the messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $entityTypeId = $this->entity->getEntityTypeId();
    $entityStorage = $this->entityTypeManager->getStorage($entityTypeId);

    $entity = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#description' => $this->t('Label for the entity type.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => [$entityStorage, 'load'],
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = $entity->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger->addMessage($this->t('Created the %label %type.', [
          '%label' => $entity->label(),
          '%type' => $entity->getEntityTypeId(),
        ]));
        break;

      default:
        $this->messenger->addMessage($this->t('Saved the %label %type.', [
          '%label' => $entity->label(),
          '%type' => $entity->getEntityTypeId(),
        ]));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
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
