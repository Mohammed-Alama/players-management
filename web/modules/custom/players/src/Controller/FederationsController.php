<?php

namespace Drupal\players\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FederationsController extends ControllerBase
{

  public function listPublished()
  {
    $federations = $this->getPublishedFederations();

    $renderController = \Drupal::entityTypeManager()->getViewBuilder('taxonomy_term');

    $federationTeasers = array_map(function ($federation) use ($renderController) {
      return $renderController->view($federation, 'teaser');
    }, $federations);


    return [
      '#theme' => 'federation_list',
      '#federations' => $federationTeasers,
    ];
  }

  public function listPlayers($federation_id, $competition_id)
  {
    $federation = Term::load($federation_id);
    $competition = Node::load($competition_id);
    if (!$federation || !$competition) {
      throw new NotFoundHttpException();
    }

    $players = $this->getPlayersForFederationAndCompetition($federation, $competition);

    $build = [
      '#theme' => 'player_list',
      '#federation' => $federation,
      '#competition' => $competition,
      '#players' => $players,
    ];

    return $build;
  }

  private function getCompetitionsForAdmin($user)
  {
    // Implement logic to get competitions for admin
  }

  private function getFederationsForCompetitions($competitions)
  {
    // Implement logic to get federations for competitions
  }

  private function getCompetitionsForFederation($federation)
  {
    // Implement logic to get competitions for federation
  }

  private function getPlayersForFederationAndCompetition($federation, $competition)
  {
    // Implement logic to get players for federation and competition
  }

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getPublishedFederations()
  {
    $query = $this->entityTypeManager()->getStorage('taxonomy_term')->getQuery();
    $query->condition('vid', 'federation');
    $query->condition('status', 1);
    $query->accessCheck(FALSE);
    $result = $query->execute();

    return Term::loadMultiple($result);
  }
}
