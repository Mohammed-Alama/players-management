<?php

namespace Drupal\players\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

class ContentOperationController extends ControllerBase {

  public function prepareLinks(Request $request): array
  {
    $build = [];
    $current_user = \Drupal::currentUser();

    if ($current_user->hasRole('ihf_editor')) {
      $build =[ [
        '#markup' => $this->t('<a href="@link">Create User</a>', ['@link' => '#']),
      ], [
        '#markup' => $this->t('<a href="@link">Manage Competitions</a>', ['@link' => '/manage-competitions']),
      ], [
        '#markup' => $this->t('<a href="@link">Manage Federations</a>', ['@link' => '/manage-federations']),
      ], [
        '#markup' => $this->t('<a href="@link">Manage Cities</a>', ['@link' => '/manage-cities']),
      ], [
        '#markup' => $this->t('<a href="@link">Manage Players</a>', ['@link' => '/manage-players']),
      ], [
        '#markup' => $this->t('<a href="@link">Manage Clubs</a>', ['@link' => '/manage-clubs']),
      ]];
    }

    if ($current_user->hasRole('ihf_editor')) {
      $build[] = [
        '#markup' => $this->t('<a href="@link">Content Management</a>', ['@link' => '#']),
      ];
    }

    // You can add more conditions and links as needed.

    return [
      '#theme' => 'item_list',
      '#items' => $build,
    ];
  }
}
