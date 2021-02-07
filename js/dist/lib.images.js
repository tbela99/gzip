var LIB = (function (exports) {
	'use strict';

	// @ts-check

	const queue = [];
	let fired = document.readyState != 'loading';

	function domReady() {

		document.removeEventListener('DOMContentLoaded', domReady);
		document.removeEventListener('readystatechange', readystatechange);
		fired = true;

		while (queue.length > 0) {

			requestAnimationFrame(queue.shift());
		}
	}

	function readystatechange() {

		switch (document.readyState) {

			case 'loading':
				break;

			case 'interactive':
			default:

				domReady();
				break;
		}
	}

	document.addEventListener('DOMContentLoaded', domReady);
	document.addEventListener('readystatechange', readystatechange);

	function ready(cb) {

		if (fired) {

			while (queue.length > 0) {

				requestAnimationFrame(queue.shift());
			}

			cb();
		} else {

			queue.push(cb);
		}
	}

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
	const undef = null;

	// @ts-check

	function extendArgs(fn) {
		return function (key) {
			if (typeof key == "object") {
				const args = Array.prototype.slice.call(arguments, 1);
				let k;

				for (k in key) {
					fn.apply(this, [k, key[k]].concat(args));
				}
			} else {
				fn.apply(this, arguments);
			}

			return this;
		};
	}

	function merge(target) {
		const args = Array.prototype.slice.call(arguments, 1);
		let deep = typeof target == "boolean",
			i,
			source,
			prop,
			value;

		if (deep) {
			deep = target;
			target = args.shift();
		}

		for (i = 0; i < args.length; i++) {
			source = args[i];

			for (prop in source) {
				value = source[prop];

				switch (typeof value) {
					case "object":
						if (value == undef || !deep) {
							target[prop] = value;
						} else {
							target[prop] = merge(
								deep,
								typeof source[prop] == "object" &&
								source[prop] != undef ?
								source[prop] :
								value instanceof Array ? [] : {},
								value
							);
						}
						break;

					default:
						target[prop] = value;
						break;
				}
			}
		}

		return target;
	}

	// @ts-check

	const Event = {
	  $events: {},
	  $pseudo: {},
	  on: extendArgs(function (name, fn, sticky) {
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
	  off: extendArgs(function (name, fn, sticky) {
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
	  trigger: function (name) {
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
	  addPseudo: function (name, fn) {
	    this.$pseudo[name] = fn;
	    return this;
	  }
	};


	//LIB.Event = Event;
	//})(LIB);

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

	Event.addPseudo("once", function (event) {
	    event.cb = function () {
	        const context = this;

	        const result = event.fn.apply(context, arguments);
	        context.off(event.name, event.fn);

	        return result;
	    };
	});
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

	// @ts-check

	/**
	 * legacy srcset support
	 * @param {HTMLImageElement} image
	 * @returns {Function} update
	 */
	function rspimages(image) {
	  let mq;

	  const mqs = image
	    .getAttribute("sizes")
	    .replace(/\)\s[^,$]+/g, ")")
	    .split(",");
	  const images = image.dataset.srcset.split(",").map(function (src) {
	    return src.split(" ")[0];
	  });

	  if (typeof window.CustomEvent != "function") {
	    function CustomEvent(event, params) {
	      params = params || {
	        bubbles: false,
	        cancelable: false,
	        detail: undefined
	      };
	      const evt = document.createEvent("CustomEvent");
	      evt.initCustomEvent(
	        event,
	        params.bubbles,
	        params.cancelable,
	        params.detail
	      );
	      return evt;
	    }

	    CustomEvent.prototype = window.Event.prototype;
	  }

	  function createEvent(name, params) {
	    try {
	      return new CustomEvent(name, params);
	    } catch (e) {}

	    const evt = document.createEvent("CustomEvent");
	    evt.initEvent(
	      name,
	      params && params.bubbles,
	      params == undef || (params && params.cancelable),
	      params && params.details
	    );

	    return evt;
	  }

	  function update() {
	    let i = 0;
	    const j = mqs.length;

	    for (; i < j; i++) {
	      if (matchMedia(mqs[i]).matches) {
	        if (mqs[i] != mq) {
	          mq = mqs[i];
	          image.src = images[i];
	          image.dispatchEvent(createEvent("sourcechange"));
	        }

	        break;
	      }
	    }
	  }

	  window.addEventListener("resize", update, false);
	  update();

	  return update;
	}

	function load(oldImage, observer) {
	  const img = new Image();

	  img.src = oldImage.dataset.src != undef ? oldImage.dataset.src : oldImage.src;

	  if (oldImage.dataset.srcset != undef && window.matchMedia) {
	    if (!("srcset" in img)) {
	      if (oldImage.dataset.srcset != undef) {
	        img.dataset.srcset = oldImage.dataset.srcset;
	      }
	      if (oldImage.hasAttribute("sizes")) {
	        img.setAttribute("sizes", oldImage.getAttribute("sizes"));

	        const update = rspimages(img);

	        img.addEventListener(
	          "load",
	          function () {
	            window.removeEventListener("resize", update, false);
	            rspimages(oldImage);
	          },
	          false
	        );
	      }
	    } else {
	      if (oldImage.dataset.srcset != undef) {
	        img.srcset = oldImage.dataset.srcset;
	      }
	    }
	  }

	  observer.trigger("preload", img, oldImage);

	  if (img.decode != undef) {
	    img
	      .decode()
	      .then(function () {
	        observer.trigger("load", img, oldImage);
	      })
	      .catch(function (error) {
	        observer.trigger("error", error, img, oldImage);
	      });
	  } else {
	    img.onerror = function (error) {
	      observer.trigger("error", error, img, oldImage);
	    };

	    if (img.height > 0 && img.width > 0) {
	      observer.trigger("load", img, oldImage);
	    } else {
	      img.onload = function () {
	        observer.trigger("load", img, oldImage);
	      };
	    }
	  }
	}

	function complete() {
	  this.trigger("complete");
	}

	const images = merge(Object.create(null), {
	  /**
	   *
	   * @param string selector
	   * @param object options
	   */
	  lazy(selector, options) {
	    const images = [].slice.apply(
	      ((options && options.container) || document).querySelectorAll(selector)
	    );
	    const observer = merge(true, Object.create(null), Event);
	    const io = new IntersectionObserver(function (entries) {
	      let i = entries.length,
	        index,
	        entry;

	      while (i--) {
	        entry = entries[i];

	        if (entry.isIntersecting) {
	          io.unobserve(entry.target);

	          index = images.indexOf(entry.target);
	          if (index != -1) {
	            images.splice(index, 1);
	          }

	          if (images.length == 0) {
	            observer.on({
	              "load:once": complete,
	              "fail:once": complete
	            });
	          }

	          load(entry.target, observer);
	        }
	      }
	    }, options);

	    let i = images.length;

	    while (i--) {
	      io.observe(images[i]);
	    }

	    return observer;
	  }
	});
	//})(LIB);

	// @ts-check


	const ready$1 = ready;
	const images$1 = images;

	exports.images = images$1;
	exports.ready = ready$1;

	return exports;

}({}));
