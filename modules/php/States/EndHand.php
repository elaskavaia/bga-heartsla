<?php

declare(strict_types=1);

namespace Bga\Games\Heartslav\States;

use Bga\GameFramework\StateType;
use Bga\Games\Heartslav\Game;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\NotificationMessage;
use Bga\Games\Heartslav\States\NewHand;

class EndHand extends GameState
{
  public function __construct(protected Game $game)
  {
    parent::__construct(
      $game,
      id: 40,
      type: StateType::GAME,
    );
  }

  public function onEnteringState()
  {
    $game = $this->game;
    // Count and score points, then end the game or go to the next hand.
    $players = $game->loadPlayersBasicInfos();
    // Gets all "hearts" + queen of spades

    $player_to_points = array();
    foreach ($players as $player_id => $player) {
      $player_to_points[$player_id] = 0;
    }

    $cards = $game->cards->getCardsInLocation("cardswon");
    foreach ($cards as $card) {
      $player_id = $card['location_arg'];
      // Type 2 is hearts
      if ($card['type'] == 2) {
        $player_to_points[$player_id]++;
      }
    }

    // Apply scores to player
    foreach ($player_to_points as $player_id => $points) {
      if ($points != 0) {
        $game->playerScore->inc(
          $player_id,
          -$points,
          new NotificationMessage(
            clienttranslate('${player_name} gets ${absInc} hearts and looses ${absInc} points'),
          )
        );
      }
    }

    // Test if this is the end of the game
    if ($game->playerScore->getMin() <= -100) {
      // Trigger the end of the game !
      return 99; // end game
    }


    return NewHand::class;
  }
}
