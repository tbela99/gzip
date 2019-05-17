// @ts-check

/**
 * lazy image laoder
 * @package     GZip Plugin
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */

document.addEventListener("readystatechange", function () {

    if (document.readyState == "interactive") {

        let i = 0, images = [].slice.call(document.images);
        const j = images.length;

        for (; i < j; i++) {

            images[i].classList.remove("image-placeholder-no-js");
        }
    }
}, false);