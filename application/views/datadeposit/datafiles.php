<style type="text/css">
	#label { display: none; }
	#upload_box {
		display:none;
		margin-top: 50px;
	}
	.edit {
		font-size: 11px;
	}
	
	#close-upload-form{
		color:#4071A1;
		font-size:11px;
		position:absolute;
		top:7px;
		right:10px;
		cursor:pointer;
	}
	
	#upload_box{
		position:relative;
		border:1px solid #4071A1;
		margin-bottom:30px;
		-moz-border-radius: 3px;
    -khtml-border-radius: 3px;
    -webkit-border-radius: 3px;
    border-radius: 3px;
	}
	
	#upload_box h3{
		padding:5px;
		padding-left:10px;
		margin:0px;
		color:#4071A1;
		border-bottom:1px solid #4071A1;
	}
	#upload_box .form
	{
		padding:10px;
	}
	
	.ajax-status{
		border:2px solid #00FF00;
		padding:10px;
		margin-top:20px;
		margin-bottom:20px;
		color:green;
		display:none;		
		padding-left:20px;
	}
</style>

<div class="instruction-box"><?php echo t('instructions_datafiles_usage'); ?></div>
<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div style="clear:both" class="error">'.$message.'</div>' : '';?>

<?php if (!file_exists($project_folder)):?>
	<div style="clear:both" class="error"><?php echo 'STORAGE_NOT_AVAILABLE';?></div>
    <?php return;?>
<?php endif;?>

<div class="ajax-status">Please wait while page is being updated!</div>

<?php if (count($resources)==0):?>
<div id="upload_button" style="float:left;font-size:11px;" class="submit-button"> <span><?php echo t('upload_files'); ?></span> </div>
<?php endif;?>

<div id="upload_box">
	<h3><?php echo t('upload_files'); ?></h3>
	<div id="close-upload-form">Close</div>
    <form class="form form-upload-resources" method="post" action="<?php echo site_url('datadeposit/process_normal_uploads/'.$this->uri->segment(3)); ?>" enctype="multipart/form-data" >
    <div id="file-uploads">
	<div class="allowed-file-types-container"><span class="caption">Allowed file types:</span> <span class="allowed-file-types"><?php echo str_replace(",",", ",$allowed); ?></span></div>
    <div id="uploader">
      <p>Your browser doesn't have Flash, Silverlight, Gears, BrowserPlus or HTML5 support.</p>
      <?php
	  	$upload_max_size = ini_get('upload_max_filesize');
	  ?>
      <div class="field">
        <label for="upload_folder">Select files to upload. Max file size: <?php echo $upload_max_size;?></label>
        <div style="clear:both;overflow:auto;">
        <input class="input-flex" type="file" name="file[]" /><br/>
        <input class="input-flex" type="file" name="file[]" /><br/>
        <input class="input-flex" type="file" name="file[]" /><br/>
        <input class="input-flex" type="file" name="file[]" /><br/>
        <input class="input-flex" type="file" name="file[]" /><br/>
        </div>
      </div>
      <div style="margin-top:5px;">
        <input type="submit" name="upload" value="Upload"/>
      </div>
    </div>
  </form>
</div>
</div>

<div id="label">
  <div style="display:none" class="field">
    <select name='dctype'>
      <option value='--'>--</option>
      <option value='Document, Other [doc/oth]'>Document, Other</option>
      <option value='Document, Questionnaire [doc/qst]'>Questionnaire</option>
      <option value='Document, Report [doc/rep]'>Report</option>
      <option value='Document, Technical [doc/tec]'>Technical Document</option>
      <option value='Audio [aud]'>Audio</option>
      <option value='Map [map]'>Map</option>
      <option value='Microdata File [dat/micro]'>Microdata File</option>
      <option value='Photo [pic]'>Photo</option>
      <option value='Program [prg]'>Program</option>
      <option value='Table [tbl]'>Table</option>
      <option value='Video [vid]'>Video</option>
      <option value='Web Site [web]'>Web Site</option>
    </select>
  </div>
</div>



<?php if (!empty($resources)): ?>
<div class="batch-actions">
    <select id="batch_actions">
        <option value="-1">Batch actions</option>
        <option value="delete">Delete</option>
    </select>
    <input type="button" id="batch_actions_apply" name="batch_actions_apply" value="Apply">

	<div id="upload_button" style="float:right;font-size:11px;" class="submit-button"> <span><?php echo t('upload_files'); ?></span> </div>

</div>
<table class="grid-table table-striped"  style="margin-top:5px;width:100%;">  
  <tr valign="top" align="left"  class="header">
    <th style="width:20px"><input type="checkbox" value="-1" id="chk_toggle"></th>
    <th ><?php echo t('name');?></th>
    <th><?php echo t('type');?></th>
    <th><?php echo t('size');?></th>
    <!--<th>Exists</th>-->
    <th ><?php echo t('actions');?></th>
  </tr>
  <?php $prefix = ""; ?>
	<?php 
		$dctypes_list=array(
			'Document, Questionnaire [doc/qst]'	=>'Questionnaire',
			'Document, Report [doc/rep]'		=> 'Report',
			'Document, Technical [doc/tec]'		=>	'Technical Document',
			'Audio [aud]'						=>	'Audio',
			'Map [map]'							=>	'Map',
			'Microdata File [dat/micro]'		=>	'Microdata File',
			'Photo [pic]'						=>	'Photo',
			'Program [prg]'						=>	'Program',
			'Table [tbl]'						=>	'Table',
			'Video [vid]'						=>	'Video',
			'Web Site [web]'					=>	'Web Site'
	);
	
	?>

  <?php foreach( $resources as $resource): ?>
  <tr class="data" valign="top">
    <td ><input type="checkbox" value="<?php echo $resource['id']; ?>" class="chk"></td>
    <td><?php echo anchor('datadeposit/managefiles/'.$resource['id'], $resource['filename']); ?></td>
    <td id="<?php echo $resource['id']; ?>" class="dctype">
        <div class="field" style="display:none;">
        <select name='dctype'>
            <option value='--'>--</option>
            <?php foreach ($dctypes_list as $key=>$value):?>
                <option value='<?php echo $key;?>' <?php echo $resource['dctype']==$key ? 'selected="selected"' : '';?>><?php echo $value;?></option>
            <?php endforeach;?>
        </select>
        </div>

      <span class="resource-dctype"><?php echo (isset($resource['dctype'])) ? preg_replace('#\[.*?\]#', '', $resource['dctype']) : 'N/A';?></span> 
      <a href="javascript:void(0);" class="edit"><?php echo t('edit'); ?></a>

      </td>
    <td><?php echo format_bytes(@filesize(unix_path($project_folder.'/'.$resource['filename']))); ?></td>
    <td>
		<?php echo anchor('datadeposit/managefiles/'.$resource['id'],'<i class="fa fa-pencil-square" aria-hidden="true"></i> ');?> 
		<?php echo anchor('datadeposit/delete_resource/'.$resource['id'], '<i class="fa fa-trash" aria-hidden="true"></i>',array('class'=>'delete','data-id'=>$resource['id']));?> 
	</td>
  </tr>
  <?php endforeach;?>
</table>

<div style="font-size:10pt;float:right;padding:5px;"><?php echo t('total_files_count');?><?php echo count($resources);?></div>
<?php endif; ?>

<script type="text/javascript" >

//checkbox select/deselect
jQuery(document).ready(function(){

    $("#chk_toggle").click(
            function (e) 
            {
                $('.chk').each(function(){ 
                    this.checked = (e.target).checked; 
                }); 
            }
    );

    $(".chk").click(
            function (e) 
            {
               if (this.checked==false){
                $("#chk_toggle").attr('checked', false);
               }               
            }
    );          

    $("#batch_actions_apply").click(
        function (e){
            if( $("#batch_actions").val()=="delete"){
                batch_delete();
            }
        }
    );

   $(".delete").click(
       function (e){
            if (!confirm("Are you sure you want to delete the selected item?")){
		        return false;
    		}
		    delete_resources($(this).attr("data-id"));
			return false;
        }
    );

	

});


//@items = comma separated list of IDs e.g. 1,2,34
function delete_resources(items)
{
	$.ajax({
        timeout:1000*120,
        cache:false,
        dataType: "html",
        data:{ answer: "Yes",'delete':"Submit"},
        type:'POST', 
        url: CI.base_url+'/datadeposit/batch_delete_resource/'+items,
        success: function(data) {
                window.location = "<?php echo site_url('datadeposit/datafiles/'.$this->uri->segment(3)); ?>";
        },
        error: function(XHR, textStatus, thrownError) {
            alert("Error occured " + XHR.status);
        }
    });
}

function batch_delete(){

    if ($('.chk:checked').length==0){
        alert("You have not selected any items");
        return false;
    }

    if (!confirm("Are you sure you want to delete the selected item(s)?"))
    {
        return false;
    }

    selected='';

    $('.chk:checked').each(function(){ 
        if (selected!=''){selected+=',';}
        selected+= this.value; 
     });
	$(".ajax-status").show();
	delete_resources(selected);
}

$(function() {

	$('#upload_button').click(function() {
		$('#upload_box').toggle();
	});
	
	$(document.body).on("click","a.edit", function(){ 
		console.log("a");
		window.x=$(this);
		$(this).closest("td").find(".resource-dctype").hide();
		$(this).closest("td").find('.field').css('display', 'block');
	});
	
	$(document.body).on("click","#close-upload-form", function(){ 
		$("#upload_box").hide();
	});
	
	var types=[];
	var edit='<a href="javascript:void(0);" class="edit"><?php echo t('edit'); ?></a>';
	
	$(document.body).on("change",'select[id!="batch_actions"]', function(){ 
		var _this=$(this);
		types.push(_this.val());
		$.post("<?php echo site_url('datadeposit/ajax_managefiles') , '/'; ?>"+$(this).parent().parent().attr('id'), _this.serialize(), function(data) {
			_this.parents('td').html($('#label').html()+data+edit);
		});
		var found=0;
		$.each(types, function(index, value) {
			if (value.search(/.*?Questionnaire.*?/) != -1) {
				found++;
			}
		});
		if (found >= 1 && types.length > 1) {
			$('#color3').attr('style', 'background: #F9FFE3 url(<?php echo site_url(), '/../images/tick.png';?>) no-repeat 95% 8% !important');
		}
	});

	$('tr.data').each(function() {
		if ($(this).children('.description').html() != 'N/A' && $(this).children('.dctype').html() != 'N/A') {
			$($(this).children('td')[1]).css('color', 'purple');
		}
	});
});
</script>

<!-- Load plupload and all it's runtimes and finally the jQuery queue widget -->
<script type="text/javascript" src="<?php echo base_url();?>javascript/plupload/js/plupload.full.min.js"></script>
<script type="text/javascript" src="<?php echo base_url();?>javascript/plupload/js/jquery.plupload.queue/jquery.plupload.queue.min.js"></script>

<script>

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

	function log(d)
	{
		if (console) {
			console.log(d);
		}
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
		url : '<?php echo site_url('datadeposit/process_batch_uploads/'.$this->uri->segment(3)); ?>',
		max_file_size : '<?php echo $max_resource_upload_size;?>mb',
		chunk_size : '2mb',
		unique_names : false,
		multiple_queues:true,
		multipart_params: { 'upload_folder': 'default', 'overwrite':0},

		// Resize images on clientside if we can
		//resize : {width : 800, height : 800, quality : 100},

		// Specify what files to browse for
		filters : [
			{title : "Resources", extensions : "<?php echo $this->config->item("allowed_resource_types");?>"}
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
				 file.name = file.name.toLowerCase();
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
				 var response=jQuery.parseJSON(info.response);
				 if (response.status=='failed')
				 {
				 	up.stop();
					alert("Upload failed: " + response.message);
					window.location = "<?php echo site_url('datadeposit/datafiles/'.$this->uri->segment(3)); ?>";
				 }
			},			

			UploadComplete: function (up, file) {
				//called when all files are uploaded
				window.location = "<?php echo site_url('datadeposit/datafiles/'.$this->uri->segment(3)); ?>";
			},
			
			Error: function(up, args) {
				// Called when a error has occured
				log('[error] ', args);
			}
		}

	});

	// Client side form validation
	$('form').submit(function(e) {
		var uploader = $('#uploader').pluploadQueue();

		// Validate number of uploaded files
		if (uploader.total.uploaded == 0) {
			// Files in queue upload them first
			if (uploader.files.length > 0) {
				// When all files are uploaded submit form
				uploader.bind('UploadProgress', function() {
					if (uploader.total.uploaded == uploader.files.length)
						$('form').submit();
				});

				uploader.start();
			} else
				alert('You must at least upload one file.');

			e.preventDefault();
		}
	});
	

});

</script>
