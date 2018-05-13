// @ts-check

/**
 * async css loader
 * @package     GZip Plugin
 * @subpackage  System.Gzip *
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */

function _l(link, undef) {
	if (link.dataset.media != undef) {
		link.media = link.dataset.media;
		link.removeAttribute("data-media");
	} else {
		link.removeAttribute("media");
	}
}
