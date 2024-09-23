<?php

namespace Drupal\players\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

class FederationAccessCheck {
  public function access(AccountInterface $account) {
    return AccessResult::allowedIf($account->hasRole('federation_admin'));
  }
}
