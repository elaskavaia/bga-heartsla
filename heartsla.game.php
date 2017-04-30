<?php
 /**
  *------
  * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
  * template implementation : © <Your name here> <Your email address here>
  * 
  * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
  * See http://en.boardgamearena.com/#!doc/Studio for more information.
  * -----
  * 
  * heartsla.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class Heartsla extends Table {

	function __construct() {
        	
 
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        
	    parent::__construct();
	    self::initGameStateLabels( array(
	            "currentHandType" => 10,
	            "trickColor" => 11,
	            "alreadyPlayedHearts" => 12,
	            //      ...
	            //    "my_first_game_variant" => 100,
	    ) );
	    
	    $this->cards = self::getNew( "module.common.deck" );
	    $this->cards->init( "card" );        
	}
	
    protected function getGameName( )
    {
		// Used for translations and stuff. Please do not modify.
        return "heartsla";
    }	

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
    {    
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $default_colors = array( "ff0000", "008000", "0000ff", "ffa500", "773300" );
 
        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reattributeColorsBasedOnPreferences( $players, array(  "ff0000", "008000", "0000ff", "ffa500", "773300" ) );
        self::reloadPlayersBasicInfos();
        
        /************ Start the game initialization *****/

        // Init global values with their initial values
        
        // Note: hand types: 0 = give 3 cards to player on the left
        //                   1 = give 3 cards to player on the right
        //                   2 = give 3 cards to player on tthe front
        //                   3 = keep cards
        self::setGameStateInitialValue( 'currentHandType', 0 );
        
        // Set current trick color to zero (= no trick color)
        self::setGameStateInitialValue( 'trickColor', 0 );
        
        // Mark if we already played some heart during this hand
        self::setGameStateInitialValue( 'alreadyPlayedHearts', 0 );
        
        // Init game statistics
        // (note: statistics are defined in your stats.inc.php file)

        // Create cards
        $cards = array ();
        foreach ( $this->colors as $color_id => $color ) {
            // spade, heart, diamond, club
            for ($value = 2; $value <= 14; $value ++) {
                //  2, 3, 4, ... K, A
                $cards [] = array ('type' => $color_id,'type_arg' => $value,'nbr' => 1 );
            }
        }
        
        $this->cards->createCards( $cards, 'deck' );
       
        // Shuffle deck
        $this->cards->shuffle('deck');
        // Deal 13 cards to each players
        $players = self::loadPlayersBasicInfos();
        foreach ( $players as $player_id => $player ) {
            $cards = $this->cards->pickCards(13, 'deck', $player_id);
        } 

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();


        /************ End of the game initialization *****/
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array( 'players' => array() );
    
        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!
    
        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );
  
        // Cards in player hand
        $result['hand'] = $this->cards->getCardsInLocation( 'hand', $current_player_id );
        
        // Cards played on the table
        $result['cardsontable'] = $this->cards->getCardsInLocation( 'cardsontable' );
  
        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        // TODO: compute and return the game progression

        return 0;
    }

        //////////////////////////////////////////////////////////////////////////////
        //////////// Utility functions
        ////////////    
        /*
     * In this space, you can put any utility methods useful for your game logic
     */
        //////////////////////////////////////////////////////////////////////////////
        //////////// Player actions
        //////////// 
        /*
     * Each time a player is doing some game action, one of the methods below is called.
     * (note: each method below must match an input method in template.action.php)
     */
    function playCard($card_id) {
        self::checkAction("playCard");
        $player_id = self::getActivePlayerId();
        $this->cards->moveCard($card_id, 'cardsontable', $player_id);
        $currentCard = $this->cards->getCard($card_id);
        // XXX check rules here
        // Set the trick color if it hasn't been set yet
        $currentTrickColor = self::getGameStateValue( 'trickColor' ) ;
        if( $currentTrickColor == 0 )
            self::setGameStateValue( 'trickColor', $currentCard['type'] );
        // And notify
        self::notifyAllPlayers('playCard', clienttranslate('${player_name} plays ${value_displayed} ${color_displayed}'), array (
                'i18n' => array ('color_displayed','value_displayed' ),'card_id' => $card_id,'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),'value' => $currentCard ['type_arg'],
                'value_displayed' => $this->values_label [$currentCard ['type_arg']],'color' => $currentCard ['type'],
                'color_displayed' => $this->colors [$currentCard ['type']] ['name'] ));
        // Next player
        $this->gamestate->nextState('playCard');
    }
    
        //////////////////////////////////////////////////////////////////////////////
        //////////// Game state arguments
        ////////////
        /*
     * Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
     * These methods function is to return some additional information that is specific to the current
     * game state.
     */
    function argGiveCards() {
        return array ();
    }

        //////////////////////////////////////////////////////////////////////////////
        //////////// Game state actions
        ////////////
        /*
     * Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
     * The action method of state X is called everytime the current game state is set to X.
     */
    function stNewHand() {
        // Take back all cards (from any location => null) to deck
        $this->cards->moveAllCardsInLocation(null, "deck");
        $this->cards->shuffle('deck');
        // Deal 13 cards to each players
        // Create deck, shuffle it and give 13 initial cards
        $players = self::loadPlayersBasicInfos();
        foreach ( $players as $player_id => $player ) {
            $cards = $this->cards->pickCards(13, 'deck', $player_id);
            // Notify player about his cards
            self::notifyPlayer($player_id, 'newHand', '', array ('cards' => $cards ));
        }
        self::setGameStateValue('alreadyPlayedHearts', 0);
        $this->gamestate->nextState("");
    }

    function stNewTrick() {
        // New trick: active the player who wins the last trick, or the player who own the club-2 card
        // Reset trick color to 0 (= no color)
        self::setGameStateInitialValue('trickColor', 0);
        $this->gamestate->nextState();
    }

    function stNextPlayer() {
        // Active next player OR end the trick and go to the next trick OR end the hand
        if ($this->cards->countCardInLocation('cardsontable') == 4) {
            // This is the end of the trick
            $cards_on_table = $this->cards->getCardsInLocation('cardsontable');
            $best_value = 0;
            $best_value_player_id = null;
            $currentTrickColor = self::getGameStateValue('trickColor');
            foreach ( $cards_on_table as $card ) {
                // Note: type = card color
                if ($card ['type'] == $currentTrickColor) {
                    if ($best_value_player_id === null || $card ['type_arg'] > $best_value) {
                        $best_value_player_id = $card ['location_arg']; // Note: location_arg = player who played this card on table
                        $best_value = $card ['type_arg']; // Note: type_arg = value of the card
                    }
                }
            }
            
            // Active this player => he's the one who starts the next trick
            $this->gamestate->changeActivePlayer( $best_value_player_id );
            
            // Move all cards to "cardswon" of the given player
            $this->cards->moveAllCardsInLocation('cardsontable', 'cardswon', null, $best_value_player_id);
        
            // Notify
            // Note: we use 2 notifications here in order we can pause the display during the first notification
            //  before we move all cards to the winner (during the second)
            $players = self::loadPlayersBasicInfos();
            self::notifyAllPlayers( 'trickWin', clienttranslate('${player_name} wins the trick'), array(
                    'player_id' => $best_value_player_id,
                    'player_name' => $players[ $best_value_player_id ]['player_name']
            ) );
            self::notifyAllPlayers( 'giveAllCardsToPlayer','', array(
                    'player_id' => $best_value_player_id
            ) );
            
            if ($this->cards->countCardInLocation('hand') == 0) {
                // End of the hand
                $this->gamestate->nextState("endHand");
            } else {
                // End of the trick
                $this->gamestate->nextState("nextTrick");
            }
        } else {
            // Standard case (not the end of the trick)
            // => just active the next player
            $player_id = self::activeNextPlayer();
            self::giveExtraTime($player_id);
            $this->gamestate->nextState('nextPlayer');
        }
    }

    function stEndHand() {
            // Count and score points, then end the game or go to the next hand.
        $players = self::loadPlayersBasicInfos();
        // Gets all "hearts" + queen of spades

        $player_to_points = array ();
        foreach ( $players as $player_id => $player ) {
            $player_to_points [$player_id] = 0;
        }
        $cards = $this->cards->getCardsInLocation("cardswon");
        foreach ( $cards as $card ) {
            $player_id = $card ['location_arg'];
            // Note: 2 = heart
            if ($card ['type'] == 2) {
                $player_to_points [$player_id] ++;
            }
        }
        // Apply scores to player
        foreach ( $player_to_points as $player_id => $points ) {
            if ($points != 0) {
                $sql = "UPDATE player SET player_score=player_score-$points  WHERE player_id='$player_id'";
                self::DbQuery($sql);
                $heart_number = $player_to_points [$player_id];
                self::notifyAllPlayers("points", clienttranslate('${player_name} gets ${nbr} hearts and looses ${nbr} points'), array (
                        'player_id' => $player_id,'player_name' => $players [$player_id] ['player_name'],
                        'nbr' => $heart_number ));
            } else {
                // No point lost (just notify)
                self::notifyAllPlayers("points", clienttranslate('${player_name} did not get any hearts'), array (
                        'player_id' => $player_id,'player_name' => $players [$player_id] ['player_name'] ));
            }
        }
        $newScores = self::getCollectionFromDb("SELECT player_id, player_score FROM player", true );
        self::notifyAllPlayers( "newScores", '', array( 'newScores' => $newScores ) );
            
        ///// Test if this is the end of the game
        foreach ( $newScores as $player_id => $score ) {
            if ($score <= -100) {
                // Trigger the end of the game !
                $this->gamestate->nextState("endGame");
                return;
            }
        }
        
        
        $this->gamestate->nextState("nextHand");
    }
    

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
    */

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];
    	
        if ($state['type'] == "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] == "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $sql = "
                UPDATE  player
                SET     player_is_multiactive = 0
                WHERE   player_id = $active_player
            ";
            self::DbQuery( $sql );

            $this->gamestate->updateMultiactiveOrNextState( '' );
            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }
    
///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */
    
    function upgradeTableDb( $from_version )
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345
        
        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            $sql = "ALTER TABLE xxxxxxx ....";
//            self::DbQuery( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            $sql = "CREATE TABLE xxxxxxx ....";
//            self::DbQuery( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }    
}


