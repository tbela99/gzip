// @ts-check

/**
 * lazy image loader
 * @package     GZip Plugin
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */

// load responsive css background images
LIB.ready(function (undef) {

    // dynamic inline background images
    // or Array.from()
    const elements = [].slice.apply(document.querySelectorAll('[data-bg-style]'));

    // responsive css background-image in [style] attribute
    if (elements.length > 0) {

        // or use resizeObserver
        const styles = elements.map(function (el) {
            return JSON.parse(el.dataset.bgStyle);
        });
        let keys = [];

        Object.values(styles).forEach(function (entry) {
            Object.keys(entry).forEach(function (value) {

                value = +value;

                if (keys.indexOf(value) == -1) {

                    keys.push(value);
                }
            })
        });

        keys.sort(function (a, b) {

            return b - a;
        });

        function updateBgStyle() {

            let i = keys.length;
            let path;
            let el;
            let k;
            let j;
            let mql;

            i = elements.length;

            while (i--) {

                el = elements[i];
                k = Object.keys(styles[i]);

                mql = undef;

                for (j = 0; j < k.length; j++) {

                    mql = window.matchMedia('(max-width: ' + k[j] + 'px)');

                    if (mql.matches) {

                        break;
                    }
                }

                if (mql == undef || !mql.matches || j == k.length) {

                    continue;
                }

                path = 'url(' + styles[i][k[j]] + ')';

                if (el.style.backgroundImage == path) {

                    continue;
                }

                let img = new Image;
                let apply = (function (src) {

                    return function () {
                        el.style.backgroundImage = src;
                    }
                })(path);

                img.src = styles[i][k[j]];

                if (img.width > 0 && img.height > 0) {

                    apply();
                } else if ('decode' in img) {

                    img.decode().then(apply)
                } else img.onload = apply;
            }
        }

        window.addEventListener('resize', updateBgStyle, false);
        setTimeout(updateBgStyle, 10);
    }
});