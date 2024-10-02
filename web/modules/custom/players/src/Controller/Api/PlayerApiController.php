<?php

namespace Drupal\players\Controller\Api;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormState;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\players\Form\Api\PlayerCreateForm;
use Exception;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class PlayerApiController extends ControllerBase
{

  protected $formBuilder;

  private mixed $fileGenerator;

  public function __construct()
  {
    $this->fileGenerator = Drupal::service('file_url_generator');
    $this->formBuilder = Drupal::service('form_builder');
  }


  public function getAll(): JsonResponse
  {
    $query = Drupal::entityQuery('node')
      ->condition('type', 'player')
      ->condition('status', 1)
      ->accessCheck()
      ->execute();
    $players = Drupal::entityTypeManager()->getStorage('node')->loadMultiple($query);

    $clubs_data = Arr::map($players, function ($player) {
      $clubs = $this->prepareCLub($player);
      $federation = $player->get('field_federation')->referencedEntities() ?? [];

      return [
        'id' => $player->id(),
        'full_name' => $player->getTitle(),
        'current_club' => $clubs,
        'gender' => $player->get('field_gender')->value,
        'position' => $player->get('field_position')->value,
        'federation' => reset($federation) ? (reset($federation))->getName() : null,
        'birth_of_date' => $player->get('field_date_of_birth')->value,
        'image' => $this->prepareImage($player)
      ];
    });

    return new JsonResponse(array_values($clubs_data));
  }

  private function prepareCLub($player)
  {
    $club_paragraphs = $player->get('field_club')->referencedEntities();
    $current_club = '';
    if (!empty($club_paragraphs)) {
      foreach ($club_paragraphs as $club) {
        if ($club->get('field_current_club')->value == 1) {
          $club = $club->get('field_club')->referencedEntities() ?? [];
          $club = !empty($club) ? reset($club) : null;

          $current_club = [
            'id' => $club ? $club->id() : null,
            'name' => $club ? $club->getTitle() : null,
          ];
        }
      }
    }
    return $current_club;

  }

  private function prepareImage($player)
  {
    if ($player->hasField('field_player_image') && !$player->get('field_player_image')->isEmpty()) {
      $logo_files = $player->get('field_player_image')->referencedEntities();

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

  public function addPlayer(Request $request): JsonResponse
  {
    $data = json_decode($request->getContent(), TRUE);

    $form_state = new FormState();
    $form_state->setValues($data);

    $form = new PlayerCreateForm();
    $empty_form_ref = [];

    $form->validateForm($empty_form_ref, $form_state);

    if ($form_state->hasAnyErrors()) {
      $errors = [];
      foreach ($form_state->getErrors() as $field => $error) {
        $errors[$field] = $error;
      }
      return new JsonResponse(['errors' => $errors], 400);
    }

    try {
      if (!empty($data['club'])) {
        $player = Node::create([
          'type' => 'player',
          'title' => $data['full_name'],
          'field_full_name' => $data['full_name'],
          'field_position' => $data['position'],
          'field_federation' => $data['federation'],
          'field_gender' => $data['gender'],
          'field_date_of_birth' => $data['date_of_birth'],
          'field_player_image' => $this->uploadImage($data['player_image']),
        ]);

        $club_paragraph = Paragraph::create([
          'type' => 'club_association',
          'field_club_name' => $data['club'],
//          'field_current_club' => 1
        ]);
        $club_paragraph->save();
        $player->set('field_club', [
          'target_id' => $club_paragraph->id(),  // Paragraph entity ID.
          'target_revision_id' => $club_paragraph->getRevisionId(),  // Paragraph revision ID.
        ]);
        $player->save();

      }
      return new JsonResponse(['message' => 'Player Created Successfully.']);
    } catch (Exception $e) {
      return new JsonResponse(['error' => $e->getMessage()], $e->getCode());
    }
//
//    // Return success response.
//    return new JsonResponse([
//      'status' => 'success',
//      'message' => 'Player created successfully.',
//      'player_id' => $player->id(),
//    ], 201);
  }

  private function uploadImage($image_base64)
  {
    $file_data = base64_decode($image_base64);
    $file_name = 'public://player_images/' . uniqid() . '.jpg';
    $dir = 'public://player_images';
    Drupal::service('file_system')->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY);
    file_put_contents($file_name, $file_data);

    $file = File::create([
      'uri' => $file_name,
      'status' => 1,
    ]);

    $file->save();
    return $file->id();
  }
}
