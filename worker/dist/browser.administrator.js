/**
 * Service worker browser client
 * @package     GZip Plugin
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */

// @ts-check

// build b37168b 2021-12-24 17:13:52-05:00

if ("serviceWorker" in navigator) {
	navigator.serviceWorker.
		register("{scope}worker{debug}.js", {scope: "{scope}"}).
		//    .then(function(registration) {

		//    console.log("🍻");
		//    })
		catch(function(error) {
			//	console.log(error);
			console.error("😭", error);
		});
}
