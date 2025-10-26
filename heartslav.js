/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Heartslav implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * heartslav.js
 *
 * Heartslav user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
  "dojo",
  "dojo/_base/declare",
  "ebg/core/gamegui",
  "ebg/counter",
  getLibUrl("bga-animations", "1.x"), // the lib uses bga-animations so this is required!
  getLibUrl("bga-cards", "1.0.7"),
], function (dojo, declare, gamegui, counter, BgaAnimations, BgaCards) {
  return declare("bgagame.heartslav", ebg.core.gamegui, {
    constructor: function () {
      console.log("heartslav constructor");

      // Here, you can init the global variables of your user interface
      // Example:
      // this.myGlobalValue = 0;
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
      console.log("Starting game setup", gamedatas);

      document.getElementById("game_play_area").insertAdjacentHTML(
        "beforeend",
        `
                <div id="myhand_wrap" class="whiteblock">
                    <b id="myhand_label">${_("My hand")}</b>
                    <div id="myhand">
                       
                    </div>
                </div>

            `
      );
      // Example to add a div on the game area
      this.getGameAreaElement().insertAdjacentHTML(
        "beforeend",
        `
                <div id="player-tables"></div>
            `
      );
      //<span class="dealer_token" id="dealer_token_p${player.id}">🃏 </span>
      // Setting up player boards
      const numPlayers = Object.keys(gamedatas.players).length;
      Object.values(gamedatas.players).forEach((player, index) => {
        document.getElementById("player-tables").insertAdjacentHTML(
          "beforeend",
          // we generate this html snippet for each player
          `
    <div class="playertable whiteblock playertable_${index}">
        <div class="playertablename" style="color:#${player.color};">${player.name}</div>
        <div id="cardswon_${player.id}" class="cardswon"></div>
        <div id="tableau_${player.id}" class="tableau"></div>
    </div>
    `
        );
      });

      // Hide hand zone from spectators
      if (this.isSpectator)
        document.getElementById("myhand_wrap").style.display = "none";

      // create the animation manager, and bind it to the `game.bgaAnimationsActive()` function
      this.animationManager = new BgaAnimations.Manager({
        animationsActive: () => this.bgaAnimationsActive(),
      });

      const cardWidth = 100;
      const cardHeight = 135;

      // create the card manager
      this.cardsManager = new BgaCards.Manager({
        animationManager: this.animationManager,
        type: "ha-card",
        getId: (card) => card.id,

        cardWidth: cardWidth,
        cardHeight: cardHeight,
        cardBorderRadius: "5%",
        setupFrontDiv: (card, div) => {
          div.dataset.type = card.type; // suit 1..4
          div.dataset.typeArg = card.type_arg; // value 2..14
          div.style.backgroundPositionX = `calc(100% / 14 * (${card.type_arg} - 2))`; // 14 is number of columns in stock image minus 1
          div.style.backgroundPositionY = `calc(100% / 3 * (${card.type} - 1))`; // 3 is number of rows in stock image minus 1
          this.addTooltipHtml(div.id, `tooltip of ${card.type}`);
        },
      });

      // create the stock, in the game setup
      this.handStock = new BgaCards.HandStock(
        this.cardsManager,
        document.getElementById("myhand"),
        {
          sort: BgaCards.sort("type", "type_arg"),
        }
      );

      this.handStock.onCardClick = (card) => {
        this.onCardClick(card);
      };

      // Cards in player's hand

      this.handStock.addCards(Array.from(Object.values(this.gamedatas.hand)));

      // map stocks

      this.tableauStocks = [];
      Object.values(gamedatas.players).forEach((player, index) => {
        // add player tableau stock
        const stock = new BgaCards.LineStock(
          this.cardsManager,
          document.getElementById(`tableau_${player.id}`)
        );

        this.tableauStocks[player.id] = stock;

        // add void stock
        new BgaCards.VoidStock(
          this.cardsManager,
          document.getElementById(`cardswon_${player.id}`),
          {
            fadeOut: true, // not working
            toPlaceholder: "shrink", // not working
            autoPlace: (card) =>
              card.location === "cardswon" && card.location_arg == player.id,
          }
        );
      });

      // for (let i in handCards) {
      //   this.tableauStocks[this.player_id].addCards([handCards[i]]);
      //   if (i > 3) break;
      // }

      // Cards played on table
      for (i in this.gamedatas.cardsontable) {
        var card = this.gamedatas.cardsontable[i];
        var player_id = card.location_arg;
        this.tableauStocks[player_id].addCards([card]);
      }

      // TODO: Set up your game interface here, according to "gamedatas"

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
        case "PlayerTurn":
          if (this.isCurrentPlayerActive()) {
            // Check playable cards received from argPlayerTurn() in php

            const playableCardIds = args.args._private.playableCards.map((x) =>
              parseInt(x)
            ); 

            const allCards = this.handStock.getCards();
            const playableCards = allCards.filter(
              (card) => playableCardIds.includes(parseInt(card.id)) // never know if we get int or string, this method cares
            );
            this.handStock.setSelectionMode("single", playableCards);
          }
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

    /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */

    ///////////////////////////////////////////////////
    //// Player's action

    /*
        
            Here, you are defining methods to handle player's action (ex: results of mouse click on 
            game objects).
            
            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server
        
        */

    // Example:

    onCardClick: function (card) {
      console.log("onCardClick", card);
      if (!card) return; // hmm
      switch (this.gamedatas.gamestate.name) {
        case "PlayerTurn":
          // Can play a card
          this.bgaPerformAction("actPlayCard", {
            cardId: card.id, // this corresponds to the argument name in php, so it needs to be exactly the same
          });
          break;
        case "GiveCards":
          // Can give cards TODO
          break;
        default: {
          this.handStock.unselectAll();
          break;
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

      // automatically listen to the notifications, based on the `notif_xxx` function on this class.
      this.bgaSetupPromiseNotifications();
    },

    // TODO: from this point and below, you can write your game notifications handling methods

    notif_newHand: function (args) {
      // We received a new full hand of 13 cards.

      this.handStock.removeAll();
      this.handStock.addCards(Array.from(Object.values(args.cards)));
    },

    notif_playCard: function (args) {
      // Play a card on the table
      this.tableauStocks[args.player_id].addCards([args.card]);
    },

    notif_trickWin: async function () {
      // We do nothing here (just wait in order players can view the 4 cards played before they're gone)
    },
    notif_giveAllCardsToPlayer: async function (args) {
      // Move all cards on table to given table, then destroy them
      const winner_id = args.player_id;

      const cards = Array.from(Object.values(args.cards));
      await this.tableauStocks[winner_id].addCards(cards);
      await this.cardsManager.placeCards(cards); // auto-placement
    },
  });
});
