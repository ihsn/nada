<style>
.review-pages li{
padding: 0px;
line-height: 150%;
background: none;
margin-bottom: 10px;
}
</style>
<div class="tab-sidebar">
    <ul class="review-pages">
    <li><a href="<?php echo site_url('catalog/'.$study_id.'/review');?>">Reviewer notes</a></li>
    <li><a href="<?php echo site_url('catalog/'.$study_id.'/review/resources');?>">Microdata and other resources</a></li>
    </ul>
</div>
<div class="tab-body" >
<!--
	<div class="reviewer-notes-container"><?php //echo $this->load->view('ddibrowser/study_notes');?></div>
-->
	<?php echo $tab_content;?>
</div>