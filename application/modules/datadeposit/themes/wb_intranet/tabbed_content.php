<?php
$active_repo=$this->session->userdata('active_repository');
if ($active_repo=='')
{
	$active_repo='central';
}
?>
<!--tab-->
<div class="tab-panel">
        <div class="tab-h-l">
            <div class="tab-h-r">
                <div class="tab-h-m">
					<!--breadcrumbs -->
                    <?php $breadcrumbs_str= $this->breadcrumb->to_string();?>
					<?php if ($breadcrumbs_str!=''):?>
                        <div id="breadcrumb">
                        <?php echo $breadcrumbs_str;?>
                        </div>
                    <?php endif;?>
        
                </div>
            </div>
        </div>
        <div class="tab-body">
            <div id="srvcTabWrap" class="tab-wraper tabs-scroll" style="margin-right: 20px; ">
                <ul class="tabs tabs-scroll" id="srvcTab" style="left: 0px; ">
                    <li <?php if ($this->uri->segment(3)=='about'){echo 'class="sel"';}?> >
                        <a href="<?php echo site_url();?>/catalog/<?php echo $active_repo;?>/about">About</a>
                    </li>
                    <li  <?php if ($this->uri->segment(1)=='catalog' && $this->uri->segment(3)!=='about'){echo 'class="sel"';}?> >
                        <a href="<?php echo site_url();?>/catalog/<?php echo $active_repo;?>">Data Catalog</a>
                    </li>
                    <!--
                    <li <?php if ($this->uri->segment(1)=='citations'){echo 'class="sel"';}?>>
                        <a href="<?php echo site_url();?>/citations">Citations</a>
                    </li>
                    -->
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