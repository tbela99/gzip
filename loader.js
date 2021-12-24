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

	function runScripts() {
		const scripts = document.querySelectorAll('script[type="text/script"],script[type="text/module"]');
		const j = scripts.length;
		let i = 0;

		for (; i < j; i++) {

			setTimeout(
				(function (oldScript, script) {
					return function () {
						const parent = oldScript.parentElement;
						script.text = oldScript.text;

						if (oldScript.type == 'text/module') {

							script.type = 'module';
						}

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

		const module = document.querySelector('template[data-type=module]');

		if (module) {

			setTimeout(function () {

				module.parentElement.insertBefore(module.content, module);
				module.remove();
			}, 0)
		}
	}

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
		 'script[data-async]'
	 );

	let count = scripts.length;

	if (count === 0) {

		runScripts();
	}

	else {

		for (i = 0; i < count; i++) {

			scripts[i].addEventListener('load', function () {

				count--;

				if (count == 0) {

					runScripts()
				}
			})
		}
	}
});