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

function il(position) {
	const scripts = document[position].querySelectorAll(
		'script[type="text/foo"]'
	);
	const j = scripts.length;

	let i = 0;

	for (; i < j; i++) {
		setTimeout(
			(function(oldScript, script) {
				return function() {
					const parent = oldScript.parentElement;
					script.text = oldScript.text;

					try {
						parent.insertBefore(script, oldScript);
						parent.removeChild(oldScript);
					} catch (e) {
						console.error(e);
						console.log(script.text);
					}
				};
			}(scripts[i], document.createElement("script"))),
			0
		);
	}
}
