<?php

namespace Drupal\custom_entity_tools\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EntityBaseSettingsForm.
 *
 * An interface for entity-specific settings.
 *
 * The settings form class is defined in the handlers->form->settings section of
 * the entity annotation. The HtmlRouteProvider define the route for this form
 * as entity.{$entity_type_id}.settings.
 *
 * @ingroup custom_entity_tools
 */
abstract class EntityBaseSettingsForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  abstract public function getFormId(): string;

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Empty implementation of the abstract submit class.
  }

  /**
   * Defines the settings form for EntityBase entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    return $form;
  }

}
