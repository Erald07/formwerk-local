window.addEventListener("resize", function (event) {
	post_container_height_to_parent();
}, true);

function post_container_height_to_parent() {
	let container = document.querySelector('.leform-container');

	window.parent.postMessage(
		{
			'container': {
				'height': container.offsetHeight + 32 //height of admin bar
			}
		},
		'https://www.hwk-do.de'
	);
}

setInterval(function() {
	post_container_height_to_parent();
}, 1000);