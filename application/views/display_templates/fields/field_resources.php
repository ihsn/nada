<?php
/**
 * Online resources from distribution transferOptions.onLine.
 *
 * display_options.exclude — dctype code(s) to skip (legacy default: pic).
 * Image file extensions are also skipped so they stay in the photo gallery.
 */
$field_key = isset($template['key']) ? (string) $template['key'] : (isset($name) ? (string) $name : 'transferOptions.onLine');
$field_title = isset($template['title']) ? (string) $template['title'] : t('transferOptions.onLine');
$opts = isset($template['display_options']) && is_array($template['display_options'])
	? $template['display_options']
	: (isset($options) && is_array($options) ? $options : array());
$exclude = isset($opts['exclude']) ? (array) $opts['exclude'] : array('pic');
$image_ext = array('jpg', 'jpeg', 'gif', 'png');

if (!isset($data) || !is_array($data) || count($data) < 1) {
	return;
}

$rows = array();
foreach ($data as $resource) {
	if (!is_array($resource)) {
		continue;
	}
	$dctype = isset($resource['dctype']) ? (string) $resource['dctype'] : '';
	$dctypecode = explode('[', $dctype);
	$dctypecode = str_replace(']', '', $dctypecode[count($dctypecode) - 1]);
	if ($dctypecode !== '' && in_array($dctypecode, $exclude, true)) {
		continue;
	}
	$filename = isset($resource['filename']) ? (string) $resource['filename'] : '';
	$path = parse_url($filename, PHP_URL_PATH);
	$ext = isset($resource['extension']) && $resource['extension'] !== ''
		? strtolower((string) $resource['extension'])
		: strtolower((string) pathinfo($path ? $path : $filename, PATHINFO_EXTENSION));
	if (in_array($ext, $image_ext, true)) {
		continue;
	}
	if ($filename === '' && empty($resource['title'])) {
		continue;
	}
	$rows[] = $resource;
}

if (count($rows) < 1) {
	return;
}
?>
<style>
.resource-icon { font-size: 35px; padding: 10px; color: #0071bc; }
</style>
<div class="field resource-downloads field-<?php echo str_replace('.', '__', html_escape($field_key)); ?>">
	<div class="field-title"><?php echo display_template_resolve_title($field_key, $field_title); ?></div>
	<div class="field-value p-2">
	<?php foreach ($rows as $resource): ?>
		<?php
			$href = isset($resource['filename']) ? (string) $resource['filename'] : '';
			$title = isset($resource['title']) && $resource['title'] !== '' ? (string) $resource['title'] : $href;
		?>
		<div class="row mb-3 pb-2 border-bottom">
			<div class="col">
				<a target="_blank" href="<?php echo html_escape($href); ?>" class="font-weight-bold">
					<?php echo html_escape($title); ?>
				</a>
				<?php if (!empty($resource['dcformat'])): ?>
					<span class="badge badge-light"><?php echo html_escape($resource['dcformat']); ?></span>
				<?php endif; ?>
				<?php if (!empty($resource['description'])): ?>
				<div><?php echo nl2br(html_escape($resource['description'])); ?></div>
				<?php endif; ?>
			</div>
			<div class="col-md-2 col-sm-4">
				<button class="float-right btn btn-primary btn-sm">
					<i class="fa fa-download" aria-hidden="true"></i>
					<a target="_blank" href="<?php echo html_escape($href); ?>" class="text-white"><?php echo t('Download'); ?></a>
				</button>
			</div>
		</div>
	<?php endforeach; ?>
	</div>
</div>
