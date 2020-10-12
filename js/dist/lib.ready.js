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

	exports.ready = ready;

	return exports;

}({}));
