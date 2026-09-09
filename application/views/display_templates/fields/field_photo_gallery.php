<?php
/**
 * Image resources from distribution transferOptions.onLine (jpg/jpeg/gif/png).
 */
$field_key = isset($template['key']) ? (string) $template['key'] : (isset($name) ? (string) $name : 'graphicOverview');
$field_title = isset($template['title']) ? (string) $template['title'] : t('graphicOverview');
$image_ext = array('jpg', 'jpeg', 'gif', 'png');

if (!isset($data) || !is_array($data) || count($data) < 1) {
	return;
}

$slides = array();
foreach ($data as $resource) {
	if (!is_array($resource)) {
		continue;
	}
	$filename = isset($resource['filename']) ? (string) $resource['filename'] : '';
	$download = isset($resource['download_link']) && $resource['download_link'] !== ''
		? (string) $resource['download_link']
		: '';
	if ($download === '' && isset($resource['_links']['download']) && $resource['_links']['download'] !== '') {
		$download = (string) $resource['_links']['download'];
	}
	if ($download === '') {
		$download = $filename;
	}
	$path = parse_url($filename, PHP_URL_PATH);
	$ext = isset($resource['extension']) && $resource['extension'] !== ''
		? strtolower((string) $resource['extension'])
		: strtolower((string) pathinfo($path ? $path : $filename, PATHINFO_EXTENSION));
	if (!in_array($ext, $image_ext, true)) {
		continue;
	}
	$slides[] = array(
		'title' => isset($resource['title']) && $resource['title'] !== '' ? (string) $resource['title'] : $filename,
		'download_link' => $download,
	);
}

if (count($slides) < 1) {
	return;
}
?>
<style>
.carousel-container { border: 1px solid gainsboro; padding: 5px; }
.carousel-inner { max-height: 300px; overflow: hidden !important; height: 300px; }
.carousel-img { max-height: 300px; width: auto; margin: auto; }
.carousel-indicators { bottom: -5px; }
.icon-wrap { background: #545b62; padding: 10px; padding-bottom: 5px; border: 1px solid white; }
.gallery-indicators li { height: 11px; border: 1px solid #ced4da; background-color: #6c757d; }
.carousel-indicators .active { background-color: #f8f9fa; }
</style>
<div class="field resource-photo field-<?php echo str_replace('.', '__', html_escape($field_key)); ?>">
	<div class="field-title"><?php echo display_template_resolve_title($field_key, $field_title); ?></div>
	<div class="field-value">
		<div id="photoGallery" class="carousel-container carousel slide" data-ride="carousel" data-interval="false">
			<ol class="carousel-indicators gallery-indicators">
				<?php foreach ($slides as $index => $resource): ?>
				<li data-target="#photoGallery" data-slide-to="<?php echo (int) $index; ?>" class="<?php echo $index === 0 ? 'active' : ''; ?>"></li>
				<?php endforeach; ?>
			</ol>
			<div class="carousel-inner">
				<?php foreach ($slides as $index => $resource): ?>
				<div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
					<img src="<?php echo html_escape($resource['download_link']); ?>" title="<?php echo html_escape($resource['title']); ?>" class="carousel-img mx-auto d-block" alt="<?php echo html_escape($resource['title']); ?>">
				</div>
				<?php endforeach; ?>
			</div>
			<a class="carousel-control-prev" href="#photoGallery" role="button" data-slide="prev">
				<span class="icon-wrap"><span class="carousel-control-prev-icon carousel-control" aria-hidden="true"></span></span>
				<span class="sr-only">Previous</span>
			</a>
			<a class="carousel-control-next" href="#photoGallery" role="button" data-slide="next">
				<span class="icon-wrap"><span class="carousel-control-next-icon" aria-hidden="true"></span></span>
				<span class="sr-only">Next</span>
			</a>
		</div>
		<div><?php echo t('Download'); ?></div>
		<ul>
			<?php foreach ($slides as $resource): ?>
			<li>
				<a href="<?php echo html_escape($resource['download_link']); ?>">
					<i class="fa fa-download" aria-hidden="true"></i> <?php echo html_escape($resource['title']); ?>
				</a>
			</li>
			<?php endforeach; ?>
		</ul>
	</div>
</div>
