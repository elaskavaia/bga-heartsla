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

      // Cards in player's hand
      for (var i in this.gamedatas.hand) {
        var card = this.gamedatas.hand[i];
        var color = card.type;
        var value = card.type_arg;
        this.playerHand.addToStockWithId(this.getCardUniqueId(color, value), card.id);
      }

      // Cards played on table
      for (i in this.gamedatas.cardsontable) {
        var card = this.gamedatas.cardsontable[i];
        var color = card.type;
        var value = card.type_arg;
        var player_id = card.location_arg;
        this.playCardOnTable(player_id, color, value, card.id);
      }

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

    playCardOnTable: function (player_id, color, value, card_id) {
      // player_id => direction
      this.addTableCard(value, color, player_id, player_id);

      if (player_id != this.player_id) {
        // Some opponent played a card
        // Move card from player panel
        this.placeOnObject("cardontable_" + player_id, "overall_player_board_" + player_id);
      } else {
        // You played a card. If it exists in your hand, move card from there and remove
        // corresponding item

        if ($("myhand_item_" + card_id)) {
          this.placeOnObject("cardontable_" + player_id, "myhand_item_" + card_id);
          this.playerHand.removeFromStockById(card_id);
        }
      }

      // In any case: move it to its final destination
      this.slideToObject("cardontable_" + player_id, "playertablecard_" + player_id).play();
    },

    addTableCard(value, color, card_player_id, playerTableId) {
      const x = value - 2;
      const y = color - 1;
      document.getElementById("playertablecard_" + playerTableId).insertAdjacentHTML(
        "beforeend",
        `
            <div class="card cardontable" id="cardontable_${card_player_id}" style="background-position:-${x}00% -${y}00%"></div>
        `
      );
    },
    ///////////////////////////////////////////////////
    //// Player's action

    onPlayerHandSelectionChanged: function () {
      var items = this.playerHand.getSelectedItems();

      if (items.length > 0) {
        var action = "actPlayCard";
        if (this.checkAction(action, true)) {
          // Can play a card
          var card_id = items[0].id;
          this.bgaPerformAction(action, {
            card_id: card_id
          });

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

    setupNotifications : function() {
        console.log('notifications subscriptions setup');

        // table of notif type to delay in milliseconds
        const notifs = [
            ['newHand', 1],
            ['playCard', 100],
            ['trickWin', 1000],
            ['giveAllCardsToPlayer', 600],
            ['newScores', 1],
        ];

        notifs.forEach((notif) => {
            dojo.subscribe(notif[0], this, `notif_${notif[0]}`);
            this.notifqueue.setSynchronous(notif[0], notif[1]);
        });

    },

    notif_newHand : function(notif) {
        // We received a new full hand of 13 cards.
        this.playerHand.removeAll();

        for ( var i in notif.args.cards) {
            var card = notif.args.cards[i];
            var color = card.type;
            var value = card.type_arg;
            this.playerHand.addToStockWithId(this.getCardUniqueId(color, value), card.id);
        }
    },

    notif_playCard : function(notif) {
        // Play a card on the table
        this.playCardOnTable(notif.args.player_id, notif.args.color, notif.args.value, notif.args.card_id);
    },

    notif_trickWin : function(notif) {
        // We do nothing here (just wait in order players can view the 4 cards played before they're gone.
    },
    notif_giveAllCardsToPlayer : function(notif) {
        // Move all cards on table to given table, then destroy them
        var winner_id = notif.args.player_id;
        for ( var player_id in this.gamedatas.players) {
            var anim = this.slideToObject('cardontable_' + player_id, 'overall_player_board_' + winner_id);
            dojo.connect(anim, 'onEnd', function(node) {
                dojo.destroy(node);
            });
            anim.play();
        }
    },
    notif_newScores : function(notif) {
        // Update players' scores
        for ( var player_id in notif.args.newScores) {
            this.scoreCtrl[player_id].toValue(notif.args.newScores[player_id]);
        }
    },
  });
});
