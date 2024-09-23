<?php

namespace Drupal\players\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ClubAutocompleteController extends ControllerBase
{

  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entity_type_manager)
  {
    $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('logger.factory')->get('players')
    );
  }

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function handleAutocomplete(Request $request,$federation): JsonResponse
  {
    $matches = [];

    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'club')
      ->condition('title', $request->query->get('q'), 'CONTAINS')
      ->accessCheck(false)
      ->range(0, 10);

    if ($federation) {
      $query->condition('field_club_federation', $federation);
    }

    $result = $query->execute();

    $clubs = $this->entityTypeManager->getStorage('node')->loadMultiple($result);

    foreach ($clubs as $club) {
      $matches[] = [
        'value' => $club->label() . ' (' . $club->id() . ')',
        'label' => $club->label(),
      ];
    }

    return new JsonResponse($matches);
  }
}
