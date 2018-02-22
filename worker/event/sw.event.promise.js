// @ts-check
/* eslint wrap-iife: 0 */
/* global SW, undef */
// promisified event api on(event, handler) => resolve(event, [args...])
// promisified event api on({event: handler, event2: handler2}) => resolve(event, [args...])
!(function() {
	"use strict;";

	const Utils = SW.Utils;
	const extendArgs = Utils.extendArgs;

	const Event = {
		$events: {},
		$pseudo: {},
		// accept (event, handler)
		// Example: promisify('click:once', function () { console.log('clicked'); }) <- the event handler is fired once and removed
		// accept object with events as keys and handlers as values
		// Example promisify({'click:once': function () { console.log('clicked once'); }, 'click': function () { console.log('click'); }})
		promisify: extendArgs(function(name, fn, sticky) {
			const self = this;

			if (fn == undef) {
				return;
			}

			name = name.toLowerCase();

			let i, ev;

			const original = name;
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
			Object.defineProperty(event, "sticky", { value: !!sticky });

			self.$events[name].push(event);
		}),
		off: extendArgs(function(name, fn, sticky) {
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
					(event.fn == fn &&
						(!event.sticky || event.sticky == sticky))
				) {
					self.$events[name].splice(i, 1);
				}
			}

			if (events.length == 0) {
				delete self.$events[name];
			}
		}),
		// return a promise
		resolve(name) {
			name = name.toLowerCase();

			const self = this;
			const args =
				arguments.length > 1 ? [].slice.call(arguments, 1) : [];

			return Promise.all(
				(self.$events[name] || []).concat().map((event) => new Promise((resolve) => {
					resolve(event.cb.apply(self, args));
				}))
			);
		},
		addPseudo(name, fn) {
			this.$pseudo[name] = fn;
			return this;
		}
	};

	Event.addPseudo("once", function(event) {
		event.cb = function() {
			const context = this;

			const value = event.fn.apply(context, arguments);
			context.off(event.name, event.fn);

			return value;
		};

		return this;
	});

	SW.PromiseEvent = Event;
	Utils.merge(true, SW, Event);
})();
