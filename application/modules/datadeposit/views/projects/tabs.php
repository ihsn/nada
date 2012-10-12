<?php
$active_repo="test";

$project_status=FALSE;

if (isset($this->active_project) && is_array($this->active_project)) {
	$project_status=$this->active_project[0]->status;
}
if(in_array($project_status,array('submitted','closed'))) {
	if ($this->uri->segment(2)!='summary'&& $this->uri->segment(2) !='request_reopen')
	{
		redirect('projects/summary/'.(integer)$this->uri->segment(3));
	}
}
?>


<?php if (in_array($project_status,array('submitted','closed'))): ?>
    <script type="text/javascript">
        $(function() {
            $('.m-body:first li').css('display', 'none');
        });
    </script>
<div class="info-box"><?php echo t('project_locked_message');?></div>
<?php endif;?>

<!--tab-->
<div class="tab-panel">

        <div class="tab-body">
            <div id="srvcTabWrap" class="tab-wraper tabs-scroll" style="margin-right: 20px; ">
                <ul class="tabs tabs-scroll" id="srvcTab" style="left: 0px; ">
                    <?php if (in_array($project_status,array('submitted','closed'))): ?>
					<li <?php if ($this->uri->segment(2)=='summary'){echo 'class="sel"';}?> >
                        <a href="<?php echo site_url();?>/projects/summary/<?php  echo $this->uri->segment(3); ?>">Summary</a>
                    </li>
                    <?php else:?>
                        <li <?php if ($this->uri->segment(2)=='update' || $this->uri->segment(2)=='create'){echo 'class="sel"';}?> >
                            <a href="<?php echo site_url();?>/projects/update/<?php echo $this->uri->segment(3); ?>">Project Information</a>
                        </li>
                        <li <?php if ($this->uri->segment(2)=='study'){echo 'class="sel"';}?> >
                            <a href="<?php echo site_url();?>/projects/study/<?php echo $this->uri->segment(3); ?>">Study Description</a>
                        </li>
                        <li <?php if ($this->uri->segment(2)=='datafiles'){echo 'class="sel"';}?> >
                            <a href="<?php echo site_url();?>/projects/datafiles/<?php echo $this->uri->segment(3); ?>">Datafiles/Resources</a>
                        </li>
                        <li <?php if ($this->uri->segment(2)=='citations'){echo 'class="sel"';}?> >
                            <a href="<?php echo site_url();?>/projects/citations/<?php  echo $this->uri->segment(3); ?>">Citations</a>
                        </li>
                        <li <?php if ($this->uri->segment(2)=='summary'){echo 'class="sel"';}?> >
                            <a href="<?php echo site_url();?>/projects/summary/<?php  echo $this->uri->segment(3); ?>">Summary</a>
                        </li>
                        <li <?php if ($this->uri->segment(2)=='submit'){echo 'class="sel"';}?> >
                            <a href="<?php echo site_url();?>/projects/submit/<?php echo $this->uri->segment(3); ?>">Submit</a>
                        </li>
					<?php endif; ?>
                </ul>
            </div>


            <div class="tab-content">
                <div class="tab-b-t-l">
                    <div class="tab-b-t-r">
                        <div class="tab-b-t-m">
                        </div>
                    </div>
                </div>
                <div class="tab-content-body">
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
        <div class="tab-f-l">
            <div class="tab-f-r">
            </div>
        </div>
    </div>
<!--end tabs-->