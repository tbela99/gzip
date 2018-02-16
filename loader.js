// @ts-check
function il(position) {
	const scripts = document[position].querySelectorAll(
		'script[type="text/foo"]'
	);
	const j = scripts.length;

	let i = 0;

	for (; i < j; i++) {
		setTimeout(
			(function(oldScript, script) {
				return function() {
					const parent = oldScript.parentElement;
					script.text = oldScript.text;

					parent.insertBefore(script, oldScript);
					parent.removeChild(oldScript);
				};
			}(scripts[i], document.createElement("script"))),
			0
		);
	}
}
