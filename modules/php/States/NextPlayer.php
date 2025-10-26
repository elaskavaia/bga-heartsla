<?php

declare(strict_types=1);

namespace Bga\Games\Heartslav\States;

use Bga\GameFramework\StateType;
use Bga\Games\Heartslav\Game;
use Bga\GameFramework\States\GameState;

class NextPlayer extends GameState
{
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: 32,
            type: StateType::GAME,
        );
    }

    public function onEnteringState()
    {
        $game = $this->game;
        // Active next player OR end the trick and go to the next trick OR end the hand
        if ($game->cards->countCardInLocation('cardsontable') == 4) {
            // This is the end of the trick
            $cards_on_table = $game->cards->getCardsInLocation('cardsontable');
            $best_value = 0;
            $best_value_player_id = $this->game->getActivePlayerId();
            $currentTrickColor = $game->getGameStateValue('trick_color');
            foreach ($cards_on_table as $card) {
                if ($card['type'] == $currentTrickColor) {   // type is card suite
                    if ($best_value_player_id === null || $card['type_arg'] > $best_value) {
                        $best_value_player_id = $card['location_arg']; // location_arg is player who played this card on table
                        $best_value = $card['type_arg']; // type_arg is value of the card (2 to 14)
                    }
                }
            }

            // Active this player => he's the one who starts the next trick
            $this->gamestate->changeActivePlayer($best_value_player_id);

            // Move all win cards to cardswon location
            $moved_cards = $game->cards->getCardsInLocation('cardsontable'); // remember for notification what we moved
            $game->cards->moveAllCardsInLocation('cardsontable', 'cardswon', null, $best_value_player_id);

            // Note: we use 2 notifications here in order we can pause the display during the first notification
            //  before we move all cards to the winner (during the second)

            $game->notify->all('trickWin', clienttranslate('${player_name} wins the trick'), array(
                'player_id' => $best_value_player_id,
            ));

            $game->notify->all('giveAllCardsToPlayer', '', array(
                'player_id' => $best_value_player_id,
                'cards' => $game->cards->getCards(array_keys($moved_cards))
            ));

            if ($game->cards->countCardInLocation('hand') == 0) {
                // End of the hand
                return EndHand::class;
            } else {
                // End of the trick
                // Reset trick suite to 0 
                $this->game->setGameStateInitialValue('trick_color', 0);
                return PlayerTurn::class;
            }
        } else {
            // Standard case (not the end of the trick)
            // => just active the next player
            $player_id = $game->activeNextPlayer();
            $game->giveExtraTime($player_id);
            return PlayerTurn::class;
        }
    }
}
