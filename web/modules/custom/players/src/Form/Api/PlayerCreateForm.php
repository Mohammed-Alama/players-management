<?php

namespace Drupal\players\Form\Api;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;

class PlayerCreateForm extends FormBase {


  public function getFormId(): string
  {
    return 'player_create_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    return [];
  }


  public function validateForm(array &$form, FormStateInterface $form_state): void
  {
    $data = $form_state->getValues();

    if (empty($data['full_name'])) {
      $form_state->setErrorByName('full_name', $this->t('Full Name is required.'));
    }

    if (empty($data['position'])) {
      $form_state->setErrorByName('position', $this->t('Position is required.'));
    }else {
      if (!in_array($data['position'], ['setter', 'outside_hitter', 'middle_blocker', 'libero'])) {
        $form_state->setErrorByName('position', $this->t('Invalid position.'));
      }
    }

    if (empty($data['federation'])) {
      $form_state->setErrorByName('federation', $this->t('Federation is required.'));
    } else {
      $federation = Term::load($data['federation']);
      if (!$federation) {
        $form_state->setErrorByName('federation', $this->t('Invalid Federation reference.'));
      }
    }

    if (empty($data['gender'])) {
      $form_state->setErrorByName('gender', $this->t('Gender is required.'));
    }else{
      if (!in_array($data['gender'], ['man', 'women'])){
        $form_state->setErrorByName('gender', $this->t('Invalid gender.'));
      }
    }

    if (empty($data['date_of_birth'])) {
      $form_state->setErrorByName('date_of_birth', $this->t('Date of Birth is required.'));
    }
    if (empty($data['player_image'])){
      $form_state->setErrorByName('player_image', $this->t('Player Image is required.'));
    }else{
      if (!base64_decode($data['player_image'], true)) {
        $form_state->setErrorByName('player_image', $this->t('Invalid image format.'));
      }
    }

    if (empty($data['club'])) {
      $form_state->setErrorByName('club', $this->t('Club is required.'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
  }
}
