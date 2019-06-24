<?php

namespace Drupal\tugboat\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;

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
    $tugboat_token = Settings::get('tugboat_token');
    $masked_token = '';
      if ($tugboat_token) {
      $masked_token =  substr($tugboat_token, 0, 4) . str_repeat("*", strlen($tugboat_token) - 8) . substr($tugboat_token, -4);
    }
    $form['tugboat_token'] = array(
      '#type' => 'item',
      '#title' => t('Tugboat Secret Token'),
      '#markup' => ($tugboat_token ? $masked_token : t('Not found! Must be set in settings.php!')),
      '#description' => t('Provides API access to tugboat.qa. This setting must be stored in settings.php as <code>$conf[\'tugboat_token\']</code>'),
    );
    $form['tugboat_executable_path'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Tugboat Executable Path'),
      '#description' => t('The Tugboat executable binary file location on the server or relative to the Backdrop installation. This file is downloadable from <a href="@dashboard2.tugboat.qa">https://dashboard2.tugboat.qa/downloads</a>.', array('@dashboard2.tugboat.qa' => 'https://dashboard2.tugboat.qa/downloads')),
      '#default_value' => $config->get('tugboat_executable_path'),
    );
    $form['tugboat_repository_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Tugboat Repository ID'),
      '#description' => t('The repository ID as provided by the Tugboat dashboard. A 24-character alphanumeric hash, such as <code>5bdb5c268eabd5000137a87b</code>.'),
      '#default_value' => $config->get('tugboat_repository_id')
    );
    $form['tugboat_repository_base'] = array(
      '#type' => 'textfield',
      '#title' => t('Tugboat Repository Base Name'),
      '#description' => t('The branch, tag, or pull request name from which to base the previews. A preview with a matching name must be specified in the Tugboat dashboard. Usually <code>master</code> is the latest version.'),
      '#default_value' => $config->get('tugboat_repository_base')
    );
    $form['tugboat_sandbox_lifetime'] = array(
      '#type' => 'select',
      '#title' => t('Sandbox lifetime'),
      '#description' => t('The amount of time the Tugboat preview will be available. Previews older than this will automatically be torn down on cron jobs.'),
      '#options' => array(
        '7200' => '2 hours',
        '14400' => '4 hours',
        '28800' =>   '8 hours',
        '86400' => '1 day',
        '172800' =>  '2 days',
        '259200' =>  '3 days',
        '345600' => '4 days',
        '432000' =>  '5 days',
        '518400' =>  '6 days',
        '604800' => '1 week',
        '1209600' => '2 weeks',
      ),
      '#default_value' => $config->get('tugboat_sandbox_lifetime')
    );
    return parent::buildForm($form, $form_state);
  }


/**
 * Validate handler for tugboat_admin_settings().
 */
public function validateForm(array &$form, FormStateInterface $form_state) {
  //$executable_path = $form_state['values']['tugboat_executable_path'];
  $executable_path = $form_state->getValue('tugboat_executable_path');
  if (!is_file($executable_path)) {
    $form_state->setErrorByName('tugboat_executable_path', $this->t('No file found at the provided path.'));
    return;
  }
  elseif (!is_executable($executable_path)) {
    $form_state->setErrorByName('tugboat_executable_path', $this->t('The Tugboat CLI binary was found, but it is not executable.'));
    return;
  }
  $repo = $form_state->getValue('tugboat_repository_id');
  //$branch = $form_state['values']['repository_base'];
  $data = array();
  $error_string = '';
  $success = _tugboat_execute("find '$repo'", $data, $error_string, $executable_path);
  if (!$success) {
    if ($error_string) {
      $form_state->setErrorByName('tugboat_repository_id', t('The provided repository ID was not found. Tugboat returned the response: @error', array('@error' =>  $error_string)));
    }
    else {
      $form_state->setErrorByName('tugboat_repository_id', t('Tugboat returned a response that was not understood.'));
    }
  }

}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('tugboat.settings')
      ->set('tugboat_executable_path', $form_state->getValue('tugboat_executable_path'))
      ->save();
    $this->config('tugboat.settings')
      ->set('tugboat_repository_id', $form_state->getValue('tugboat_repository_id'))
      ->save();
    $this->config('tugboat.settings')
      ->set('tugboat_repository_base', $form_state->getValue('tugboat_repository_base'))
      ->save();
    $this->config('tugboat.settings')
      ->set('tugboat_sandbox_lifetime', $form_state->getValue('tugboat_sandbox_lifetime'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
