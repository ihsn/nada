<?php
if (!function_exists('dd_summary_h')) {
	function dd_summary_h($value)
	{
		return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
	}
}
$info_rows = isset($info_rows) && is_array($info_rows) ? $info_rows : array();
$metadata_sections = isset($metadata_sections) && is_array($metadata_sections) ? $metadata_sections : array();
$access_sections = isset($access_sections) && is_array($access_sections) ? $access_sections : array();
$files = isset($files) && is_array($files) ? $files : array();
$labels = isset($labels) && is_array($labels) ? $labels : array();
?>
<div class="page-review-submit dd-email-summary">
	<div class="field">
		<h2><?php echo dd_summary_h(isset($labels['step_info']) ? $labels['step_info'] : 'Project information'); ?></h2>
		<table class="tbl-border">
			<?php foreach ($info_rows as $row): ?>
			<tr>
				<td class="td-label"><?php echo dd_summary_h(isset($row['label']) ? $row['label'] : ''); ?></td>
				<td><?php echo nl2br(dd_summary_h(isset($row['value']) ? $row['value'] : '')); ?></td>
			</tr>
			<?php endforeach; ?>
		</table>
	</div>

	<div class="field">
		<h2><?php echo dd_summary_h(isset($labels['step_metadata']) ? $labels['step_metadata'] : 'Study description'); ?></h2>
		<?php if (!$metadata_sections): ?>
			<p><?php echo dd_summary_h(isset($labels['no_preview']) ? $labels['no_preview'] : ''); ?></p>
		<?php else: ?>
			<?php foreach ($metadata_sections as $section): ?>
				<?php if (!empty($section['title'])): ?>
					<h3><?php echo dd_summary_h($section['title']); ?></h3>
				<?php endif; ?>
				<table class="tbl-border">
					<?php foreach ((isset($section['rows']) && is_array($section['rows'])) ? $section['rows'] : array() as $row): ?>
					<tr>
						<td class="td-label"><?php echo dd_summary_h(isset($row['title']) ? $row['title'] : ''); ?></td>
						<td><?php echo nl2br(dd_summary_h(isset($row['value']) ? $row['value'] : '')); ?></td>
					</tr>
					<?php endforeach; ?>
				</table>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>

	<div class="field">
		<h2><?php echo dd_summary_h(isset($labels['step_files']) ? $labels['step_files'] : 'Files'); ?></h2>
		<?php if (!$files): ?>
			<p><?php echo dd_summary_h(isset($labels['no_files']) ? $labels['no_files'] : ''); ?></p>
		<?php else: ?>
			<table class="tbl-border">
				<tr>
					<th><?php echo dd_summary_h(isset($labels['title']) ? $labels['title'] : 'Title'); ?></th>
					<th><?php echo dd_summary_h(isset($labels['resource_type']) ? $labels['resource_type'] : 'Type'); ?></th>
				</tr>
				<?php foreach ($files as $file): ?>
				<tr>
					<td>
						<?php
						$file_title = isset($file['title']) ? trim((string) $file['title']) : '';
						$filename = isset($file['filename']) ? trim((string) $file['filename']) : '';
						echo dd_summary_h($file_title !== '' ? $file_title : $filename);
						if ($file_title !== '' && $filename !== '' && $file_title !== $filename) {
							echo '<br/><span style="color:#666">'.dd_summary_h($filename).'</span>';
						}
						?>
					</td>
					<td><?php echo dd_summary_h(isset($file['dctype_title']) && $file['dctype_title'] !== '' ? $file['dctype_title'] : (isset($file['dctype']) ? $file['dctype'] : '')); ?></td>
				</tr>
				<?php endforeach; ?>
			</table>
		<?php endif; ?>
	</div>

	<div class="field">
		<h2><?php echo dd_summary_h(isset($labels['step_access']) ? $labels['step_access'] : 'Access and notes'); ?></h2>
		<?php if (!$access_sections): ?>
			<p><?php echo dd_summary_h(isset($labels['no_preview']) ? $labels['no_preview'] : ''); ?></p>
		<?php else: ?>
			<?php foreach ($access_sections as $section): ?>
				<?php if (!empty($section['title']) && $section['title'] !== 'Details'): ?>
					<h3><?php echo dd_summary_h($section['title']); ?></h3>
				<?php endif; ?>
				<table class="tbl-border">
					<?php foreach ((isset($section['rows']) && is_array($section['rows'])) ? $section['rows'] : array() as $row): ?>
					<tr>
						<td class="td-label"><?php echo dd_summary_h(isset($row['title']) ? $row['title'] : ''); ?></td>
						<td><?php echo nl2br(dd_summary_h(isset($row['value']) ? $row['value'] : '')); ?></td>
					</tr>
					<?php endforeach; ?>
				</table>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>
