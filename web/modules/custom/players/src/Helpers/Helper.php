<?php

namespace Drupal\players\Helpers;

use Drupal\node\Entity\Node;

/**
 * Class PlayersHelper.
 *
 * A helper class for various player-related functionalities.
 */
class Helper {

  /**
   * Checks if a competition has reached its maximum number of players.
   *
   * @param int $competition_id
   *   The competition ID.
   *
   * @return bool
   *   TRUE if the competition is full, FALSE otherwise.
   */
  public static function isCompetitionFull($competition_id): bool
  {
    $config = \Drupal::config('players.settings');
    $max_players = $config->get('max_number_of_players');

    $playersNumber = \Drupal::entityQuery('node')
      ->condition('type', 'player_approval')
      ->condition('field_approval_competition', $competition_id)
      ->count()
      ->accessCheck()
      ->execute();

    return $playersNumber >= $max_players;
  }
}
