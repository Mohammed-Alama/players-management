<?php 
namespace Drupal\players\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

class PlayerRegistrationAccessCheck {
  public function access(AccountInterface $account, NodeInterface $competition) {
    // Check if the user has permission to register players
    // Check if the competition registration is open
    // Check if the maximum number of players has been reached
    // Check if the registration deadline has passed
    return AccessResult::allowed(); // Modify this based on your checks
  }
}
