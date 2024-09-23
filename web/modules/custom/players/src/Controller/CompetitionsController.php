<?php

namespace Drupal\players\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CompetitionsController extends ControllerBase
{
  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function list($federation_id)
  {
    $federation = Term::load($federation_id);
    if (!$federation) {
      throw new NotFoundHttpException();
    }

    $competitions = $this->getCompetitionsForFederation($federation);
    $renderController = \Drupal::entityTypeManager()->getViewBuilder('taxonomy_term');

    $competitionsTeasers = array_map(function ($competition) use ($renderController) {
      return [
        'id' => $competition->id(),
        'teaser' => $renderController->view($competition, 'teaser'),
      ];
    }, $competitions);

    return [
      '#theme' => 'competition_list',
      '#federation' => $federation,
      '#competitions' => $competitionsTeasers,
    ];
  }

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getCompetitionsForFederation($federation): array
  {
    $query = $this->entityTypeManager()
      ->getStorage('node')
      ->getQuery();
    $nids = $query
      ->condition('type', 'competition')
      ->condition('field_teams', $federation->id(), 'CONTAINS')
      ->accessCheck(FALSE)
      ->execute();

    return $this->entityTypeManager()->getStorage('node')->loadMultiple($nids);
  }
}
