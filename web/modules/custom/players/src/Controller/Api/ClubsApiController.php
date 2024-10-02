<?php

namespace Drupal\players\Controller\Api;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\JsonResponse;

class ClubsApiController extends ControllerBase
{

  private mixed $fileGenerator;

  public function __construct()
  {
    $this->fileGenerator = Drupal::service('file_url_generator');
  }

  public function getClubs(): JsonResponse
  {
    $query = Drupal::entityQuery('node')
      ->condition('type', 'club')
      ->condition('status', 1)
      ->accessCheck()
      ->execute();
    $clubs = Drupal::entityTypeManager()->getStorage('node')->loadMultiple($query);

    $clubs_data = Arr::map($clubs, function ($club) {
      $city = $club->get('field_city')->referencedEntities() ?? [];
      $federation = $club->get('field_club_federation')->referencedEntities() ?? [];


      return [
        'id' => $club->id(),
        'title' => $club->getTitle(),
        'city' => (reset($city))->getName(),
        'federation' => (reset($federation))->getName(),
        'logo' => $this->prepareLogo($club)
      ];
    });

    return new JsonResponse(array_values($clubs_data));
  }

  private function prepareLogo($club)
  {
    if ($club->hasField('field_logo') && !$club->get('field_logo')->isEmpty()) {
      $logo_files = $club->get('field_logo')->referencedEntities();

      if (!empty($logo_files)) {
        $logo = $this->fileGenerator->generateAbsoluteString((reset($logo_files))->getFileUri());
      } else {
        $logo = NULL;
      }
    } else {
      $logo = NULL;
    }

    return $logo;
  }


}
