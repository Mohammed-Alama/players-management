<?php

namespace Drupal\players\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class PlayersSettingsForm extends ConfigFormBase
{

  /**
   * @inheritDoc
   */
  protected function getEditableConfigNames()
  {
    return ['players.settings'];
  }

  /**
   * @inheritDoc
   */
  public function getFormId()
  {
    return 'players_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $config = $this->config('players.settings');

    $form['emails'] = [
      '#type' =>'textfield',
      '#title' => $this->t('Emails'),
      '#default_value' => $config->get('emails'),
    ];

    $form['max_number_of_players'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of players'),
      '#default_value' => $config->get('max_number_of_players'),
    ];

    $form['default_image'] = [
      '#type' => 'file',
      '#title' => $this->t('Default Image'),
      '#default_value' => $config->get('default_image'),
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    parent::submitForm($form, $form_state);
    $config = $this->config('players.settings');

    $config->set('emails', $form_state->getValue('emails'));
    $config->set('max_number_of_players', $form_state->getValue('max_number_of_players'));
    $config->set('default_image', $form_state->getValue('default_image'));
    $config->save();
  }
}
