<?php

namespace Drupal\players\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LatestPlayersByCompetitionController extends ControllerBase
{
  public function latestByCompetition(NodeInterface $competition): array
  {
    if ($competition->bundle() !== 'competition') {
      throw new NotFoundHttpException();
    }

    $approval_query = \Drupal::entityQuery('node')
      ->condition('type', 'player_approval')
      ->condition('field_approval_competition', $competition->id())
      ->condition('field_approval_status', 'approved')
      ->sort('created', 'DESC')
      ->range(0, 10)
      ->accessCheck(FALSE);
    $approval_ids = $approval_query->execute();


    $players = [];
    if (!empty($approval_ids)) {
      $approvals = Node::loadMultiple($approval_ids);
      foreach ($approvals as $approval) {
        $player_id = $approval->get('field_approval_player')->target_id;
        $players[] = Node::load($player_id);
      }
    }

    return [
      '#theme' => 'latest_players_by_competition',
      '#competition' => $competition,
      '#players' => $players,
    ];
  }

}
