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

	const links = document.querySelectorAll(
		'link[data-media]'
	);
	const j = links.length;

	let i = 0, link;

	for (; i < j; i++) {

		link = links[i];

		if (link.hasAttribute('data-media')) {
			link.media = link.dataset.media;
			link.removeAttribute("data-media");
		} else {
			link.removeAttribute("media");
		}
	}

	 const scripts = document.querySelectorAll(
		 'script[data-async][async]'
	 );

	let count = scripts.length;

	for (i = 0; i < count; i++) {

		scripts[i].addEventListener('load', function () {

			count--;

			if (count == 0) {

				const scripts = document.querySelectorAll('script[type="text/foo"]');
				const j = scripts.length;
				let i = 0;

				for (; i < j; i++) {

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
				}
			}
		})
	}
});