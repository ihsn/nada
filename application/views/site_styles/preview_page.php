<?php
/**
 * Sample typography for site_layout_preview/styles.
 * The admin page sends CSS with postMessage as settings change.
 */
?>
<style>
.ss-preview-sample{max-width:42rem;margin:1.5rem auto;padding:0 1rem}
.ss-preview-sample h1,.ss-preview-sample h2,.ss-preview-sample h3,
.ss-preview-sample h4,.ss-preview-sample h5,.ss-preview-sample h6{margin:1rem 0 .35rem}
.ss-preview-sample p{margin:0 0 .75rem;line-height:1.5}
.ss-preview-muted{opacity:.65;font-size:.85rem}
</style>
<div class="ss-preview-sample">
	<p class="ss-preview-muted"><?php echo htmlspecialchars(tt('ss_preview_hint', 'Sample content — edit CSS on the left to override headings and links.'), ENT_QUOTES, 'UTF-8'); ?></p>
	<h1>Heading 1</h1>
	<h2>Heading 2</h2>
	<h3>Heading 3</h3>
	<h4>Heading 4</h4>
	<h5>Heading 5</h5>
	<p>Body text with a <a href="#">hyperlink</a> and another <a class="link" href="#">styled link</a>.</p>
	<p>Hover the links to check hover color.</p>
</div>
<script>
(function () {
	var origin = window.location.origin;
	var framed = window.parent && window.parent !== window;

	function styleEl() {
		return document.querySelector('style[data-ss-style]');
	}

	function applyCss(css) {
		var el = styleEl();
		if (!el) {
			el = document.createElement('style');
			el.setAttribute('data-ss-style', '');
			document.head.appendChild(el);
		}
		el.textContent = typeof css === 'string' ? css : '';
	}

	function send(msg) {
		if (framed) {
			window.parent.postMessage(msg, origin);
		}
	}

	window.addEventListener('message', function (e) {
		if (e.origin !== origin || e.source !== window.parent || !e.data || e.data.type !== 'ss-styles') {
			return;
		}
		if (typeof e.data.css === 'string') {
			applyCss(e.data.css);
		}
	});

	document.addEventListener('click', function (e) {
		var a = e.target.closest ? e.target.closest('a') : null;
		if (a) {
			e.preventDefault();
		}
	}, true);

	send({ type: 'ss-ready' });
})();
</script>
