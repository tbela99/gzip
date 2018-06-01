// @ts-check

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
LIB.ready(function() {
	const buttons = document.querySelectorAll("[data-button-group=gzip]");
	let i = buttons.length;

	while (i && i--) {
		buttons[i].addEventListener("click", function() {
			const formData = new FormData(this.form);

			if (this.dataset.task != null) {
				formData.append("task", this.dataset.task);
			}

			fetch(location.pathname, {
				method: "POST",
				body: formData,
				credentials: "include"
			}).then(function(response) {
				return response.json().then(function(data) {
					if (data) {
						if (data.success) {
							alert("Message sent to " + data.data.recipients);
						} else {
							if (data.errors) {
								alert(data.errors.join("\n"));
							}
						}
					}
				});
			});
		});
	}
});
