// @ts-check
/* global LIB */
/**
 *
 * @package     GZip Plugin
 * @subpackage  System.Gzip *
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */

!(function(LIB, undef) {
	"use strict";

	const merge = LIB.Utils.merge;

	function load(oldImage, observer) {
		const img = new Image();

		if (oldImage.dataset.srcset != undef) {
			img.srcset = oldImage.dataset.srcset;
		}

		if (img.decode != undef) {
			img.src = oldImage.dataset.src;

			img.
				decode().
				then(function() {
					observer.trigger("load", img, oldImage);
				}).
				catch(function() {
					observer.trigger("error", img);
				});
		} else {
			img.onload = function() {
				observer.trigger("load", img, oldImage);
			};

			img.onerror = function() {
				observer.trigger("error", img, oldImage);
			};

			img.src = oldImage.dataset.src;

			if (img.height > 0 && img.height > 0) {
				observer.trigger("load", img, oldImage);
			}
		}
	}

	function complete() {
		this.trigger("complete");
	}

	// return a promise
	LIB.images = merge(Object.create(null), {
        /**
		 *
         * @param string selector
         * @param object options
         */
		lazy: function(selector, options) {
			const images = [].slice.apply((options && options.container || document).querySelectorAll(selector));
			const observer = merge(true, Object.create(null), LIB.Event);
			const io = new IntersectionObserver(function(entries) {
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
}(LIB));