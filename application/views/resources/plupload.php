<!-- Load plupload and all it's runtimes and finally the jQuery queue widget -->
<script type="text/javascript" src="javascript/plupload/js/plupload.full.min.js"></script>
<script type="text/javascript" src="javascript/plupload/js/jquery.plupload.queue/jquery.plupload.queue.min.js"></script>

<script type="text/javascript">
// Convert divs to queue widgets when the DOM is ready
$(function() {

	plupload.addI18n({
		'Select files' : '<?php echo t('Select files');?>',
		'Add files to the upload queue and click the start button.' : '<?php echo t('Add files to the upload queue and click the start button.');?>',
		'Filename' : '<?php echo t('Filename');?>',
		'Status' : '<?php echo t('Status');?>',
		'Size' : '<?php echo t('Size');?>',
		'Add files' : '<?php echo t('Add files');?>',
		'Start upload' : '<?php echo t('Start upload');?>',
		'Stop current upload' : '<?php echo t('Stop current upload');?>',
		'Start uploading queue' : '<?php echo t('Start uploading queue');?>',
		'Uploaded %d/%d files': '<?php echo t('Uploaded %d/%d files');?>',
		'N/A' : '<?php echo t('N/A');?>',
		'Drag files here.' : '<?php echo t('Drag files here.');?>',
		'File extension error.': '<?php echo t('File extension error.');?>',
		'File size error.': '<?php echo t('File size error.');?>',
		'Init error.': '<?php echo t('Init error.');?>',
		'HTTP Error.': '<?php echo t('HTTP Error.');?>',
		'Security error.': '<?php echo t('Security error.');?>',
		'Generic error.': '<?php echo t('Generic error.');?>',
		'IO error.': '<?php echo t('IO error.');?>'
	});

	function log()
	{
	}

    <?php
        $max_resource_upload_size=intval($this->config->item("max_resource_upload_size"));
        if ($max_resource_upload_size<1)
        {
            //default file size
            $max_resource_upload_size=500;
        }
    ?>

	$("#uploader").pluploadQueue({
		// General settings
		runtimes : 'html5,flash,silverlight,html4',
		url : '<?php echo site_url().'/admin/resources/pl_uploads/'.$this->uri->segment(4); ?>',
		max_file_size : '<?php echo $max_resource_upload_size;?>mb',
		chunk_size : '2mb',
		unique_names : false,
		multiple_queues:true,
		multipart_params: { 'upload_folder': 'default', 'overwrite':0},

		// Resize images on clientside if we can
		//resize : {width : 800, height : 800, quality : 100},

		// Specify what files to browse for
		filters : [
			{title : "External Resources", extensions : "<?php echo $this->config->item("allowed_resource_types");?>"}
		],

		// Flash settings
		flash_swf_url : 'javascript/plupload/js/Moxie.swf',

		// Silverlight settings
		silverlight_xap_url : 'javascript/plupload/js/Moxie.xap',

		// Post init events, bound after the internal events
		init : {
			Refresh: function(up) {
				// Called when upload shim is moved
				log('[Refresh]');
			},

			BeforeUpload: function(up,file) {
				 //up.settings.multipart_params.upload_folder = $("#upload_folder").val();
				 if ($("#overwrite").is(':checked')) {
				 	up.settings.multipart_params.overwrite = 1;
				 }
			},

			StateChanged: function(up) {
				// Called when the state of the queue is changed
				log('[StateChanged]', up.state == plupload.STARTED ? "STARTED" : "STOPPED");
			},

			QueueChanged: function(up) {
				// Called when the files in queue are changed by adding/removing files
				log('[QueueChanged]');
			},

			UploadProgress: function(up, file) {
				// Called while a file is being uploaded
				log('[UploadProgress]', 'File:', file, "Total:", up.total);
			},

			FilesAdded: function(up, files) {
				// Callced when files are added to queue
				log('[FilesAdded]');

				plupload.each(files, function(file) {
					log('  File:', file);
				});
			},

			FilesRemoved: function(up, files) {
				// Called when files where removed from queue
				log('[FilesRemoved]');

				plupload.each(files, function(file) {
					log('  File:', file);
				});
			},

			FileUploaded: function(up, file, info) {
				// Called when a file has finished uploading
				//console.log('[FileUploaded] File:', file, "Info:", info);
			},

			ChunkUploaded: function(up, file, info) {
				// Called when a file chunk has finished uploading
				log('[ChunkUploaded] File:', file, "Info:", info);
			},

			UploadComplete: function (up, file) {
				//called when all files are uploaded
				window.location='<?php echo site_url('admin/catalog/edit/'.$this->uri->segment(4));?>';
			},

			Error: function(up, args) {
				// Called when a error has occured
				log('[error] ', args);
			}
		}

	});

});
</script>
<div class="container-fluid">
<h3><?php echo t('upload_external_resources');?></h3>
<form method="post" enctype="multipart/form-data" >
	<div id="uploader">
		<p>You browser doesn't have Flash, Silverlight, Gears, BrowserPlus or HTML5 support.</p>

        <div class="field">
            <label for="upload_folder"><?php echo t('select_upload_files');?></label>
            <?php for($i=0;$i<5;$i++):?>
                <input class="input-flex" type="file" name="file[]" /><br/>
            <?php endfor;?>
        </div>

        <div style="margin-top:5px;">
        <input type="submit" name="upload" value="<?php echo t('upload');?>"/>
        </div>
	</div>
</form>
<div> Max upload file size: <?php echo $max_resource_upload_size;?> mb </div>
<div style="margin-top:20px;">
	<a class="btn btn-primary" href="<?php echo site_url('admin/catalog/edit/'.$this->uri->segment(4));?>">
		<span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span> 
		<?php echo t('return_to_study_edit_page');?>
	</a>
</div>
</div>