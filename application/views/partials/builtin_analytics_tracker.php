<?php defined('BASEPATH') OR exit('No direct script access allowed');

$this->load->config('analytics');
$analytics_enabled = $this->config->item('analytics_enabled');
$analytics_source = $this->config->item('analytics_tracking_source');

if (!$analytics_enabled || $analytics_source !== 'builtin') {
	return;
}

$tracker_path = FCPATH . 'javascript/analytics-tracker.js';
$tracker_version = file_exists($tracker_path) ? filemtime($tracker_path) : '';

$pageview_dedupe_window = $this->config->item('analytics_pageview_dedupe_window_minutes');
if ($pageview_dedupe_window === null || $pageview_dedupe_window === '') {
	$pageview_dedupe_window = $this->config->item('analytics_dedupe_window_minutes');
}
$pageview_dedupe_window = (int) ($pageview_dedupe_window ?: 5);
?>
<script src="<?php echo base_url(); ?>javascript/analytics-tracker.js?v=<?php echo $tracker_version; ?>"></script>
<script>
if (typeof NADA !== 'undefined' && NADA.Analytics) {
	NADA.Analytics.init({
		baseUrl: '<?php echo site_url(); ?>',
		enabled: <?php echo $analytics_enabled ? 'true' : 'false'; ?>,
		builtinEnabled: true,
		gaEnabled: false,
		debug: <?php echo $this->config->item('analytics_debug_js') ? 'true' : 'false'; ?>,
		sessionTimeoutMinutes: <?php echo (int) ($this->config->item('analytics_session_timeout_minutes') ?: 30); ?>,
		dedupeWindowMinutes: <?php echo $pageview_dedupe_window; ?>,
		csrfToken: '<?php echo $this->security->get_csrf_hash(); ?>',
		csrfTokenName: '<?php echo $this->security->get_csrf_token_name(); ?>'
	});
}
</script>
