<?php

namespace Drupal\players\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ApprovalPlayersController extends ControllerBase {

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function bulkApprove(Request $request, $competition) {
    $selected_players = $request->request->all()['selected_players'] ?? [];

    if (!empty($selected_players)) {
      $storage = \Drupal::entityTypeManager()->getStorage('node');
      $query = $storage->getQuery()
        ->condition('type', 'player_approval')
        ->condition('field_approval_player', $selected_players, 'IN')
        ->condition('field_approval_competition', $competition)
        ->accessCheck(FALSE);
      $approval_player_ids = $query->execute();

      $approval_players = $storage->loadMultiple($approval_player_ids);

      foreach ($approval_players as $approval_player) {
        $approval_player->set('field_approval_status', 'approved');
        $approval_player->save();
      }

      $this->messenger()->addMessage($this->t('@count approval players have been updated.', ['@count' => count($approval_players)]));
    }

    return new RedirectResponse($request->headers->get('referer'));
  }
}
