<?php

namespace Drupal\players\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\node\NodeInterface;

class PlayersController extends ControllerBase {

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function checkClubFederation($club, $federation): JsonResponse
  {
    $club_node = $this->entityTypeManager()->getStorage('node')->load($club);

    if ($club_node instanceof NodeInterface && $club_node->bundle() === 'club') {
      $club_federation = $club_node->get('field_club_federation')->target_id;
      $belongs = $club_federation == $federation;
    } else {
      $belongs = false;
    }

    return new JsonResponse(['belongs' => $belongs]);
  }
}
