<?php
$active_repo="test";

$project_status=FALSE;


if (@$this->active_project && is_array($this->active_project)) {
	if (isset($this->active_project[0]->status)) {
		$project_status=$this->active_project[0]->status;
	}
}


if(in_array($project_status,array('submitted','closed'))) {
	if ($this->uri->segment(2)!='summary'&& $this->uri->segment(2) !='request_reopen')
	{
		redirect('datadeposit/summary/'.(integer)$this->uri->segment(3));
	}
}

$uri        = $this->uri->segment(2);

$links   = array('update', 'study', 'datafiles', 'citations', 'summary', 'submit');
$color   = array_search($uri, $links);

if (in_array($this->uri->segment(2), $links)) {
	$uri        = $this->uri->segment(2);
	$current    = array_search($uri, $links);
	if ($uri == 'update') {
		$next = $links[$current+1];
		$next = site_url("datadeposit/{$next}/{$this->uri->segment(3)}");
	} else if ($uri == 'submit') {
		$prev = $links[$current-1];
		$prev = site_url("datadeposit/{$prev}/{$this->uri->segment(3)}");
	} else {
		$prev = $links[$current-1];
		$prev = site_url("datadeposit/{$prev}/{$this->uri->segment(3)}");
		$next = $links[$current+1];
		$next = site_url("datadeposit/{$next}/{$this->uri->segment(3)}");
	}
	$links = str_replace(array('update', 'study', 'datafiles'), array('Project Information', 'Study Description', 'Datafiles and Resources'), $links);
}

?>


<?php if (in_array($project_status,array('submitted','closed'))): ?>
    <script type="text/javascript">
        $(function() {
            $('.m-body:first li').remove();
            $('.m-body:first').html("<li><?php echo t('no_pending_tasks'); ?></li>")
            $('span.mandatory').remove();
        });
    </script>
<div class="info-box"><?php echo t('project_locked_message');?>
<div style="float:right"><a href="<?php echo site_url('datadeposit/request_reopen'), '/', $this->uri->segment(3);?>"><?php echo t('request_reopen'); ?></a></div>
</div>
<?php endif;?>

<!--tab-->
<br />
<div class="tab-panel">

        <div class="tab-body">
            <div id="srvcTabWrap" class="tab-wraper tabs-scroll" style="margin-right: 20px; ">
                <div class="tabs tabs-scroll" id="srvcTab" style="left: 0px; ">
                    <?php if (in_array($project_status,array('submitted','closed'))): ?>
                        <div id="color5" class="navbox" <?php if ($this->uri->segment(2)=='summary'){echo 'class="sel"';}?> >
                        	<img src="<?php echo site_url(), '/../images/right-arrow.png'; ?>" alt="arrow" />                            
                            <a style="position: relative;top: 22px;left: 25px;" href="<?php echo site_url();?>/datadeposit/summary/<?php  echo $this->uri->segment(3); ?>">Summary</a>
                        </div>
                    <?php elseif($this->uri->segment(2)=='create'):?>
                        <div style="margin-left:0!important" id="color1" class="navbox" <?php if ($this->uri->segment(2)=='update'){echo 'class="sel"';}?> >
                        	<img src="<?php echo site_url(), '/../images/right-arrow.png'; ?>" alt="arrow" />
                            <a style="position: relative;top: 12px;left: 18px;"  href="<?php echo site_url();?>/datadeposit/update/<?php echo $this->uri->segment(3); ?>">Project Information</a>
                        </div>
                    <?php else: ?>          
                        <div style="margin-left:0!important" id="color1" class="navbox" <?php if ($this->uri->segment(2)=='update'){echo 'class="sel"';}?> >
                        	<img src="<?php echo site_url(), '/../images/right-arrow.png'; ?>" alt="arrow" />
                            <a style="position: relative;top: 12px;left: 18px;"  href="<?php echo site_url();?>/datadeposit/update/<?php echo $this->uri->segment(3); ?>">Project Information</a>
                        </div>
                        <div id="color2" class="navbox" <?php if ($this->uri->segment(2)=='study'){echo 'class="sel"';}?> >
                        	<img src="<?php echo site_url(), '/../images/right-arrow.png'; ?>" alt="arrow" />
                            <a style="position: relative;top: 12px;left: 20px;" href="<?php echo site_url();?>/datadeposit/study/<?php echo $this->uri->segment(3); ?>">Study Description</a>
                        </div>
                        <div id="color3" class="navbox" <?php if ($this->uri->segment(2)=='datafiles'){echo 'class="sel"';}?> >
                        	<img src="<?php echo site_url(), '/../images/right-arrow.png'; ?>" alt="arrow" />                            
                            <a style="position: relative;top: 8px;left: 23px;" href="<?php echo site_url();?>/datadeposit/datafiles/<?php echo $this->uri->segment(3); ?>">Data Files and Other Resources</a>
                        </div>
                        <div id="color4" class="navbox" <?php if ($this->uri->segment(2)=='citations'){echo 'class="sel"';}?> >
                        	<img src="<?php echo site_url(), '/../images/right-arrow.png'; ?>" alt="arrow" />                           
                            <a style="position: relative;top: 22px;left: 25px;" href="<?php echo site_url();?>/datadeposit/citations/<?php  echo $this->uri->segment(3); ?>">Citations</a>
                        </div>
                        <div id="color5" class="navbox" <?php if ($this->uri->segment(2)=='summary'){echo 'class="sel"';}?> >
                        	<img src="<?php echo site_url(), '/../images/right-arrow.png'; ?>" alt="arrow" />                            
                            <a style="position: relative;top: 22px;left: 25px;" href="<?php echo site_url();?>/datadeposit/summary/<?php  echo $this->uri->segment(3); ?>">Summary</a>
                        </div>
                        <div id="color6" class="navbox" <?php if ($this->uri->segment(2)=='submit'){echo 'class="sel"';}?> >
                            <a style="position: relative;top: 22px;left: 29px;" href="<?php echo site_url();?>/datadeposit/submit/<?php echo $this->uri->segment(3); ?>">Submit</a>
                        </div>
					<?php endif; ?>
                    <p id="here" style="width:74px;float:left;clear:both;margin-top:-1px;margin-left:12px">You are here</p>
                </div>
            </div>

			<div class="tab-header"></div>
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