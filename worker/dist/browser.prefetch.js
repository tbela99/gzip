var prefetch = (function (exports) {
	'use strict';

	// @ts-check

	const queue = [];
	let fired = document.readyState != 'loading';

	function domReady() {

		document.removeEventListener('DOMContentLoaded', domReady);
		fired = true;

		while (queue.length > 0) {

			requestAnimationFrame(queue.shift());
		}
	}

	document.addEventListener('DOMContentLoaded', domReady);

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

	/*! instant.page v2.0.0 - (C) 2019 Alexandre Dieulot - https://instant.page/license */

	let urlToPreload;
	const prefetcher = document.createElement('link');

	const allowQueryString = 'instantAllowQueryString' in document.body.dataset;
	const allowExternalLinks = 'instantAllowExternalLinks' in document.body.dataset;
	const filterMode = document.body.dataset.instantFilterType;
	const filters = JSON.parse(document.body.dataset.instantFilters);
	const preloadedUrls = new Set;

	// preload urls only once
	preloadedUrls.add('' + window.location);

	ready(function () {

	    let mouseoverTimer;
	    let lastTouchTimestamp;

	    const isSupported = prefetcher.relList && prefetcher.relList.supports && prefetcher.relList.supports('prefetch');
	    const isDataSaverEnabled = navigator.connection && navigator.connection.saveData;

	    let delayOnHover = +document.body.dataset.instantIntensity || 65;
	    let trigger = document.body.dataset.instantTrigger;

	    if (!isSupported || isDataSaverEnabled) {

	        return
	    }

	    prefetcher.rel = 'prefetch';
	    document.head.appendChild(prefetcher);

	    const eventListenersOptions = {
	        capture: true,
	        passive: true,
	    };

	    if (!('mousedown' in document.documentElement) && ('ontouchstart' in document.documentElement)) {

	        trigger = 'touchstart';
	    }

	    document.addEventListener(trigger, trigger === 'touchstart' ? touchstartListener : (trigger === 'mousedown' ? mousedownListener : mouseoverListener), eventListenersOptions);

	    function touchstartListener(event) {
	        /* Chrome on Android calls mouseover before touchcancel so `lastTouchTimestamp`
	         * must be assigned on touchstart to be measured on mouseover. */
	        lastTouchTimestamp = performance.now();

	        const linkElement = event.target.closest('a');

	        if (!isPreloadable(linkElement)) {
	            return
	        }

	        linkElement.addEventListener('touchcancel', touchendAndTouchcancelListener, {
	            passive: true
	        });
	        linkElement.addEventListener('touchend', touchendAndTouchcancelListener, {
	            passive: true
	        });

	        urlToPreload = linkElement.href;
	        preload(linkElement.href);
	    }

	    function touchendAndTouchcancelListener() {
	        urlToPreload = undefined;
	        stopPreloading();
	    }

	    function mouseoverListener(event) {
	        if (performance.now() - lastTouchTimestamp < 1100) {
	            return
	        }

	        const linkElement = event.target.closest('a');

	        if (!isPreloadable(linkElement)) {
	            return
	        }

	        linkElement.addEventListener('mouseout', mouseoutListener, {
	            passive: true
	        });

	        urlToPreload = linkElement.href;

	        mouseoverTimer = setTimeout(() => {
	            preload(linkElement.href);
	            mouseoverTimer = undefined;
	        }, delayOnHover);
	    }

	    function mousedownListener(event) {
	        const linkElement = event.target.closest('a');

	        if (!isPreloadable(linkElement)) {
	            return
	        }

	        linkElement.addEventListener('mouseout', mouseoutListener, {
	            passive: true
	        });

	        urlToPreload = linkElement.href;

	        preload(linkElement.href);
	    }

	    function mouseoutListener(event) {
	        if (event.relatedTarget && event.target.closest('a') === event.relatedTarget.closest('a')) {
	            return
	        }

	        if (mouseoverTimer) {
	            clearTimeout(mouseoverTimer);
	            mouseoverTimer = undefined;
	        }

	        urlToPreload = undefined;

	        stopPreloading();
	    }
	});

	function isPreloadable(linkElement) {
	    if (!linkElement ||
	        !linkElement.href ||
	        urlToPreload == linkElement.href ||
	        preloadedUrls.has(linkElement.href) ||
	        (!allowExternalLinks && linkElement.origin != location.origin) ||
	        !['http:', 'https:'].includes(linkElement.protocol) ||
	        linkElement.protocol != location.protocol ||
	        (!allowQueryString && linkElement.search && !('instant' in linkElement.dataset))
	    ) {
	        return false;
	    }

	    if (filters.length > 0) {

	        // match
	        const match = filters.some(function (value) {

	            return linkElement.pathname.indexOf(value) != -1;
	        });

	        if ((filterMode == 1 && !match) || filterMode == 2 && match) {

	            return false;
	        }
	    }

	    return true;
	}

	// preload only once
	function preload(url) {

	    if (!preloadedUrls.has(url)) {

	        prefetcher.href = url;
	    }

	    preloadedUrls.add(url);
	}

	function stopPreloading() {
	    prefetcher.removeAttribute('href');
	}

	exports.isPreloadable = isPreloadable;
	exports.preload = preload;
	exports.stopPreloading = stopPreloading;

	return exports;

}({}));
