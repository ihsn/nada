<?php
$config = (isset($catalog_overview_app_config) && is_array($catalog_overview_app_config))
	? $catalog_overview_app_config
	: array();
$this->load->helper('vite_helper');
$vite_dev_url = defined('VITE_DEV_URL') ? VITE_DEV_URL : 'http://localhost:5173';
$use_vite_dev = defined('VUE_ENVIRONMENT') && VUE_ENVIRONMENT === 'development';
?>

<div id="catalog-study-overview-app"></div>
<script>
window.APP_CONFIG = <?php echo json_encode($config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
</script>
<?php if ($use_vite_dev): ?>
<?php echo render_vite_dev_scripts('admin/catalog_study_overview/main.js', $vite_dev_url); ?>
<?php else: ?>
<?php echo render_vite_entry_assets('admin_catalog_study_overview', 'frontend/dist'); ?>
<?php endif; ?>
