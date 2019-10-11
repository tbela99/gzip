/*! instant.page v2.0.0 - (C) 2019 Alexandre Dieulot - https://instant.page/license */

import {
    ready
} from '../utils/ready.js';

let urlToPreload
const prefetcher = document.createElement('link');

const allowQueryString = 'instantAllowQueryString' in document.body.dataset
const allowExternalLinks = 'instantAllowExternalLinks' in document.body.dataset
const filterMode = document.body.dataset.instantFilterType;
const filters = JSON.parse(document.body.dataset.instantFilters);

ready(function () {

    let mouseoverTimer
    let lastTouchTimestamp

    const isSupported = prefetcher.relList && prefetcher.relList.supports && prefetcher.relList.supports('prefetch')
    const isDataSaverEnabled = navigator.connection && navigator.connection.saveData

    let delayOnHover = +document.body.dataset.instantIntensity || 65
    let useMousedown = document.body.dataset.instantTrigger.indexOf('mousedown') == 0;
    let useMousedownOnly = document.body.dataset.instantTrigger == 'mousedown-only';

    if (isSupported && !isDataSaverEnabled) {
        prefetcher.rel = 'prefetch'
        document.head.appendChild(prefetcher)

        const eventListenersOptions = {
            capture: true,
            passive: true,
        }

        if (!useMousedownOnly) {
            document.addEventListener('touchstart', touchstartListener, eventListenersOptions)
        }

        if (!useMousedown) {
            document.addEventListener('mouseover', mouseoverListener, eventListenersOptions)
        } else {
            document.addEventListener('mousedown', mousedownListener, eventListenersOptions)
        }
    }

    function touchstartListener(event) {
        /* Chrome on Android calls mouseover before touchcancel so `lastTouchTimestamp`
         * must be assigned on touchstart to be measured on mouseover. */
        lastTouchTimestamp = performance.now()

        const linkElement = event.target.closest('a')

        if (!isPreloadable(linkElement)) {
            return
        }

        linkElement.addEventListener('touchcancel', touchendAndTouchcancelListener, {
            passive: true
        })
        linkElement.addEventListener('touchend', touchendAndTouchcancelListener, {
            passive: true
        })

        urlToPreload = linkElement.href
        preload(linkElement.href)
    }

    function touchendAndTouchcancelListener() {
        urlToPreload = undefined
        stopPreloading()
    }

    function mouseoverListener(event) {
        if (performance.now() - lastTouchTimestamp < 1100) {
            return
        }

        const linkElement = event.target.closest('a')

        if (!isPreloadable(linkElement)) {
            return
        }

        linkElement.addEventListener('mouseout', mouseoutListener, {
            passive: true
        })

        urlToPreload = linkElement.href

        mouseoverTimer = setTimeout(() => {
            preload(linkElement.href)
            mouseoverTimer = undefined
        }, delayOnHover)
    }

    function mousedownListener(event) {
        const linkElement = event.target.closest('a')

        if (!isPreloadable(linkElement)) {
            return
        }

        linkElement.addEventListener('mouseout', mouseoutListener, {
            passive: true
        })

        urlToPreload = linkElement.href

        preload(linkElement.href)
    }

    function mouseoutListener(event) {
        if (event.relatedTarget && event.target.closest('a') == event.relatedTarget.closest('a')) {
            return
        }

        if (mouseoverTimer) {
            clearTimeout(mouseoverTimer)
            mouseoverTimer = undefined
        }

        urlToPreload = undefined

        stopPreloading()
    }

});

export function isPreloadable(linkElement) {
    if (!linkElement || !linkElement.href) {
        return false;
    }

    if (urlToPreload == linkElement.href) {
        return false;
    }

    if (!allowExternalLinks && linkElement.origin != location.origin) {
        return false;
    }

    if (!['http:', 'https:'].includes(linkElement.protocol)) {
        return false;
    }

    if (linkElement.protocol == 'http:' && location.protocol == 'https:') {
        return false;
    }

    if (!allowQueryString && linkElement.search && !('instant' in linkElement.dataset)) {
        return false;
    }

    if (linkElement.hash && linkElement.pathname + linkElement.search == location.pathname + location.search) {
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

export function preload(url) {
    prefetcher.href = url;
}

export function stopPreloading() {
    prefetcher.removeAttribute('href');
}