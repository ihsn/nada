<?php
/**
 * Public catalog study tab: Data API for a data table (/api/tables) — Vue 3 app
 * frontend/catalog/study_data_api_public. Replaces views/data_api/preview.php.
 */
defined('BASEPATH') OR exit('No direct script access allowed');

$this->load->helper('vite_helper');

$_ui = isset($table_data_api_ui) && is_array($table_data_api_ui) ? $table_data_api_ui : array();

$vite_dev_url = defined('VITE_DEV_URL') ? VITE_DEV_URL : 'http://localhost:5173';
$use_vite_dev = defined('VUE_ENVIRONMENT') && VUE_ENVIRONMENT === 'development';
$json_flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
?>
<div id="catalog-study-data-api-app" class="catalog-study-data-api-vue"></div>
<script>
window.APP_CONFIG = Object.assign({}, window.APP_CONFIG || {}, {
	siteUrl: <?php echo json_encode(site_url(), $json_flags); ?>,
	baseUrl: <?php echo json_encode(base_url(), $json_flags); ?>,
	apiBaseUrl: <?php echo json_encode(site_url('api/tables'), $json_flags); ?>,
	assetsBase: <?php echo json_encode(base_url('frontend/dist/'), $json_flags); ?>,
	dbId: <?php echo json_encode((string) $db_id, $json_flags); ?>,
	tableId: <?php echo json_encode((string) $table_id, $json_flags); ?>,
	tables: <?php echo json_encode(isset($tables) && is_array($tables) ? $tables : array(array('db_id' => (string) $db_id, 'table_id' => (string) $table_id)), $json_flags); ?>,
	studySid: <?php echo json_encode(isset($sid) ? (int) $sid : 0); ?>,
	studyIdno: <?php echo json_encode(isset($idno) ? (string) $idno : '', $json_flags); ?>,
	studyTitle: <?php echo json_encode(isset($study_title) ? (string) $study_title : '', $json_flags); ?>,
	tableDataApiUi: <?php echo json_encode($_ui, $json_flags); ?>
});
</script>
<?php if ($use_vite_dev): ?>
<?php echo render_vite_dev_scripts('catalog/study_data_api_public/main.js', $vite_dev_url); ?>
<?php else: ?>
<?php echo render_vite_entry_assets('catalog_study_data_api', 'frontend/dist'); ?>
<?php endif; ?>
