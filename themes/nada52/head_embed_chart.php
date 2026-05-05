<?php
/**
 * Minimal head for iframe chart embed: no site Bootstrap/jQuery/theme CSS.
 * The Vue entry (render_vite_* in catalog/study_indicator_data_public.php) loads JS/CSS for the chart app.
 */
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo isset($title) ? htmlspecialchars($title, ENT_QUOTES, 'UTF-8') : ''; ?></title>
<?php if (isset($_meta)) {
	echo $_meta;
} ?>
<style>
	/* Full viewport for iframe (100vh matches iframe inner height when iframe has no explicit height chain) */
	html.embed-chart-html {
		box-sizing: border-box;
		width: 100%;
		height: 100%;
		min-height: 100vh;
		margin: 0;
		padding: 0;
		background: #fff;
	}
	html.embed-chart-html body.embed-catalog-chart {
		margin: 0;
		padding: 0;
		width: 100%;
		min-height: 100vh;
		min-height: 100dvh;
		height: 100%;
		display: flex;
		flex-direction: column;
		box-sizing: border-box;
		background: #fff;
	}
	body.embed-catalog-chart .embed-chart-body {
		flex: 1 1 0%;
		min-height: 0;
		width: 100%;
		max-width: 100%;
		display: flex;
		flex-direction: column;
		box-sizing: border-box;
	}
	#catalog-study-indicator-data-app.catalog-study-indicator-data-vue--embed {
		flex: 1 1 0%;
		min-height: 0;
		width: 100%;
		max-width: 100%;
		display: flex;
		flex-direction: column;
		box-sizing: border-box;
	}
	#catalog-study-indicator-data-app.catalog-study-indicator-data-vue--embed .v-application,
	#catalog-study-indicator-data-app.catalog-study-indicator-data-vue--embed .v-application__wrap {
		flex: 1 1 auto;
		min-height: 0 !important;
		display: flex;
		flex-direction: column;
	}
	#catalog-study-indicator-data-app.catalog-study-indicator-data-vue--embed .v-main {
		flex: 1 1 0%;
		min-height: 0 !important;
		display: flex;
		flex-direction: column;
		padding: 0 !important;
		width: 100%;
		max-width: 100%;
	}
	#catalog-study-indicator-data-app.catalog-study-indicator-data-vue--embed .v-container {
		flex: 1 1 0%;
		min-height: 0;
		display: flex;
		flex-direction: column;
		width: 100%;
		max-width: 100% !important;
		padding-left: 0 !important;
		padding-right: 0 !important;
		margin: 0 !important;
	}
	/* Chart tab root fills space below optional snack / alert */
	#catalog-study-indicator-data-app.catalog-study-indicator-data-vue--embed .catalog-indicator-container--embed > *:last-child {
		flex: 1 1 0%;
		min-height: 0;
		display: flex;
		flex-direction: column;
		width: 100%;
		max-width: 100%;
	}
</style>
<?php if (isset($_styles)) {
	echo $_styles;
} ?>
<?php if (isset($_scripts)) {
	echo $_scripts;
} ?>
