<?php if (isset($_GET['print']) && $_GET['print'] == 'yes'): ?>
<script type="text/javascript" src="<?php echo site_url(); ?>javascript/jquery/jquery.js"></script>
<?php endif; ?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?> 
  
<div id="toTop">^ Back to Top</div>

<div class="contents page-review-submit">
    <?php $active_tab_class=' class= "ui-corner-top ui-tabs-selected ui-state-active"'; ?>
	<div id="tabs">
        <ul>
            <li><a href="<?php echo current_url();?>#tab-review"><?php echo t('review');?></a></li>
            <li><a href="<?php echo current_url();?>#tab-submit"><?php echo t('submit');?></a></li>
        </ul>
        <div id="tab-review">
        
			<!--quick links-->
            <div style="font-size:14px;text-align:right">
                Generate: <a href="<?php echo site_url('datadeposit'); ?>/export/<?php echo $project[0]->id; ?>?format=ddi">DDI</a> |
                <a href="<?php echo site_url('datadeposit'); ?>/export/<?php echo $project[0]->id; ?>?format=rdf">RDF</a> |
                <a target="_blank" href="<?php echo current_url();?>?print=yes"><?php echo t('print_preview'); ?></a> |
                <a href="javascript:void(0);" id="email_to_friend"><?php echo t('email_to_friend'); ?></a>
            </div>
		
        	<div id="email-project" style="display:none;">
            	<h3 style="margin-top:0px;padding-top:0px;">Email project</h3>
            	<input type="text" name="share_email" id="share_email" placeholder="Enter email address" style="width:300px;"/>
                <input type="button" name="send_email" id="send_email" value="Send Email" style="font-size:smaller;cursor:pointer;"/>
                <a href="#" class="cancel_email" >Cancel</a>
            </div>
        
			<?php $this->load->view('datadeposit/project_review');?>
        </div>
        <div id="tab-submit"><?php $this->load->view('datadeposit/project_submit');?></div>
 	</div>
</div>
    
    
<script type="text/javascript">
	jQuery(document).ready(function(){
		//tabs
		$("#tabs").tabs();
		
		//send email cancel button
		$('.cancel_email').click(function() {
			$("#email-project").hide();return false;
		});
		
		//show project email box
		$('#email_to_friend').click(function() {
			$("#email-project").show();return false;
		});
		
		//send email
		$('#send_email').click(function() {
			$.post( "<?php echo site_url('datadeposit/email_summary/');?>", 
				{ 
					email: $("#share_email").val(),
					pid: <?php echo $project[0]->id; ?>
			} );
			$("#email-project").hide();
		});
		
		<?php if ($this->input->post("submit_project")):?>
			//select submit tab
			$('#tabs').tabs( "option", "active", 1 );
		<?php endif;?>
		
	});
</script>    