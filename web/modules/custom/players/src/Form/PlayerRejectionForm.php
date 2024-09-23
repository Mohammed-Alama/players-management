<?php

namespace Drupal\players\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class PlayerRejectionForm extends FormBase {

  public function getFormId() {
    return 'player_rejection_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $player = null, $competition = null) {
    $playerEntity = \Drupal::entityTypeManager()->getStorage('node')->load($player);
    $competitionEntity = \Drupal::entityTypeManager()->getStorage('node')->load($competition);
    $competition_name = $competitionEntity->getTitle();

    $form['player_id'] = [
      '#type' => 'hidden',
      '#value' => $player,
    ];

    $form['federation_id'] = [
      '#type' => 'hidden',
      '#value' => $playerEntity->get('field_federation')->target_id,
    ];

    $form['competition_id'] = [
      '#type' => 'hidden',
      '#value' => $competition,
    ];
    $form['player'] = [
      '#type' => 'textfield',
      '#value' => $playerEntity->getTitle(),
      '#disabled' => TRUE,
    ];

    $form['competition'] = [
      '#type' => 'textfield',
      '#value' => $competition_name,
      '#disabled' => TRUE,
    ];

    $form['rejection_reason'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Rejection Reason'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reject Player'),
      '#ajax' => [
        'callback' => '::ajaxSubmit',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // The actual submission is handled in the ajaxSubmit method
  }

  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    $player_id = $form_state->getValue('player_id');
    $competition_id = $form_state->getValue('competition_id');
    $federation_id = $form_state->getValue('federation_id');
    $reason = $form_state->getValue('rejection_reason');

    $query = \Drupal::entityQuery('node')
      ->condition('type', 'player_approval')
      ->condition('field_approval_player', $player_id)
      ->condition('field_approval_competition', $competition_id)
      ->accessCheck(FALSE);
    $approval_ids = $query->execute();

    $response = new AjaxResponse();

    if (!empty($approval_ids)) {
      $approval = Node::load(reset($approval_ids));
      $approval->set('field_approval_status', 'rejected');
      $approval->set('field_approval_rejection_reason', $reason);
      $approval->save();

      $message = $this->t('Player has been rejected.');
      $response->addCommand(new MessageCommand($message, null, ['type' => 'status']));
    } else {
      $message = $this->t('Player approval not found.');
      $response->addCommand(new MessageCommand($message, null, ['type' => 'error']));
    }
    $url = Url::fromRoute('players.federation_competition_players', [
      'competition_id' => $competition_id,
      'federation_id' => $federation_id,
    ]);

    $response->addCommand(new RedirectCommand($url->toString()));

    return $response;
  }
}
