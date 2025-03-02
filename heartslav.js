/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * heartslav implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * heartslav.js
 *
 * heartslav user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

const DIRECTIONS = {
  3: ["S", "W", "E"],
  4: ["S", "W", "N", "E"]
};

define([
  "dojo",
  "dojo/_base/declare",
  "ebg/core/gamegui",
  "ebg/counter",
  "ebg/stock" /// <==== HERE
], function (dojo, declare) {
  return declare("bgagame.heartslav", ebg.core.gamegui, {
    constructor: function () {
      console.log("hearts constructor");
      this.cardwidth = 72;
      this.cardheight = 96;
    },

    /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */

    setup: function (gamedatas) {
      console.log("Starting game setup");

      document.getElementById("game_play_area").insertAdjacentHTML(
        "beforeend",
        `<div id="myhand_wrap" class="whiteblock">
                    <b id="myhand_label">${_("My hand")}</b>
   <div id="myhand">
       <div class="playertablecard"></div>
    </div>
                </div>`
      );

      // Example to add a div on the game area
      document.getElementById("game_play_area").insertAdjacentHTML(
        "beforeend",
        `
                            <div id="player-tables"></div>
                        `
      );

      // Setting up player boards
      var numPlayers = Object.keys(gamedatas.players).length;
      Object.values(gamedatas.players).forEach((player, index) => {
        document.getElementById("player-tables").insertAdjacentHTML(
          "beforeend",
          `
    <div class="playertable whiteblock playertable_${DIRECTIONS[numPlayers][index]}">
        <div class="playertablename" style="color:#${player.color};"><span class="dealer_token" id="dealer_token_p${player.id}">🃏 </span>${player.name}</div>
        <div class="playertablecard" id="playertablecard_${player.id}"></div>
        <div class="playertablename" id="hand_score_wrap_${player.id}"><span class="hand_score_label"></span> <span id="hand_score_${player.id}"></span></div>
    </div>
                `
        );
      });

      //   // example of setting up players boards
      //   this.getPlayerPanelElement(player.id).insertAdjacentHTML(
      //     "beforeend",
      //     `
      //               <div id="player-counter-${player.id}">A player counter</div>
      //           `
      //   );

      // TODO: Set up your game interface here, according to "gamedatas"

      // Player hand
      this.playerHand = new ebg.stock(); // new stock object for hand
      this.playerHand.create(this, $("myhand"), this.cardwidth, this.cardheight);
      this.playerHand.image_items_per_row = 13; // 13 images per row

      // Create cards types:
      for (var color = 1; color <= 4; color++) {
        for (var value = 2; value <= 14; value++) {
          // Build card type id
          var card_type_id = this.getCardUniqueId(color, value);
          this.playerHand.addItemType(card_type_id, card_type_id, g_gamethemeurl + "img/cards.jpg", card_type_id);
        }
      }

      dojo.connect(this.playerHand, "onChangeSelection", this, "onPlayerHandSelectionChanged");

      // 2 = hearts, 5 is 5, and 42 is the card id, which normally would come from db
      this.playerHand.addToStockWithId(this.getCardUniqueId(2, 5), 42);

      // Setup game notifications to handle (see "setupNotifications" method below)
      this.setupNotifications();

      console.log("Ending game setup");
    },

    ///////////////////////////////////////////////////
    //// Game & client states

    // onEnteringState: this method is called each time we are entering into a new game state.
    //                  You can use this method to perform some user interface changes at this moment.
    //
    onEnteringState: function (stateName, args) {
      console.log("Entering state: " + stateName, args);

      switch (stateName) {
        /* Example:
            
            case 'myGameState':
            
                // Show some HTML block at this game state
                dojo.style( 'my_html_block_id', 'display', 'block' );
                
                break;
           */

        case "dummy":
          break;
      }
    },

    // onLeavingState: this method is called each time we are leaving a game state.
    //                 You can use this method to perform some user interface changes at this moment.
    //
    onLeavingState: function (stateName) {
      console.log("Leaving state: " + stateName);

      switch (stateName) {
        /* Example:
            
            case 'myGameState':
            
                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );
                
                break;
           */

        case "dummy":
          break;
      }
    },

    // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
    //                        action status bar (ie: the HTML links in the status bar).
    //
    onUpdateActionButtons: function (stateName, args) {
      console.log("onUpdateActionButtons: " + stateName, args);

      if (this.isCurrentPlayerActive()) {
        switch (stateName) {
          case "playerTurn":
            const playableCardsIds = args.playableCardsIds; // returned by the argPlayerTurn

            // Add test action buttons in the action status bar, simulating a card click:
            playableCardsIds.forEach((cardId) =>
              this.statusBar.addActionButton(_("Play card with id ${card_id}").replace("${card_id}", cardId), () =>
                this.onCardClick(cardId)
              )
            );

            this.statusBar.addActionButton(_("Pass"), () => this.bgaPerformAction("actPass"), { color: "secondary" });
            break;
        }
      }
    },

    ///////////////////////////////////////////////////
    //// Utility methods

    // Get card unique identifier based on its color and value
    getCardUniqueId: function (color, value) {
      return (color - 1) * 13 + (value - 2);
    },
    ///////////////////////////////////////////////////
    //// Player's action

    onPlayerHandSelectionChanged: function () {
      var items = this.playerHand.getSelectedItems();

      if (items.length > 0) {
        if (this.checkAction("actPlayCard", true)) {
          // Can play a card

          var card_id = items[0].id;
          console.log("on playCard " + card_id);

          this.playerHand.unselectAll();
        } else if (this.checkAction("actGiveCards")) {
          // Can give cards => let the player select some cards
        } else {
          this.playerHand.unselectAll();
        }
      }
    },

    ///////////////////////////////////////////////////
    //// Reaction to cometD notifications

    /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your heartslav.game.php file.
        
        */
    setupNotifications: function () {
      console.log("notifications subscriptions setup");

      // TODO: here, associate your game notifications with local methods

      // Example 1: standard notification handling
      // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );

      // Example 2: standard notification handling + tell the user interface to wait
      //            during 3 seconds after calling the method in order to let the players
      //            see what is happening in the game.
      // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
      // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
      //
    }

    // TODO: from this point and below, you can write your game notifications handling methods

    /*
        Example:
        
        notif_cardPlayed: function( notif )
        {
            console.log( 'notif_cardPlayed' );
            console.log( notif );
            
            // Note: notif.args contains the arguments specified during you "notifyAllPlayers" / "notifyPlayer" PHP call
            
            // TODO: play the card in the user interface.
        },    
        
        */
  });
});
