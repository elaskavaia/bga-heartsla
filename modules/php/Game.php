<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Heartslav implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * Game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 */

declare(strict_types=1);

namespace Bga\Games\Heartslav;

use Bga\Games\Heartslav\States\PlayerTurn;
use Bga\GameFramework\Components\Deck;

define("HEARTS", 2);
define("CLUBS", 3);

class Game extends \Bga\GameFramework\Table
{

    public array $card_types;

    public Deck $cards;


    /**
     * Your global variables labels:
     *
     * Here, you can assign labels to global variables you are using for this game. You can use any number of global
     * variables with IDs between 10 and 99. If you want to store any type instead of int, use $this->globals instead.
     *
     * NOTE: afterward, you can get/set the global variables with `getGameStateValue`, `setGameStateInitialValue` or
     * `setGameStateValue` functions.
     */
    public function __construct()
    {
        parent::__construct();
        $this->initGameStateLabels(
            [
                "trick_color" => 11
            ]
        );

        $this->cards = $this->deckFactory->createDeck('card');


        $this->card_types = [
            "suites" => [
                1 => [
                    'name' => clienttranslate('Spade'),
                ],
                2 => [
                    'name' => clienttranslate('Heart'),
                ],
                3 => [
                    'name' => clienttranslate('Club'),
                ],
                4 => [
                    'name' => clienttranslate('Diamond'),
                ]
            ],
            "types" => [
                2 => ['name' => '2'],
                3 => ['name' => '3'],
                4 => ['name' => '4'],
                5 => ['name' => '5'],
                6 => ['name' => '6'],
                7 => ['name' => '7'],
                8 => ['name' => '8'],
                9 => ['name' => '9'],
                10 => ['name' => '10'],
                11 => ['name' => clienttranslate('J')],
                12 => ['name' => clienttranslate('Q')],
                13 => ['name' => clienttranslate('K')],
                14 => ['name' => clienttranslate('A')]
            ]
        ];

        /* example of notification decorator.*/

        $this->notify->addDecorator(function (string $message, array $args) {
            if (isset($args['player_id']) && !isset($args['player_name']) && str_contains($message, '${player_name}')) {
                $args['player_name'] = $this->getPlayerNameById($args['player_id']);
            }

            return $args;
        });
    }

    /**
     * Compute and return the current game progression.
     *
     * The number returned must be an integer between 0 and 100.
     *
     * This method is called each time we are in a game state with the "updateGameProgression" property set to true.
     *
     * @return int
     * @see ./states.inc.php
     */
    public function getGameProgression()
    {
        // TODO: compute and return the game progression

        return 0;
    }

    /**
     * Migrate database.
     *
     * You don't have to care about this until your game has been published on BGA. Once your game is on BGA, this
     * method is called everytime the system detects a game running with your old database scheme. In this case, if you
     * change your database scheme, you just have to apply the needed changes in order to update the game database and
     * allow the game to continue to run with your new version.
     *
     * @param int $from_version
     * @return void
     */
    public function upgradeTableDb($from_version)
    {
        //       if ($from_version <= 1404301345)
        //       {
        //            // ! important ! Use `DBPREFIX_<table_name>` for all tables
        //
        //            $sql = "ALTER TABLE `DBPREFIX_xxxxxxx` ....";
        //            $this->applyDbUpgradeToAllDB( $sql );
        //       }
        //
        //       if ($from_version <= 1405061421)
        //       {
        //            // ! important ! Use `DBPREFIX_<table_name>` for all tables
        //
        //            $sql = "CREATE TABLE `DBPREFIX_xxxxxxx` ....";
        //            $this->applyDbUpgradeToAllDB( $sql );
        //       }
    }

    /*
     * Gather all information about current game situation (visible by the current player).
     *
     * The method is called each time the game interface is displayed to a player, i.e.:
     *
     * - when the game starts
     * - when a player refreshes the game page (F5)
     */
    protected function getAllDatas(): array
    {
        $result = [];

        // WARNING: We must only return information visible by the current player.
        $current_player_id = (int) $this->getCurrentPlayerId();

        // Get information about players.
        // NOTE: you can retrieve some extra field you added for "player" table in `dbmodel.sql` if you need it.
        $result["players"] = $this->getCollectionFromDb(
            "SELECT `player_id` `id`, `player_score` `score` FROM `player`"
        );


        // TODO: Gather all information about current game situation (visible by player $current_player_id).

        // Cards in player hand
        $result['hand'] = $this->cards->getCardsInLocation('hand', $current_player_id);

        // Cards played on the table
        $result['cardsontable'] = $this->cards->getCardsInLocation('cardsontable');


        return $result;
    }

    /**
     * This method is called only once, when a new game is launched. In this method, you must setup the game
     *  according to the game rules, so that the game is ready to be played.
     */
    protected function setupNewGame($players, $options = [])
    {
        // Set the colors of the players with HTML color code. The default below is red/green/blue/orange/brown. The
        // number of colors defined here must correspond to the maximum number of players allowed for the gams.
        $gameinfos = $this->getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        foreach ($players as $player_id => $player) {
            // Now you can access both $player_id and $player array
            $query_values[] = vsprintf("('%s', '%s', '%s', '%s', '%s')", [
                $player_id,
                array_shift($default_colors),
                $player["player_canal"],
                addslashes($player["player_name"]),
                addslashes($player["player_avatar"]),
            ]);
        }

        // Create players based on generic information.
        //
        // NOTE: You can add extra field on player table in the database (see dbmodel.sql) and initialize
        // additional fields directly here.
        static::DbQuery(
            sprintf(
                "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES %s",
                implode(",", $query_values)
            )
        );

        $this->reattributeColorsBasedOnPreferences($players, $gameinfos["player_colors"]);
        $this->reloadPlayersBasicInfos();

        // Init global values with their initial values.



        // Set current trick color to zero (= no trick color)
        $this->setGameStateInitialValue('trick_color', 0);


        // Init game statistics.
        //
        // NOTE: statistics used in this file must be defined in your `stats.inc.php` file.

        // Dummy content.
        // $this->tableStats->init('table_teststat1', 0);
        // $this->playerStats->init('player_teststat1', 0);

        // TODO: Setup the initial game situation here.

        // Create cards
        $cards = [];
        foreach ($this->card_types["suites"] as $suit => $suit_info) {
            // spade, heart, diamond, club
            foreach ($this->card_types["types"] as $value => $info_value) {
                //  2, 3, 4, ... K, A
                $cards[] = ['type' => $suit, 'type_arg' => $value, 'nbr' => 1];
            }
        }
        $this->cards->createCards($cards, 'deck');

        // Activate first player once everything has been initialized and ready.
        $this->activeNextPlayer();
        return PlayerTurn::class;
    }

    function getPlayableCards($player_id): array
    {
        // Get all data needed to check playable cards at the moment
        $currentTrickColor = $this->getGameStateValue('trick_color');
        $broken_heart = $this->brokenHeart();
        $hand = $this->cards->getPlayerHand($player_id);
        $playable_card_ids = [];
        $all_ids = array_keys($hand);

        if ($this->cards->getCardsInLocation('cardsontable', $player_id)) return []; // Already played a card

        //Check whether the first card of the hand has been played or not
        $total_played = $this->cards->countCardInLocation('cardswon') + $this->cards->countCardInLocation('cardsontable');
        if ($total_played == 0) {
            // No cards have been played yet, find and return the starter card only
            foreach ($hand as $card) if ($card['type'] == 3 && $card['type_arg'] == 2) return [$card['id']]; // 2 of clubs
            return $all_ids; // should not happen
        } else if (!$currentTrickColor) { // First card of the trick
            if ($broken_heart) {
                return $all_ids; // Broken Heart or no limitation, can play any card
            } else {
                // Exclude Heart as Heart hasn't been broken yet
                foreach ($hand as $card) {
                    if ($card['type'] != HEARTS)
                        $playable_card_ids[] = $card['id'];
                }
                if (!$playable_card_ids)
                    return $all_ids; // All Heart cards!
                else
                    return $playable_card_ids;
            }
        } else {
            // Must follow the lead suit if possible
            $same_suit = false;
            foreach ($hand as $card)
                if ($card['type'] == $currentTrickColor) {
                    $same_suit = true;
                    break;
                }
            if ($same_suit) return $this->getObjectListFromDB("SELECT card_id FROM card WHERE card_type = $currentTrickColor AND card_location = 'hand' AND card_location_arg = $player_id", true); // Has at least 1 card of the same suit

            else return $all_ids;
        }
    }

    function brokenHeart(): bool
    {
        // Check Heart in the played card piles
        return (bool)$this->getUniqueValueFromDB("SELECT count(*) FROM card WHERE card_location = 'cardswon' AND card_type = 2");
    }


    /**
     * Example of debug function.
     * Here, jump to a state you want to test (by default, jump to next player state)
     * You can trigger it on Studio using the Debug button on the right of the top bar.
     */
    public function debug_goToState(int $state = 3)
    {
        $this->gamestate->jumpToState($state);
    }

    /**
     * Another example of debug function, to easily test the zombie code.
     */
    public function debug_playAutomatically(int $moves = 50)
    {
        $count = 0;
        while (intval($this->gamestate->getCurrentMainStateId()) < 99 && $count < $moves) {
            $count++;
            foreach ($this->gamestate->getActivePlayerList() as $playerId) {
                $playerId = (int)$playerId;
                $this->gamestate->runStateClassZombie($this->gamestate->getCurrentState($playerId), $playerId);
            }
        }
    }

    /*
    Another example of debug function, to easily create situations you want to test.
    Here, put a card you want to test in your hand (assuming you use the Deck component).

    public function debug_setCardInHand(int $cardType, int $playerId) {
        $card = array_values($this->cards->getCardsOfType($cardType))[0];
        $this->cards->moveCard($card['id'], 'hand', $playerId);
    }
    */
}
