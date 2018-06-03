// @ts-check
/* eslint wrap-iife: 0 */
/* global LIB */

/**
 *
 * @package     GZip Plugin
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */
!(function(LIB, undef) {
  "use strict;";

  const Utils = LIB.Utils;
  const Event = {
    $events: {},
    $pseudo: {},
    on: Utils.extendArgs(function(name, fn, sticky) {
      // 'click:delay(500)'.match(/([^:]+):([^(]+)(\(([^)]+)\))?/)
      // Array [ "click:delay(400)", "click", "delay", "(400)", "400" ]

      const self = this;

      if (fn == undef) {
        return;
      }

      name = name.toLowerCase();

      const original = name;

      let i, ev;

      const event = {
        fn: fn,
        cb: fn,
        name: name,
        original: name,
        parsed: [name]
      };

      if (name.indexOf(":") != -1) {
        const parsed = name.match(/([^:]+):([^(]+)(\(([^)]+)\))?/);

        if (parsed == undef) {
          event.name = name = name.split(":", 1)[0];
        } else {
          event.original = name;
          event.name = parsed[1];
          event.parsed = parsed;
          name = parsed[1];

          if (parsed[2] in self.$pseudo) {
            self.$pseudo[parsed[2]](event);
          }
        }
      }

      if (!(name in self.$events)) {
        self.$events[name] = [];
      }

      i = self.$events[name].length;

      while (i && i--) {
        ev = self.$events[name][i];

        if (ev.fn == fn && ev.original == original) {
          return;
        }
      }

      //    sticky = !!sticky;

      Object.defineProperty(event, "sticky", {
        value: !!sticky
      });

      self.$events[name].push(event);
    }),
    off: Utils.extendArgs(function(name, fn, sticky) {
      const self = this;
      let undef, event, i;

      name = name.toLowerCase().split(":", 1)[0];

      const events = self.$events[name];

      if (events == undef) {
        return;
      }

      sticky = !!sticky;

      i = events.length;

      while (i && i--) {
        event = events[i];

        // do not remove sticky events, unless sticky === true
        if (
          (fn == undef && !sticky) ||
          (event.fn == fn && (!event.sticky || event.sticky == sticky))
        ) {
          self.$events[name].splice(i, 1);
        }
      }

      if (events.length == 0) {
        delete self.$events[name];
      }
    }),
    trigger: function(name) {
      name = name.toLowerCase();

      const self = this;

      if (!(name in self.$events)) {
        return self;
      }

      let i;
      const args =
        arguments.length > 1 ? Array.prototype.slice.call(arguments, 1) : [];
      const events = self.$events[name].concat();

      for (i = 0; i < events.length; i++) {
        events[i].cb.apply(self, args);
      }

      return self;
    },
    addPseudo: function(name, fn) {
      this.$pseudo[name] = fn;
      return this;
    }
  };

  Event.addPseudo("once", function(event) {
    event.cb = function() {
      const context = this;

      const result = event.fn.apply(context, arguments);
      context.off(event.name, event.fn);

      return result;
    };
  })
    /*
        addPseudo('times', function (event) {

            let context = this, times = event.parsed[4] == undef && 1 || +event.parsed[4];

            event.cb = function () {

                event.fn.apply(context, arguments);

                if(--times <= 0) {

                    context.off(event.name, event.fn);
                }
            }

        }).*/
    .addPseudo("pause", function(event) {
      event.cb = Utils.pause(
        event.fn,
        (event.parsed[4] == undef && 250) || +event.parsed[4]
      );
    })
    .addPseudo("throttle", function(event) {
      event.cb = Utils.throttle(
        event.fn,
        (event.parsed[4] == undef && 250) || +event.parsed[4]
      );
    });

  LIB.Event = Event;
})(LIB);
