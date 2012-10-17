<style type="text/css">
/* 
   Plupload
------------------------------------------------------------------- */

.plupload_button {
	display: -moz-inline-box; /* FF < 3*/
	display: inline-block;
	font: normal 12px sans-serif;
	text-decoration: none;
    color: #42454a;
    border: 1px solid #bababa;
    padding: 2px 8px 3px 20px;
	margin-right: 4px;
    background: #f3f3f3;
	outline: 0;

    /* Optional rounded corners for browsers that support it */
    -moz-border-radius: 3px;
    -khtml-border-radius: 3px;
    -webkit-border-radius: 3px;
    border-radius: 3px;
}

.plupload_button:hover {
	color: #000;
	text-decoration: none;
}

.plupload_disabled, a.plupload_disabled:hover {
    color: #737373;
    border-color: #c5c5c5;
    background: #ededed;
	cursor: default;
}

.plupload_add {
	background-position: -181px center;
}

.plupload_wrapper {
	font: normal 11px Verdana,sans-serif;
	width: 100%;
}

.plupload_container {
	padding: 0px;
	/*background: url('../img/transp50.png');*/
	/*-moz-border-radius: 5px;*/
}

.plupload_header {
	background: #2A2C2E;
	display:none;
}

.plupload_header_content {
	min-height: 56px;
	padding-left: 60px;
	color: #FFF;
}
.plupload_header_title {
	font: normal 18px sans-serif;
	padding: 6px 0 3px;
	display:none;
}
.plupload_header_text {
	font: normal 12px sans-serif;
	display:none;
}

.plupload_filelist {
	margin: 0;
	padding: 0;
	list-style: none;
}

.plupload_scroll .plupload_filelist {
	height: 185px;
	background: #F5F5F5;
	overflow-y: scroll;
}

.plupload_filelist li {
	padding: 5px;
	background: #F5F5F5;
	border-bottom: 1px solid #DDD;
	list-style-type: none;
}

.plupload_filelist_header, .plupload_filelist_footer {
	background: #DFDFDF;
	padding: 8px 8px;
	color: #42454A;
}
.plupload_filelist_header {	
	border-top: 1px solid #EEE;
	border-bottom: 1px solid #CDCDCD;
}

.plupload_filelist_footer {border-top: 1px solid #FFF; height: 22px; line-height: 20px; vertical-align: middle;}
.plupload_file_name {float: left; overflow: hidden}
.plupload_file_status {color: #777;}
.plupload_file_status span {color: #42454A;}
.plupload_file_size, .plupload_file_status, .plupload_progress {
	float: right;
	width: 80px;
}
.plupload_file_size, .plupload_file_status, .plupload_file_action {text-align: right;}

.plupload_filelist .plupload_file_name {width: 205px}

.plupload_file_action {
	float: right;
	width: 16px;
	height: 16px;
	margin-left: 15px;
}

.plupload_file_action * {
	display: none;
	width: 16px;
	height: 16px;
}

li.plupload_uploading {background: #ECF3DC;}
li.plupload_done {color:#AAA}

li.plupload_delete a {
	background: url('images/delete.gif');
}

li.plupload_failed a {
	background: url('images/error.gif');
	cursor: default;
}

li.plupload_done a {
	background: url('images/done.gif');
	cursor: default;
}

.plupload_progress, .plupload_upload_status {
	display: none;
}

.plupload_progress_container {
	margin-top: 3px;
	border: 1px solid #CCC;
	background: #FFF;
	padding: 1px;
}
.plupload_progress_bar {
	width: 0px;
	height: 7px;
	background: #CDEB8B;
}

.plupload_scroll .plupload_filelist_header .plupload_file_action, .plupload_scroll .plupload_filelist_footer .plupload_file_action {
	margin-right: 17px;
}

/* Floats */

.plupload_clear,.plupload_clearer {clear: both;}
.plupload_clearer{
	display: block;
	font-size: 0;
	line-height: 0px;	
	height:1px;
}

li.plupload_droptext {
	background: transparent;
	text-align: center;
	vertical-align: middle;
	border: 0;
	line-height: 165px;
}
</style>

<script type="text/javascript" src="javascript/plupload/gears_init.js"></script>
<script type="text/javascript" src="http://bp.yahooapis.com/2.4.21/browserplus-min.js"></script>

<!-- Load plupload and all it's runtimes and finally the jQuery queue widget -->
<script type="text/javascript" src="javascript/plupload/plupload.full.min.js"></script>
<script type="text/javascript" src="javascript/plupload/jquery.plupload.queue.min.js"></script>

<script type="text/javascript">
// Convert divs to queue widgets when the DOM is ready
$(function() {

	function log()
	{
	}
	
	$("#uploader").pluploadQueue({
		// General settings
		runtimes : 'gears,flash,silverlight,browserplus,html5',
		url : '<?php echo site_url().'/admin/managefiles/'.$this->uri->segment(3).'/process_batch_uploads'; ?>',
		max_file_size : '300mb',
		chunk_size : '2mb',
		unique_names : false,
		multiple_queues:true,
		multipart_params: { 'upload_folder': 'default', 'overwrite':0},

		// Resize images on clientside if we can
		resize : {width : 800, height : 800, quality : 100},

		// Specify what files to browse for
		filters : [
			{title : "Resources", extensions : "jpg,gif,png,zip,doc,docx,pdf,sav,dta,do,smcl,log,ado,txt,rar,xls,xlsx,rtf,csv"},
			{title : "Others", extensions : "*.*"}
		],

		// Flash settings
		flash_swf_url : 'javascript/plupload/plupload.flash.swf',

		// Silverlight settings
		silverlight_xap_url : 'javascript/plupload/plupload.silverlight.xap',
		
		// Post init events, bound after the internal events
		init : {
			Refresh: function(up) {
				// Called when upload shim is moved
				log('[Refresh]');
			},
			
			BeforeUpload: function(up,file) {
							
				 up.settings.multipart_params.upload_folder = $("#upload_folder").val();
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
			},
			
			Error: function(up, args) {
				// Called when a error has occured
				log('[error] ', args);
			}
		}

	});

});
</script>

