<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div id="catalog-app-loading" class="catalog-app-loading" role="status" aria-live="polite" aria-busy="true">
	<div class="catalog-app-loading__inner">
		<div class="catalog-app-loading__spinner" aria-hidden="true"></div>
		<p class="catalog-app-loading__text"><?php echo html_escape(t('js_loading')); ?></p>
	</div>
</div>

<style>
/* Shown only when JS is enabled (html.js); removed once Vue mounts. */
html:not(.js) .catalog-app-loading {
	display: none;
}
.catalog-app-loading {
	min-height: 12rem;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 2rem 1rem;
	color: rgba(0, 0, 0, 0.65);
}
.catalog-app-loading__inner {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 0.85rem;
	text-align: center;
}
.catalog-app-loading__spinner {
	width: 2rem;
	height: 2rem;
	border: 3px solid rgba(0, 0, 0, 0.12);
	border-top-color: rgba(0, 0, 0, 0.45);
	border-radius: 50%;
	animation: catalog-app-loading-spin 0.8s linear infinite;
}
.catalog-app-loading__text {
	margin: 0;
	font-size: 0.9375rem;
	line-height: 1.4;
}
@keyframes catalog-app-loading-spin {
	to {
		transform: rotate(360deg);
	}
}
</style>
