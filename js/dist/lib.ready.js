var LIB = (function (exports) {
	'use strict';

	// @ts-check

	const queue = [];
	let fired = document.readyState != 'loading';

	function readystatechange() {

		switch (document.readyState) {

			case 'loading':
				break;

			case 'interactive':
			default:

				fired = true;

				while (queue.length > 0) {

					requestAnimationFrame(queue.shift());
				}

				document.removeEventListener('readystatechange', readystatechange);
				break;
		}
	}

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
