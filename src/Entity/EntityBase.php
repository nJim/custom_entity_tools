<?php

namespace Drupal\custom_entity_tools\Entity;

use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\UserInterface;

/**
 * Provides an abstract EntityBase for defining custom entities.
 *
 * Review implementations of this abstract for boilerplate annotations:
 *
 * @ingroup custom_entity_tools
 */
abstract class EntityBase extends EditorialContentEntityBase implements EntityBaseInterface {

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly, make the entity owner the
    // revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Name'))
      ->setDescription(new TranslatableMarkup('The name of the entity.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 1024)
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Authored by'))
      ->setDescription(new TranslatableMarkup('The user ID of author of the entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\custom_entity_tools\Entity\EntityBase::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Publishing status'))
      ->setDescription(new TranslatableMarkup('A boolean indicating whether the entity is published.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 120,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Authored on'))
      ->setDescription(new TranslatableMarkup('The time that the entity was created.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ]);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'))
      ->setDescription(new TranslatableMarkup('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * Default value callback for 'user_id' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId(): array {
    return [\Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner(): UserInterface {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid): EntityBaseInterface {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId(): ?int {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name): EntityBaseInterface {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime(): int {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp): EntityBaseInterface {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account): EntityBaseInterface {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished(): bool {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published = NULL): EntityBaseInterface {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isBundle(): bool {
    $bundleEntityType = $this->getEntityType()->getBundleEntityType();
    return isset($bundleEntityType);
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissionsKey(): string {
    $key = (string) $this->getEntityType()->id();
    if ($this->isBundle()) {
      $key .= ' ' . (string) $this->bundle();
    }
    return $key;
  }

}
