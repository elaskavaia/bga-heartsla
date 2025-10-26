<?php

declare(strict_types=1);

namespace Bga\Games\Heartslav\States;

use Bga\GameFramework\StateType;
use Bga\Games\Heartslav\Game;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\States\GameState;

class PlayerTurn extends GameState
{
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: 31,
            type: StateType::ACTIVE_PLAYER, // This state type means that one player is active and can do actions
            description: clienttranslate('${actplayer} must play a card'), // We tell OTHER players what they are waiting for
            descriptionMyTurn: clienttranslate('${you} must play a card'), // We tell the ACTIVE player what they must do
            // We suround the code with clienttranslate() so that the text is sent to the client for translation (this will enable the game to support other languages)
        );
    }

    #[PossibleAction] // a PHP attribute that tells BGA "this method describes a possible action that the player could take", so that you can call that action from the front (the client)
    public function actPlayCard(int $cardId, int $activePlayerId)
    {
        $game = $this->game;
        $card = $game->cards->getCard($cardId);
        if (!$card) {
            throw new \BgaSystemException("Invalid move");
        }
        // Rule checks

        // Check that player has this card in hand
        if ($card['location'] != "hand") {
            throw new \BgaUserException(
                clienttranslate('You do not have this card in your hand')
            );
        }
        $currentTrickColor = $game->getGameStateValue('trickColor');
        // Check that player follows suit if possible
        if ($currentTrickColor != 0) {
            $has_suit = false;
            $hand_cards = $game->cards->getCardsInLocation('hand', $activePlayerId);
            foreach ($hand_cards as $hand_card) {
                if ($hand_card['type'] == $currentTrickColor) {
                    $has_suit = true;
                    break;
                }
            }
            if ($has_suit && $card['type'] != $currentTrickColor) {
                throw new \BgaUserException(
                    clienttranslate('You must follow suit')
                );
            }
        }


        $game->cards->moveCard($cardId, 'cardsontable', $activePlayerId);
        // TODO: check rules here
        $currentCard = $game->cards->getCard($cardId);
        $currentTrickColor = $game->getGameStateValue('trickColor');
        if ($currentTrickColor == 0) $game->setGameStateValue('trickColor', $currentCard['type']);
        // And notify
        $game->notify->all(
            'playCard',
            clienttranslate('${player_name} plays ${value_displayed} ${color_displayed}'),
            [
                'i18n' => array('color_displayed', 'value_displayed'),
                'card' => $currentCard,
                'player_id' => $activePlayerId,
                'player_name' => $game->getActivePlayerName(),
                'value_displayed' => $game->card_types['types'][$currentCard['type_arg']]['name'],
                'color_displayed' => $game->card_types['suites'][$currentCard['type']]['name']
            ]
        );
        return NextPlayer::class;
    }

    public function zombie(int $playerId)
    {
        $game = $this->game;
        // Auto-play a random card from player's hand
        $cards_in_hand = $game->cards->getCardsInLocation('hand', $playerId);
        if (count($cards_in_hand) > 0) {
            $card_to_play = $cards_in_hand[array_rand($cards_in_hand)];
            $game->cards->moveCard($card_to_play['id'], 'cardsontable', $playerId);
            // Notify
            $game->notify->all(
                'playCard',
                clienttranslate('${player_name} auto plays ${value_displayed} ${color_displayed}'),
                [
                    'i18n' => array('color_displayed', 'value_displayed'),
                    'card' => $card_to_play,
                    'player_id' => $playerId,
                    'value_displayed' => $game->card_types['types'][$card_to_play['type_arg']]['name'],
                    'color_displayed' => $game->card_types['suites'][$card_to_play['type']]['name']
                ]
            );
        }
        return NextPlayer::class;
    }
}
