<?php

declare(strict_types=1);

namespace Bga\Games\Heartslav\States;

use Bga\Games\Heartslav\Game;
use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;

class NewHand extends GameState
{
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: 2, // the idea of the state
            type: StateType::GAME, // This type means that no player is active, and the game will automatically progress
            updateGameProgression: true, // entering this state can update the progress bar of the game
        );
    }

    // The action we do when entering the state
    public function onEnteringState()
    {
        $game = $this->game;
        $this->game->setGameStateValue('trick_color', 0);
        // Take back all cards (from any location => null (any)) to deck
        $game->cards->moveAllCardsInLocation(null, "deck");
        $game->cards->shuffle('deck');
        // Deal 13 cards to each players
        // Create deck, shuffle it and give 13 initial cards
        $players = $game->loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            $cards = $game->cards->pickCards(13, 'deck', $player_id);
            // Notify player about his cards
            $this->notify->player($player_id, 'newHand', '', ['cards' => $cards]);
        }

        // first player one with 2 of clubs
        $first_player = $this->game->getUniqueValueFromDb("SELECT card_location_arg FROM card WHERE card_location = 'hand' AND card_type = 3 AND card_type_arg = 2");// 2 of clubs
        $this->game->gamestate->changeActivePlayer($first_player);
        return PlayerTurn::class;
    }
}
