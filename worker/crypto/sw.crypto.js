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

// @ts-check
/* eslint wrap-iife: 0 */
/* global SW */

SW.crypto = (function() {
	function hex(buffer) {
		var hexCodes = [];
		var view = new DataView(buffer);
		var i = 0;
		var value, stringValue, paddedValue, padding;

		for (; i < view.byteLength; i += 4) {
			// Using getUint32 reduces the number of iterations needed (we process 4 bytes each time)
			value = view.getUint32(i);
			// toString(16) will give the hex representation of the number without padding
			stringValue = value.toString(16);
			// We use concatenation and slice for padding
			padding = "00000000";
			paddedValue = (padding + stringValue).slice(-padding.length);
			hexCodes.push(paddedValue);
		}

		// Join all the hex strings into one
		return hexCodes.join("");
	}

	return {
		/**
		 * provide SHA-1 -> SAH-512 hash
		 * @var algo 'SHA-1' | 'SHA-256' | 'SHA-384' | 'SHA-512'
		 * @return Promise
		 */
		digest(algo, str) {
			// We transform the string into an arraybuffer.
			var buffer = new TextEncoder("utf-8").encode(str);
			return crypto.subtle.digest(algo, buffer).then(function(hash) {
				return hex(hash);
			});
		}
	};
})();
