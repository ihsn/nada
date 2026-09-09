<?php
$sid=$this->uri->segment(4);
$selected_page=$this->uri->segment(5);

$study_type=isset($type) ? $type : 'survey';
?>

<div class="container-fluid study-edit-page">


<?php
$this->load->helper('vite_helper');
$this->lang->load('breadcrumbs');
$this->lang->load('catalog_admin');
$_study_edit_bc = array(
	'homeUrl' => site_url('admin'),
	'catalogUrl' => site_url('admin/catalog'),
	'editUrl' => site_url('admin/catalog/edit/'.$sid),
	'homeLabel' => t('Home'),
	'catalogLabel' => t('catalog_crumb'),
	'editLabel' => t('edit'),
);
$vite_dev_url = defined('VITE_DEV_URL') ? VITE_DEV_URL : 'http://localhost:5173';
$use_vite_dev = defined('VUE_ENVIRONMENT') && VUE_ENVIRONMENT === 'development';
?>
<div id="catalog-study-edit-breadcrumbs-app" class="catalog-study-edit-breadcrumbs-mount" data-config="<?php echo htmlspecialchars(json_encode($_study_edit_bc, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>"></div>
<?php if ($use_vite_dev): ?>
<?php echo render_vite_dev_scripts('admin/catalog_study_edit_breadcrumbs/main.js', $vite_dev_url); ?>
<?php else: ?>
<?php echo render_vite_entry_assets('admin_catalog_study_edit_breadcrumbs', 'frontend/dist'); ?>
<?php endif; ?>

<?php
$error = $this->session->flashdata('error');
$message = $this->session->flashdata('message');
?>
<?php if ($error != ''): ?>
	<div class="study-edit-flash study-edit-flash--error"><?php echo $error; ?></div>
<?php endif; ?>

<?php if ($message != ''): ?>
	<div class="study-edit-flash">
		<button type="button" class="study-edit-flash-close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
		<?php echo $message; ?>
	</div>
<?php endif; ?>

<?php
	//current page url
	$page_url=site_url().'/'.$this->uri->uri_string();
?>

<div class="row">
<div class="col-md-12 edit-page-header">
		
	<div class="col-md-11">
		<h1 class="study-title"><?php echo html_escape($title); ?></h1>
		<?php
		$study_published = ! empty($published);
		$study_public_url = site_url('catalog/' . (int) $sid);
		?>
		<div class="study-edit-title-meta">
			<span
				id="study-edit-publish-status-chip"
				class="study-edit-status-chip<?php echo $study_published ? ' study-edit-status-chip--published' : ' study-edit-status-chip--draft'; ?>"
				role="status"
				data-published="<?php echo $study_published ? '1' : '0'; ?>"
				data-label-published="<?php echo htmlspecialchars(t('published'), ENT_QUOTES, 'UTF-8'); ?>"
				data-label-draft="<?php echo htmlspecialchars(t('draft'), ENT_QUOTES, 'UTF-8'); ?>"
			><?php echo html_escape($study_published ? t('published') : t('draft')); ?></span>
			<span class="study-edit-type-chip" title="<?php echo htmlspecialchars(t('study_type_label'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(isset($study_type_display) ? $study_type_display : $study_type, ENT_QUOTES, 'UTF-8'); ?></span>
			<a
				class="study-edit-public-link study-edit-public-link--with-icon"
				href="<?php echo htmlspecialchars($study_public_url, ENT_QUOTES, 'UTF-8'); ?>"
				target="_blank"
				rel="noopener noreferrer"
			><span class="study-edit-public-link__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg></span><span><?php echo html_escape(t('preview_study')); ?></span></a>
		</div>
	</div>
</div>


	<?php $is_metadata_tab = ($selected_page === 'metadata'); ?>
	<div id="survey" class="<?php echo $is_metadata_tab ? 'col-md-12' : 'col-md-9'; ?>">

		<div>
			<!-- Nav tabs -->
			<ul class="nav nav-tabs" role="tablist">
				<li role="presentation" <?php echo $selected_page=='' ? 'class="active"' : '';?>><a href="<?php echo site_url('admin/catalog/edit/'.$sid);?>" aria-controls="home" role="tab" ><?php echo t('tab_overview');?></a></li>
				<li role="presentation" <?php echo $selected_page=='metadata' ? 'class="active"' : '';?>><a href="<?php echo site_url('admin/catalog/edit/'.$sid.'/metadata');?>" aria-controls="metadata-editor" role="tab" ><?php echo t('Metadata');?> </a></li>
				<li role="presentation" <?php echo $selected_page=='files' ? 'class="active"' : '';?>><a href="<?php echo site_url('admin/catalog/edit/'.$sid.'/files');?>" aria-controls="profile" role="tab" ><?php echo t('tab_manage_files');?> <span class="badge badge-light study-edit-tab-badge" data-study-summary="files" aria-hidden="true"></span></a></li>
				<li role="presentation" <?php echo $selected_page=='resources' ? 'class="active"' : '';?>><a href="<?php echo site_url('admin/catalog/edit/'.$sid.'/resources');?>" aria-controls="resources" role="tab" ><?php echo t('tab_resources');?> <span class="badge badge-light study-edit-tab-badge" data-study-summary="resources" aria-hidden="true"></span></a></li>
				<li role="presentation" <?php echo $selected_page=='citations' ? 'class="active"' : '';?>><a href="<?php echo site_url('admin/catalog/edit/'.$sid.'/citations');?>" aria-controls="settings" role="tab" ><?php echo t('tab_citations');?> <span class="badge badge-light study-edit-tab-badge" data-study-summary="citations" aria-hidden="true"></span></a></li>

				<?php /* ?>
				<li role="presentation" <?php echo $selected_page=='data-files' ? 'class="active"' : '';?>><a href="<?php echo site_url('admin/catalog/edit/'.$sid.'/data-files');?>" aria-controls="data-files" role="tab" ><?php echo t('tab_data_files');?> <span class="badge badge-light"><?php echo $data_files['total'];?></span></a></li>-->
				<?php */?>
				<li role="presentation" <?php echo $selected_page=='notes' ? 'class="active"' : '';?>><a href="<?php echo site_url('admin/catalog/edit/'.$sid.'/notes');?>" aria-controls="settings" role="tab" ><?php echo t('tab_notes');?> <span class="badge badge-light study-edit-tab-badge" data-study-summary="notes" aria-hidden="true"></span></a></li>
				<li role="presentation" <?php echo $selected_page=='related-data' ? 'class="active"' : '';?>><a href="<?php echo site_url('admin/catalog/edit/'.$sid.'/related-data');?>" aria-controls="settings" role="tab" ><?php echo t('tab_related_data');?> <span class="badge badge-light study-edit-tab-badge" data-study-summary="related_studies" aria-hidden="true"></span></a></li>

				<?php if (!empty($analytics_enabled)): ?>
				<li role="presentation" <?php echo $selected_page=='analytics' ? 'class="active"' : '';?>><a href="<?php echo site_url('admin/catalog/edit/'.$sid.'/analytics');?>" aria-controls="analytics" role="tab"><?php echo t('Analytics');?></a></li>
				<?php endif; ?>
				<?php if ($study_type === 'timeseries'): ?>
				<li role="presentation" <?php echo $selected_page=='timeseries-data' ? 'class="active"' : '';?>><a href="<?php echo site_url('admin/catalog/edit/'.$sid.'/timeseries-data');?>" aria-controls="timeseries-data" role="tab"><?php echo t('tab_timeseries_data');?></a></li>
				<?php endif; ?>
				
			</ul>

		</div>


		<input name="tmp_id" type="hidden" id="tmp_id" value="<?php echo get_form_value('tmp_id',isset($tmp_id) ? $tmp_id: $this->uri->segment(4)); ?>"/>

		<div class="study-tab-container<?php echo ($selected_page === 'metadata') ? ' study-tab-container--metadata' : ''; ?>">
		<?php
			//load tab content
			switch($this->uri->segment(5)) {
				case 'resources':
					$this->load->view('catalog/edit_study_resources');
				break;
				case 'data-files':
				echo $data_files['formatted'];
			break;
				case 'citations':
					$this->load->view('catalog/edit_study_citations');
				break;
				case 'related-data':
					$this->load->view('catalog/edit_study_related_data');
				break;
				case 'notes':
					$this->load->view('catalog/edit_study_notes');
				break;
				case 'files':
					$this->load->view('catalog/edit_study_files');
				break;
				case 'metadata':
					$this->load->view('catalog/edit_study_metadata');
				break;
				case 'analytics':
					$this->load->view('catalog/edit_study_analytics');
				break;
				case 'timeseries-data':
					$this->load->view('catalog/study_timeseries_data_tab');
				break;
				default:
					$this->load->view('catalog/edit_study_overview');
			}//end-switch
		?>
		</div>


	</div>
	<!--end survey info block-->

<?php if (! $is_metadata_tab): ?>
<div class="right-sidebar col-md-3">
<?php $this->load->view('catalog/edit_study_sidebar'); ?>
</div>
<!-- end-right-bar -->
<?php endif; ?>

</div>
<!--end-row-->

</div>
<!-- end container -->
<script>
(function () {
  var summaryUrl = <?php echo json_encode(site_url('api/admin/catalog/' . (int) $sid . '/summary?id_format=id')); ?>;
  fetch(summaryUrl, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(function (res) { return res.json(); })
    .then(function (data) {
      if (!data || data.status !== 'success' || !data.summary) return;
      var summary = data.summary;
      document.querySelectorAll('[data-study-summary]').forEach(function (el) {
        var key = el.getAttribute('data-study-summary');
        if (!key || typeof summary[key] !== 'number') {
          el.setAttribute('aria-hidden', 'true');
          el.textContent = '';
          return;
        }
        var n = summary[key];
        if (n <= 0) {
          el.setAttribute('aria-hidden', 'true');
          el.textContent = '';
          return;
        }
        el.textContent = String(n);
        el.removeAttribute('aria-hidden');
      });
    })
    .catch(function () { /* badges optional */ });

  function applyStudyEditPublishChip(detail) {
    var el = document.getElementById('study-edit-publish-status-chip');
    if (!el || !detail || typeof detail.published !== 'boolean') return;
    var pub = detail.published;
    var lp = el.getAttribute('data-label-published') || 'Published';
    var ld = el.getAttribute('data-label-draft') || 'Draft';
    el.textContent = pub ? lp : ld;
    el.setAttribute('data-published', pub ? '1' : '0');
    el.classList.remove('study-edit-status-chip--published', 'study-edit-status-chip--draft');
    el.classList.add(pub ? 'study-edit-status-chip--published' : 'study-edit-status-chip--draft');
  }
  window.addEventListener('catalogStudyPublishedChanged', function (ev) {
    applyStudyEditPublishChip(ev.detail || {});
  });
})();
</script>
