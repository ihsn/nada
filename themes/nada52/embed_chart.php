<?php
/**
 * Minimal layout for /embed/catalog/{sid}/chart — Vue chart app only (assets from Vite in content view).
 */
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

$content_wrap_class = 'container-fluid';
if (isset($body_class)) {
	$content_wrap_class = $body_class;
}
?>
<!DOCTYPE html>
<html class="embed-chart-html" lang="<?php echo $this->config->item('language') ? html_escape($this->config->item('language')) : 'en'; ?>">
<head>
<?php require_once dirname(__FILE__) . '/head_embed_chart.php'; ?>
</head>
<body class="embed-catalog-chart">

<div class="wb-page-body embed-chart-body <?php echo isset($content_wrap_class) ? $content_wrap_class : ''; ?>">
	<?php if (isset($content)) : ?>
		<?php echo $content; ?>
	<?php endif; ?>
</div>

</body>
</html>
