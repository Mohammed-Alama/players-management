<?php

namespace Drupal\players\Access;

use Drupal;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;

class PlayerRegistrationAccessCheck
{
  public function access(AccountInterface $account, NodeInterface $competition)
  {
    // Check if the user has permission to register players
    // Check if the competition registration is open

    if (Drupal\players\Helpers\Helper::isCompetitionFull($competition->id())) {
//     return AccessResult::forbidden();
      return AccessResult::allowed();
    }

    return AccessResult::allowed();
  }
}
