// @ts-check

/**
 * async script loader
 * @package     GZip Plugin
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */

LIB.ready(function () {

	const scripts = document.querySelectorAll(
		'script[type="text/foo"],link[data-media]'
	);
	const j = scripts.length;

	let i = 0;

	for (; i < j; i++) {

		if (scripts[i].tagName == 'SCRIPT') {

			setTimeout(
				(function (oldScript, script) {
					return function () {
						const parent = oldScript.parentElement;
						script.text = oldScript.text;

						try {
							parent.insertBefore(script, oldScript);
							parent.removeChild(oldScript);
						} catch (e) {
							console.error(e);
						}
					};
				}(scripts[i], document.createElement("script"))),
				0
			);
		} else {


			setTimeout(
				(function (link) {
					return function () {

						if (link.hasAttribute('data-media')) {
							link.media = link.dataset.media;
							link.removeAttribute("data-media");
						} else {
							link.removeAttribute("media");
						}
					};
				}(scripts[i])),
				0
			);
		}
	}
});