<?php

namespace Drupal\players\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CompetitionsPlayersController extends ControllerBase {

  public function listPlayers($federation_id, $competition_id) {
    $federation = Term::load($federation_id);
    $competition = Node::load($competition_id);
    if (!$federation || !$competition) {
      throw new NotFoundHttpException();
    }

    $players = $this->getPlayersForFederationAndCompetition($federation, $competition);

    return [
      '#theme' => 'players_list',
      '#federation' => $federation,
      '#competition' => $competition,
      '#players' => $players,
    ];
  }

  private function getPlayersForFederationAndCompetition($federation, $competition) {
    // Query for players registered by the selected federation
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'player')
      ->condition('field_federation', $federation->id())
      ->accessCheck(FALSE);
    $player_ids = $query->execute();

    // Query for Player Approval nodes that link players to the competition
    $approval_query = \Drupal::entityQuery('node')
      ->condition('type', 'player_approval')
      ->condition('field_approval_competition', $competition->id())
      ->condition('field_approval_player', $player_ids, 'IN')
      ->condition('field_approval_status', 'rejected', '!=')
      ->accessCheck(FALSE);
    $approval_ids = $approval_query->execute();

    // Load the approved players with their approval status
    $players_with_status = [];
    if (!empty($approval_ids)) {
      $approvals = Node::loadMultiple($approval_ids);
      foreach ($approvals as $approval) {
        $player_id = $approval->get('field_approval_player')->target_id;
        $status = $approval->get('field_approval_status')->value;
        $players_with_status[$player_id] = [
          'player' => Node::load($player_id),
          'status' => $status,
        ];
      }
    }

    return $players_with_status;
  }
}
