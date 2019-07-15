<?php
$active_repo="test";

$project_status=FALSE;


if (@$this->active_project && is_array($this->active_project)) {
	if (isset($this->active_project[0]->status)) {
		$project_status=$this->active_project[0]->status;
	}
}

if(in_array($project_status,array('closed', 'submitted', 'processed', 'accepted'))) {
	if ($this->uri->segment(2)!='summary'&& $this->uri->segment(2) !='request_reopen')
	{
		redirect('datadeposit/summary/'.(integer)$this->uri->segment(3));
	}
}

$uri     = $this->uri->segment(2);

$links   = array('update', 'study', 'datafiles', 'citations', 'summary', 'submit');
$color   = array_search($uri, $links);
?>


<?php if (in_array($project_status,array('submitted', 'closed', 'processed', 'accepted'))): ?>
    <script type="text/javascript">
        $(function() {
			$('#srvcTabWrap').css('display', 'none');
            $('.grey-module:first ').remove();
            $('span.mandatory').remove();
        });
    </script>
    <style type="text/css">
	.tab-body {
		margin-top: -76px;
	}
	</style>
<!--[if !IE]><!-->
    <style type="text/css">
	.info-box {
	}
	a.request {
		text-decoration:none;
	}
	a.request:hover {
		text-decoration:none;
	}
	</style>
 <!--<![endif]-->
<?php if ($this->active_project[0]->status == 'processed'): ?> 
<style type="text/css">
*+html .info-box {
	margin-top:-8px;
}
.info-box{
	margin-top:-14px!important;
	margin-bottom:-8px;
}
#project_name {
	position:relative;
	top:10px;
}
</style>

<?php endif; ?>
<?php if ($this->active_project[0]->status != 'processed'): ?> 
    <?php $message=isset($message)?$message:$this->session->flashdata('message');?>
	<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?> 
<?php endif; ?>
<div style="margin-top:10px;<?php echo ($this->active_project[0]->requested_reopen) ? 'height:110px;' : 'height:180px;'; ?>width:950px" class="info-box"><?php echo ($this->active_project[0]->status == 'processed') ? t('project_processed') : sprintf(t('project_locked_message'), date('M d, Y', $this->active_project[0]->submitted_on));?>
<?php if ($this->active_project[0]->status != 'processed'): ?> 
<style type="text/css">
  .sidebar {
	  margin-top: 10px !important;
  }
  #project_name {
	  margin-top: 0;
	  margin-bottom: 0;
  }
</style>
<?php echo form_open("datadeposit/request_reopen/".$this->uri->segment(3), 'id="request_reopen" style="margin:0;padding:0;margin-top:10px"');?>
    <?php if (!$this->active_project[0]->requested_reopen): ?>
    <div class="field">
		<label style="clear:both" for="reason">Request reopen reason:</label>
        <br />
		<textarea rows="5" cols="90" name="reason"></textarea>
	</div>  
 	<div style="text-align:left">
		<input class="button" type="hidden" name="reopen" value="Request" />
	</div>
	<?php echo form_close(); ?>                
<div style="float:left"><div onclick="$('#request_reopen').submit();" style="font-size:11pt;" class="button">
                		<span><?php echo t('request_reopen'); ?></span>
                	</div></div>
   <?php else: ?>
   <br />
   <p style="font-size:14px"><?php echo sprintf(t('project_was_requested'), date("M d, Y", $this->active_project[0]->requested_when));  ?></p>
   <?php endif; ?>
<?php endif; ?>
</div>
<?php endif;?>

<!--tab-->
<br />
<div class="tab-panel">
		
        <div class="tab-body">

			<script type="text/javascript">
            	$(function() {
					$('<div id="project_name"> <?php echo addslashes((isset($this->active_project[0]->title)) ? $this->active_project[0]->title : t('no_project_name_yet')); ?> </div>')
						.insertAfter('#srvcTabWrap');
					$('.tab-header').html(
						$('title').html()	
					);
				});
            </script>
			<div class="tab-header"></div>
            <div class="tab-content">
                <div class="tab-b-t-l">
                    <div class="tab-b-t-r">
                        <div class="tab-b-t-m">
                        </div>
                    </div>
                </div>
                <div id="datadeposit" class="tab-content-body">
                        <div class="show" style="display: block; ">
                        	<div>
							<?php echo isset($content) ? $content : '';?>
                            </div>
                        </div>
                </div>
                <div class="tab-b-b-l">
                    <div class="tab-b-b-r">
                        <div class="tab-b-b-m">
                            &nbsp;
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--<div class="tab-f-l">
            <div class="tab-f-r">
            </div>
        </div>-->
    </div>
<!--end tabs-->