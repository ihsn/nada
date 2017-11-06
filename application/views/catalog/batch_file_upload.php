<!-- Load plupload and all it's runtimes and finally the jQuery queue widget -->
<script type="text/javascript" src="javascript/plupload/js/plupload.full.min.js"></script>
<script type="text/javascript" src="javascript/plupload/js/jquery.plupload.queue/jquery.plupload.queue.min.js"></script>

<script type="text/javascript">
// Convert divs to queue widgets when the DOM is ready
$(function() {

	function log()
	{
	}

	$("#uploader").pluploadQueue({
		// General settings
		runtimes : 'html5,flash,silverlight,html4',
		url : '<?php echo site_url($upload_url); ?>',
		max_file_size : '300mb',
		chunk_size : '2mb',
		unique_names : false,
		multiple_queues:true,
		multipart_params: { 'upload_folder': 'default', 'overwrite':0},

		// Specify what files to browse for
		filters : [
			{title : "External Resources", extensions : "<?php echo $allowed_extensions;?>"}
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
				//log('[ChunkUploaded] File:', file, "Info:", info);
			},

			UploadComplete: function (up, file) {
				//called when all files are uploaded
				window.location='<?php echo site_url($destination_url);?>';
			},

			Error: function(up, args) {
				// Called when a error has occured
				log('[error] ', args);
			}
		}

	});

});
</script>

<form method="post" enctype="multipart/form-data" >
	<div id="uploader">
		<p>You browser doesn't have Flash, Silverlight, Gears, BrowserPlus or HTML5 support.</p>
	</div>
</form>
