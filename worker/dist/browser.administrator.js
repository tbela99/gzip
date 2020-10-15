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

// build 77ad4d7 2020-10-14 21:54:44-04:00

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
