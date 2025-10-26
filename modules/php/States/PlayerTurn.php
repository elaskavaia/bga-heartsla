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

    public function getArgs(int $activePlayerId): array
    {
        // Send playable card ids of the active player privately
        return [
            '_private' => [
                $activePlayerId => [
                    'playableCards' => $this->game->getPlayableCards($activePlayerId)
                ],
            ],
        ];
    }

    #[PossibleAction] // a PHP attribute that tells BGA "this method describes a possible action that the player could take", so that you can call that action from the front (the client)
    public function actPlayCard(int $cardId, int $activePlayerId)
    {
        $game = $this->game;
        $currentCard = $game->cards->getCard($cardId);
        if (!$currentCard) {
            throw new \BgaSystemException("Invalid move $cardId");
        }
        // Rule checks
        $playable_cards = $game->getPlayableCards($activePlayerId);
        if (!in_array($cardId, $playable_cards)) {
            throw new \BgaUserException(clienttranslate("You cannot play this card now"));
        }

        $game->cards->moveCard($cardId, 'cardsontable', $activePlayerId);

        // Set the trick color if it hasn't been set yet
        $currentTrickColor = $game->getGameStateValue('trick_color');
        if ($currentTrickColor == 0) $game->setGameStateValue('trick_color', $currentCard['type']);
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
        $playable_cards = $this->game->getPlayableCards($playerId);
        if (count($playable_cards) == 0) {
            return NextPlayer::class;
        }
        $zombieChoice = $this->getRandomZombieChoice($playable_cards); // random choice over possible moves
        return $this->actPlayCard((int)$zombieChoice, $playerId);
    }
}
