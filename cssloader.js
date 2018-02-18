// @ts-check
function _l(link, undef) {
	if (link.dataset.media != undef) {
		link.media = link.dataset.media;
		link.removeAttribute("data-media");
	} else {
		link.removeAttribute("media");
	}
}
