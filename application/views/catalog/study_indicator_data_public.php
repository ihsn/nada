<?php
/**
 * Public catalog study tab: indicator chart / data API / structure (read-only timeseries API + Vue).
 */
defined('BASEPATH') OR exit('No direct script access allowed');

$this->load->helper('vite_helper');

$_sid  = isset($survey_id) ? (int) $survey_id : 0;
$_idno = isset($idno) ? (string) $idno : '';
$_imv  = isset($indicator_main_view) ? (string) $indicator_main_view : 'chart';
$_page_title = isset($catalog_page_title)
	? $catalog_page_title
	: (function_exists('t') ? t('tab_indicator_chart') : 'Chart & data');

$_study_title = isset($study_title) ? (string) $study_title : '';
$_study_abstract = isset($study_abstract) ? (string) $study_abstract : '';
$_indicator_ui = isset($indicator_data_api_ui) && is_array($indicator_data_api_ui) ? $indicator_data_api_ui : array();
$_embed_mode = !empty($embed_mode);

$vite_dev_url = defined('VITE_DEV_URL') ? VITE_DEV_URL : 'http://localhost:5173';
$use_vite_dev = defined('VUE_ENVIRONMENT') && VUE_ENVIRONMENT === 'development';
?>
<div id="catalog-study-indicator-data-app" class="catalog-study-indicator-data-vue<?php echo $_embed_mode ? ' catalog-study-indicator-data-vue--embed' : ''; ?>"></div>
<script>
window.APP_CONFIG = Object.assign({}, window.APP_CONFIG || {}, {
	siteUrl: "<?php echo addslashes(site_url()); ?>",
	baseUrl: "<?php echo addslashes(base_url()); ?>",
	apiBaseUrl: "<?php echo addslashes(site_url('api/timeseries/')); ?>",
	assetsBase: "<?php echo addslashes(base_url('frontend/dist/')); ?>",
	studySid: <?php echo json_encode($_sid); ?>,
	studyIdno: <?php echo json_encode($_idno); ?>,
	pageTitle: <?php echo json_encode($_page_title); ?>,
	indicatorMainView: <?php echo json_encode($_imv); ?>,
	studyTitle: <?php echo json_encode($_study_title, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP); ?>,
	studyAbstract: <?php echo json_encode($_study_abstract, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP); ?>,
	indicatorDataApiUi: <?php echo json_encode($_indicator_ui, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP); ?>,
	indicatorEmbed: <?php echo json_encode($_embed_mode); ?>
});
</script>
<?php if ($use_vite_dev): ?>
<?php echo render_vite_dev_scripts('catalog/study_indicator_data_public/main.js', $vite_dev_url); ?>
<?php else: ?>
<?php echo render_vite_entry_assets('catalog_study_indicator_data', 'frontend/dist'); ?>
<?php endif; ?>
