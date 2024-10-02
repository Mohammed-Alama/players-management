<?php

declare(strict_types=1);

namespace Drupal\players\Form;

use Drupal;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a Players form.
 */
class PlayerRegistrationForm extends FormBase
{

  public function getFormId()
  {
    return 'player_registration_form';
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function buildForm(array $form, FormStateInterface $form_state, $competition = null)
  {
    $request = \Drupal::request();
    $player_id = $request->query->get('player_id');
    $player = null;

    if (!empty($player_id)) {
      $player = \Drupal::entityTypeManager()->getStorage('node')->load($player_id);
    }
    $form['competition_id'] = [
      '#type' => 'hidden',
      '#value' => $competition->id(),
    ];

    $form['player_search'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Search player'),
      '#target_type' => 'node',
      '#selection_settings' => ['target_bundles' => ['player']],
      '#ajax' => [
        'callback' => '::playerSearchCallback',
        'event' => 'autocompleteclose change',
        'wrapper' => 'player-details',
      ],
      '#default_value' => $player,
    ];

    $form['player_details'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'player-details'],
    ];



    $form['player_details']['full_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Full name'),
      '#required' => TRUE,
    ];

    $form['player_details']['date_of_birth'] = [
      '#type' => 'date',
      '#title' => $this->t('Date of Birth'),
      '#required' => TRUE,
      '#date_year_range' => '-100:0',
    ];

    $form['player_details']['gender'] = [
      '#type' => 'select',
      '#title' => $this->t('Gender'),
      '#options' => [
        'man' => $this->t('Man'),
        'women' => $this->t('Women'),
      ],
      '#required' => TRUE,
    ];

    $form['player_details']['position'] = [
      '#type' => 'select',
      '#title' => $this->t('Position'),
      '#options' => $this->getPositionOptions(),
      '#required' => TRUE,
    ];

    $form['player_details']['federation'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Federation'),
      '#disabled' => TRUE,
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => ['target_bundles' => ['federation']],
      '#required' => TRUE,
      '#default_value' => $this->getCurrentUserFederation() ? \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($this->getCurrentUserFederation()) : NULL,
      '#ajax' => [
        'callback' => '::updateClubField',
        'wrapper' => 'club-wrapper',
        'event' => 'change',
      ],
    ];

    $federation_id = $form_state->getValue('federation');
    $club_options = $this->getClubOptions($federation_id);

    $form['player_details']['club'] = [
      '#type' => 'select',
      '#title' => $this->t('Club'),
      '#required' => TRUE,
      '#prefix' => '<div id="club-wrapper">',
      '#suffix' => '</div>',
      '#options' => $club_options,
      '#empty_option' => $this->t('- Select a club -'),
      '#value' => $form_state->getValue('club'),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Register Player'),
    ];


    // Trigger the AJAX callback if player_id is present
    if ($player_id) {
      $form_state->setValue('player_search', $player_id);
      $form['player_search']['#disabled'] = TRUE;
      $this->playerSearchCallback($form, $form_state);

    }

    return $form;
  }

  public function updateClubField(array &$form, FormStateInterface $form_state)
  {
    $federation_id = $form_state->getValue('federation');
    $club_options = $this->getClubOptions($federation_id);
    $form['player_details']['club']['#options'] = $club_options;

    $current_club = $form_state->getValue('club');
    if (!isset($club_options[$current_club])) {
      $form_state->setValue('club', '');
      $form['player_details']['club']['#value'] = '';
    }

    return $form['player_details']['club'];
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  private function getClubOptions($federation_id): array
  {
    if (!$federation_id) {
      return [];
    }
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'club')
      ->condition('field_club_federation', $federation_id)
      ->accessCheck(FALSE)
      ->sort('title');
    $club_ids = $query->execute();

    $clubs = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($club_ids);
    $options = [];
    foreach ($clubs as $club) {
      $options[$club->id()] = $club->label();
    }

    return $options;
  }

  public function validateForm(array &$form, FormStateInterface $form_state): void
  {
    parent::validateForm($form, $form_state);

    $federation_id = $form_state->getValue('federation');
    $club_id = $form_state->getValue('club');

    \Drupal::logger('player_registration')
      ->notice('Club value:{validateForm} ' . print_r($form_state->getValue('club'), TRUE));
    if ($federation_id && $club_id) {
      $club = \Drupal::entityTypeManager()->getStorage('node')->load($club_id);
      if ($club && $club->get('field_club_federation')->target_id != $federation_id) {
        $form_state->setErrorByName('club', $this->t('The selected club does not belong to the chosen federation.'));
      }
    }

    $dateOfBirth = new DrupalDateTime($form_state->getValue('date_of_birth'));
    $age = $dateOfBirth->diff(new DrupalDateTime())->y;

    if ($age < 14) {
      $form_state->setErrorByName('date_of_birth', $this->t('Player must be at least 14 years old.'));
    }
  }

  /**
   * @throws EntityStorageException
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $player_search = $form_state->getValue('player_search');
    $competition_id = $this->getRouteMatch()->getParameter('competition')->id();

    $values = $form_state->getValues();

    $player_details = [
      'full_name' => $form_state->getUserInput()['full_name'],
      'date_of_birth' => $form_state->getUserInput()['date_of_birth'],
      'gender' => $form_state->getUserInput()['gender'],
      'position' => $form_state->getUserInput()['position'],
      'federation' => $values['federation'],
      'club' => $form_state->getUserInput()['club'] ?? null,
    ];

    if ($player_search) {
      $player = \Drupal::entityTypeManager()->getStorage('node')->load($player_search);

      $this->updatePlayer($player, $player_details);
    } else {
      $player = $this->createPlayer($player_details);
    }

    $this->updatePlayerClub($player, $player_details['club']);
    $this->createPlayerApproval($player->id(), $competition_id);

    $this->messenger()->addMessage($this->t('Player registration submitted successfully.'));

    if (Drupal::currentUser()->hasRole('competition_admin')) {
      $form_state->setRedirect('players.federation_competition_players', [
        'competition_id' => $competition_id,
        'federation_id' => $values['federation'],
      ]);
    } elseif (Drupal::currentUser()->hasRole('federation_admin') &&  Drupal\players\Helpers\Helper::isCompetitionFull($competition_id)) {
      $form_state->setRedirect('players.federation_competition_players', [
        'competition_id' => $competition_id,
        'federation_id' => $values['federation'],
      ]);
    } else {
      $form_state->setRedirect('players.federation_competitions');
    }
  }

  private function updatePlayer($player, $player_details): void
  {
    $player->set('field_full_name', $player_details['full_name']);
    $player->set('field_date_of_birth', $player_details['date_of_birth']);
    $player->set('field_gender', $player_details['gender']);
    $player->set('field_position', $player_details['position']);
    $player->set('field_federation', $player_details['federation']);
    $player->save();
  }

  private function createPlayer($player_details)
  {
    $player = \Drupal::entityTypeManager()->getStorage('node')->create([
      'type' => 'player',
      'title' => $player_details['full_name'],
      'field_full_name' => $player_details['full_name'],
      'field_date_of_birth' => $player_details['date_of_birth'],
      'field_gender' => $player_details['gender'],
      'field_position' => $player_details['position'],
      'field_federation' => $player_details['federation'],
    ]);
    $player->save();

    return $player;
  }

  private function updatePlayerClub($player, $new_club_id)
  {
    $current_club_found = false;
    $club_associations = $player->get('field_club')->referencedEntities();

    foreach ($club_associations as $association) {
      if ($association->get('field_club')->target_id == $new_club_id) {
        $association->set('field_current_club', TRUE);
        $current_club_found = true;
      } else {
        $association->set('field_current_club', FALSE);
      }
      $association->save();
    }

    if (!$current_club_found) {
      $new_association = \Drupal::entityTypeManager()->getStorage('paragraph')->create([
        'type' => 'club_association',
        'field_club' => $new_club_id,
        'field_current_club' => TRUE,
      ]);
      $player->field_club->appendItem($new_association);
    }

    $player->save();
  }

  /**
   * @throws EntityStorageException
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  private function createPlayerApproval($player_id, $competition_id): void
  {
    $entity_type_manager = \Drupal::entityTypeManager();
    $node_storage = $entity_type_manager->getStorage('node');

    // Query for existing player approval
    $query = $node_storage->getQuery()
      ->condition('type', 'player_approval')
      ->condition('field_approval_competition', $competition_id)
      ->condition('field_approval_player', $player_id)
      ->accessCheck(FALSE)
      ->range(0, 1);

    $results = $query->execute();

    if (empty($results)) {
      $player_approval = $node_storage->create([
        'type' => 'player_approval',
        'title' => 'Player Approval - ' . $player_id,
        'field_approval_competition' => $competition_id,
        'field_approval_player' => $player_id,
        'field_approval_status' => 'pending_confirmation',
      ]);
      $player_approval->save();
    }
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function playerSearchCallback(array &$form, FormStateInterface $form_state)
  {
    $player_id = $form_state->getValue('player_search');

    if ($player_id) {
      $player = \Drupal::entityTypeManager()->getStorage('node')->load($player_id);
      if ($player) {
        $form_state->set('player_id', $player_id);
        $form['player_details']['full_name']['#value'] = $player->get('field_full_name')->value;
        $form['player_details']['date_of_birth']['#value'] = $player->get('field_date_of_birth')->value;
        $form['player_details']['gender']['#value'] = $player->get('field_gender')->value;
        $form['player_details']['position']['#value'] = $player->get('field_position')->value;

        // Update club options based on the federation
        $federation = $player->get('field_federation')->entity;
        $form_state->setValue('federation', $federation->id());
        $form['player_details']['federation']['#default_value'] = $federation;

        $club_options = $this->getClubOptions($federation->id());
        $form['player_details']['club']['#options'] = $club_options;

        // Set the current club
        $current_club = $this->getCurrentClub($player);
        if ($current_club && isset($club_options[$current_club->id()])) {
          $form_state->setValue('club', $current_club->id());
          $form['player_details']['club']['#value'] = $current_club->id();
        } else {
          $form_state->setValue('club', '');
          $form['player_details']['club']['#value'] = '';
        }
      }
    }

    return $form['player_details'];
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  private function getCurrentUserFederation(): ?string
  {
    $user = \Drupal::entityTypeManager()->getStorage('user')->load(\Drupal::currentUser()->id());
    if ($user->hasField('field_admin_federation')) {
      return $user->get('field_admin_federation')->target_id;
    }

    return null;
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  private function getPositionOptions(): array
  {
    $field_definition = \Drupal::entityTypeManager()
      ->getStorage('field_storage_config')
      ->load('node.field_position');

    $allowed_values = $field_definition->getSetting('allowed_values');
    $options = [];

    foreach ($allowed_values as $key => $value) {
      $options[$key] = $this->t($value);
    }

    return $options;
  }

  private function getCurrentClub($player)
  {
    $club_associations = $player->get('field_club')->referencedEntities();
    foreach ($club_associations as $association) {
      if ($association->get('field_current_club')->value) {
        return $association->get('field_club')->entity;
      }
    }

    return null;
  }

}
