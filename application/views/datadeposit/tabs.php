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



$links   = array('update', 'study', 'datafiles', 'citations', 'summary', 'submit');
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
            $('.m-body:first li').css('display', 'none');
        });
    </script>
<div class="info-box"><?php echo t('project_locked_message');?>
<div style="float:right"><a href="<?php echo site_url('datadeposit/request_reopen'), '/', $this->uri->segment(3);?>"><?php echo t('request_reopen'); ?></a></div>
</div>
<?php endif;?>

<!--tab-->
<div class="tab-panel">

        <div class="tab-body">
            <div id="srvcTabWrap" class="tab-wraper tabs-scroll" style="margin-right: 20px; ">
                <ul class="tabs tabs-scroll" id="srvcTab" style="left: 0px; ">
                    <?php if (in_array($project_status,array('submitted','closed'))): ?>
					<li <?php if ($this->uri->segment(2)=='summary'){echo 'class="sel"';}?> >
                        <a href="<?php echo site_url();?>/datadeposit/summary/<?php  echo $this->uri->segment(3); ?>">Summary</a>
                    </li>
                    <?php elseif($this->uri->segment(2)=='create'):?>
                       <li <?php if ($this->uri->segment(2)=='create'){echo 'class="sel"';}?> >
                            <a href="<?php echo site_url();?>/datadeposit/update/<?php echo $this->uri->segment(3); ?>">Project Information</a>
                        </li>
                    <?php else: ?>          
                        <li <?php if ($this->uri->segment(2)=='update'){echo 'class="sel"';}?> >
                            <a href="<?php echo site_url();?>/datadeposit/update/<?php echo $this->uri->segment(3); ?>">Project Information</a>
                        </li>
                        <li <?php if ($this->uri->segment(2)=='study'){echo 'class="sel"';}?> >
                            <a href="<?php echo site_url();?>/datadeposit/study/<?php echo $this->uri->segment(3); ?>">Study Description</a>
                        </li>
                        <li <?php if ($this->uri->segment(2)=='datafiles'){echo 'class="sel"';}?> >
                            <a href="<?php echo site_url();?>/datadeposit/datafiles/<?php echo $this->uri->segment(3); ?>">Data Files and Other Resources</a>
                        </li>
                        <li <?php if ($this->uri->segment(2)=='citations'){echo 'class="sel"';}?> >
                            <a href="<?php echo site_url();?>/datadeposit/citations/<?php  echo $this->uri->segment(3); ?>">Citations</a>
                        </li>
                        <li <?php if ($this->uri->segment(2)=='summary'){echo 'class="sel"';}?> >
                            <a href="<?php echo site_url();?>/datadeposit/summary/<?php  echo $this->uri->segment(3); ?>">Summary</a>
                        </li>
                        <li <?php if ($this->uri->segment(2)=='submit'){echo 'class="sel"';}?> >
                            <a href="<?php echo site_url();?>/datadeposit/submit/<?php echo $this->uri->segment(3); ?>">Submit</a>
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
               	<?php if ($project_status == 'draft'): ?>
                <?php if (isset($uri)): ?>
                	<?php if (isset($prev)): ?>
                <div style="float:left">
                	<a style="font-size:10pt" href="<?php echo $prev;?>"><?php echo "Back to ", ucfirst($links[$current-1]); ?></a>
                </div>
                	<?php endif; ?>
                	<?php if (isset($next)): ?>
                <div style="float:right">
                	<a style="font-size:10pt" href="<?php echo $next;?>"><?php echo "Next to ", ucfirst($links[$current+1]); ?></a>
                </div>
                	<?php endif; ?>
                <br />
                <?php endif; ?>
                <?php endif; ?>
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