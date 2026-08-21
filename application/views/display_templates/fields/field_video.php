<?php
/**
 * Video embed player. Accepts an embed URL string, or an object with embed_url.
 */
$embed = '';
if (is_string($data) || is_numeric($data)) {
	$embed = trim((string) $data);
} elseif (is_array($data)) {
	if (isset($data['embed_url'])) {
		$embed = trim((string) $data['embed_url']);
	}
}
if ($embed === '') {
	return false;
}

$field_key = isset($template['key']) ? (string) $template['key'] : 'video';
$hide_field_title = !empty($template['hide_field_title']);
?>
<div class="field video-field field-<?php echo html_escape(str_replace('.', '_', $field_key)); ?>">
	<?php if (!$hide_field_title && !empty($template['title'])): ?>
		<div class="field-title"><?php echo tt('metadata.' . $field_key, $template['title']); ?></div>
	<?php endif; ?>
	<div class="field-value">
		<div class="embed-responsive embed-responsive-16by9">
			<iframe class="embed-responsive-item" src="<?php echo html_escape($embed); ?>" allowfullscreen title="<?php echo html_escape(isset($template['title']) ? $template['title'] : 'Video'); ?>"></iframe>
		</div>
	</div>
</div>
