<?php //if (!$is_home):?>
                               <!--breadcrumbs -->
                                <?php $breadcrumbs_str= $this->breadcrumb->to_string();?>
                                <?php if ($breadcrumbs_str!=''):?>
                                    <div id="breadcrumb" class="notabs">
                                    <?php echo $breadcrumbs_str;?>
                                    </div>
                                <?php endif;?>
<div class="sidebar" <?php if ($this->uri->segment(2) != 'projects') echo 'style="margin-top:120px;width:220px;"'; else echo 'style="width:220px;"' ?>>
<!--side-bar -->
<script type="text/javascript">
    $(function() {
        if (!$('#srvcTabWrap').length) {
            $('.sidebar').css('display', 'none');
        }
    });
</script> 
<!--end-sidebar-->
<?php //endif;?>

<!--sidebar-reference-owner-->
<div class="grey-module" id="">
    <div class="m-head"> 
        <h2>Pending Tasks</h2>
    </div>
    <div class="m-body">
	<?php switch($this->uri->segment(2)):
    case 'create': ?>
        <li><?php echo t('title'); ?></li>
    <?php
        break;
	case 'update': ?>
        <li><?php echo t('study_desc'); ?></li>
    <?php break;
    case 'summary':
    if (empty($files) && empty($records)): ?>
        <li>Upload Files</li>
    <?php 
        endif; 
	case 'study': ?>
 		<li style="list-style-type:none">Mandatory Fields:</li>
		<script type="text/javascript">
            function turnoff(input) {
                var val = $.trim($('textarea[name*="'+input+'"], input[name*="'+input+'"]').val());
                if (val != '&nbsp;' && val != '' && val != ' ' && val != '[]' && val != '--') {
                    $('li.'+input).remove();
                }
                 if ($('.m-body:first li').length == 2) {
            $('.m-body:first').html("<li><?php echo t('no_pending_tasks'); ?></li>");
         }
            }
            <?php if ($this->uri->segment(2) == 'summary'): ?>
            $(function() {
                $('.m-body:first li').each(function() {
                    html=$(this).children('a').first().html();
                    $(this).html(html);
                });

            });
            <?php endif; ?>
        </script>
        <?php $x=0;  foreach($merged['merged'] as $input => $title):
        $input = str_replace('coll_dates', 'dates_datacollection', $input); 
        $input = str_replace('coverage_country', 'country', $input); ?>
        <script><?php echo '$(function() {turnoff(\'', $input, '\');});'; ?></script>
        <li class="<?php echo $input; ?>" style="margin-left: 5px"><?php echo '<a href="', current_url(), '#', $input, '">', $title, '</a>'; ?></li>
        <?php endforeach; ?>
 		<li style="list-style-type:none">Recommended Fields:</li>
		<?php foreach($merged['recommended'] as $input => $title): ?>
        <script><?php echo '$(function() {turnoff(\'', $input, '\');});'; ?></script>
        <li class="<?php echo $input; ?>" style="margin-left: 5px"><?php echo '<a href="', current_url(), '#', $input, '">', $title, '</a>'; ?></li>
        <?php endforeach; ?>
		<?php break; ?>
   <?php case 'submit':
            case 'datafiles': 
          ?>
   	<?php if (empty($files) || empty($records)): ?>
   		<li>Upload Files</li>
    <?php else: echo t('no_pending_tasks'); 
        endif; ?>
    	<?php break; ?>
  <?php case 'citations': ?>
  	<?php if (!isset($study[0]->citations)): ?>
    	<li>Add Citations</li>
    <?php else: echo t('no_pending_tasks');
        endif; ?>
    <?php break; ?>
    <?php endswitch; ?>
   </div>
</div>
<!--end-sidebar-reference-owner-->

<div class="grey-module" id="">
    <div class="m-head"> 
        <h2>Projects</h2>
    </div>
    <div class="m-body">
    	<?php foreach($projects as $project): ?>
        <li><a href="<?php echo site_url('datadeposit/update/'), '/', $project->id; ?>"><?php echo $project->title; ?></a></li>
        <?php endforeach; ?>
   </div>
</div>
<!--sidebar-reference-owner-->
<!--
<div class="grey-module" id="stpModule">
    <div class="m-head"> 
        <h2>For Service Owner</h2>
    </div>
    <div class="m-body">
        <?php //echo $sidebar;?>	
    </div><div class="m-footer"><span>&nbsp;</span></div>
</div>
-->
<!--end-sidebar-reference-owner-->

</div>
