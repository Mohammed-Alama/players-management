<?php

namespace Drupal\players\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\Entity\Node;

class FederationCompetitionsController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * List competitions for the current federation user.
   */
  public function listCompetitions(): array
  {
    $current_user = $this->currentUser();
    $federation_id = $this->getUserFederationId($current_user);

    // Load competitions associated with the federation.
    $competitions = $this->loadCompetitionsByFederation($federation_id);

    // Build the render array for the competitions list.
    $build = [
      '#theme' => 'item_list',
      '#items' => [],
      '#title' => $this->t('My Competitions'),
    ];

    foreach ($competitions as $competition) {
      $build['#items'][] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['competition-item']],
        'teaser' => $this->entityTypeManager()->getViewBuilder('node')->view($competition, 'teaser'),
        'register_button' => [
          '#type' => 'link',
          '#title' => $this->t('Register Players'),
          '#url' => \Drupal\Core\Url::fromRoute('players.player_registration_form', ['competition' => $competition->id()]),
          '#attributes' => ['class' => ['button', 'register-players']],//add disabled if max players
        ],
      ];
    }

    return $build;
  }

  /**
   * Get the federation ID for the current user.
   */
  private function getUserFederationId(AccountInterface $account) {
    // Assuming you have a field on the user entity that stores the federation.
    $user = $this->entityTypeManager()->getStorage('user')->load($account->id());
    return $user->get('field_admin_federation')->target_id ?? NULL;
  }

  /**
   * Load competitions associated with a federation.
   */
  private function loadCompetitionsByFederation($federation_id) {

    $query = \Drupal::entityQuery('node')
      ->condition('type', 'competition')
      ->condition('field_teams', $federation_id)
      ->accessCheck(FALSE);

    $nids = $query->execute();
    return Node::loadMultiple($nids);
  }
}
