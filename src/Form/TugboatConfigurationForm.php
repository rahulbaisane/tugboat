<?php

namespace Drupal\tugboat\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class TugboatConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tugboat_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'tugboat.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('tugboat.settings');
    $form['tugboat_executable_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tugboat Executable Path'),
      '#description' => t('The Tugboat executable binary file location on the server or relative to the Backdrop installation. This file is downloadable from <a href="@dashboard2.tugboat.qa">https://dashboard2.tugboat.qa/downloads</a>.', array('@dashboard2.tugboat.qa' => 'https://dashboard2.tugboat.qa/downloads')),
      '#default_value' => $config->get('tugboat_executable_path'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('tugboat.settings')
      ->set('tugboat_executable_path', $form_state->getValue('tugboat_executable_path'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
