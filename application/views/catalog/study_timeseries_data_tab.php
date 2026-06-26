<?php
/**
 * Study edit tab: timeseries indicator data (MongoDB) — Vue 3 + Vite.
 *
 * Variables from catalog edit context: $survey_id, $idno, $type, etc.
 */
defined('BASEPATH') OR exit('No direct script access allowed');

$this->load->helper('vite_helper');

$_sid = isset($survey_id) ? (int) $survey_id : 0;
$_idno = isset($idno) ? (string) $idno : '';

$vite_dev_url = defined('VITE_DEV_URL') ? VITE_DEV_URL : 'http://localhost:5173';
$use_vite_dev = defined('VUE_ENVIRONMENT') && VUE_ENVIRONMENT === 'development';
?>
<div id="admin-study-timeseries-data-app" class="study-timeseries-data-vue"></div>
<script>
window.APP_CONFIG = Object.assign({}, window.APP_CONFIG || {}, {
	siteUrl: "<?php echo addslashes(site_url()); ?>",
	baseUrl: "<?php echo addslashes(base_url()); ?>",
	apiBaseUrl: "<?php echo addslashes(site_url('api/admin/timeseries/')); ?>",
	datasetsApiBaseUrl: "<?php echo addslashes(site_url('api/datasets/')); ?>",
	dataStructuresApiBaseUrl: "<?php echo addslashes(site_url('api/admin/data_structures/')); ?>",
	assetsBase: "<?php echo addslashes(base_url('frontend/dist/')); ?>",
	csrfToken: "<?php echo addslashes($this->security->get_csrf_hash()); ?>",
	studySid: <?php echo json_encode($_sid); ?>,
	studyIdno: <?php echo json_encode($_idno); ?>,
	dataStructuresAdminUrl: "<?php echo addslashes(site_url('admin/data_structures')); ?>"
});
</script>
<?php if ($use_vite_dev): ?>
<?php echo render_vite_dev_scripts('admin/study_timeseries_data/main.js', $vite_dev_url); ?>
<?php else: ?>
<?php echo render_vite_entry_assets('admin_study_timeseries_data', 'frontend/dist'); ?>
<?php endif; ?>
