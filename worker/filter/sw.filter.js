// @ts-check
/* eslint wrap-iife: 0 */
/* global SW, scope, undef */

SW.Filter = (function(SW) {
	const prefetchRule = "prefetch.rule";
	const postfetchRule = "postfetch.rule";
	const map = new Map();
	const Filter = {
		Rules: {
			Prefetch: prefetchRule,
			Postfetch: postfetchRule
		},
		validators: map,
		addRule: (type, regExp) => {
			if (type != prefetchRule && type != postfetchRule) {
				throw new Error("Invalid rule type");
			}

			let validators = map.get(type);

			if (validators == undef) {
				validators = [];
				map.set(type, validators);
			}

			validators.push(regExp);
		}
	};

	SW.promisify({
		prefetch: function(request) {
			console.info("prefetch");

			const url = request.url;
			const excludeSet = map.get(prefetchRule);

			if (excludeSet != undef) {
				let i = 0;

				for (; i < excludeSet.length; i++) {
					if (excludeSet[i](request)) {
						throw new Error("Url not allowed " + url);
					}
				}
			}

			console.info({request});
			return request;
		},
		postfetch: function(request, response) {
			console.info("postfetch");

			//	const url = request.url;
			//	const excludeSet = map.get(postfetchRule);

			//	if (excludeSet != undef) {
			//		let i = 0;

			//		for (; i < excludeSet.length; i++) {
			//			if (excludeSet[i](request)) {
			//				throw new Error("Url not allowed " + url);
			//			}
			//		}
			//	}

			console.info({request, response});

			return response;
		}
	});

	return Filter;
})(SW);
