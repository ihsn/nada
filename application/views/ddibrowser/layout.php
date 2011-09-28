<script type="text/javascript"> 
   var CI = {
				'base_url': '<?php echo site_url(); ?>',
				'current_section': '<?php echo site_url().'/'.$this->uri->segment(1).'/'.$this->uri->segment(2); ?>',
				'js_loading': '<?php echo t('js_loading'); ?>'  
			}; 	
</script> 

	<script>
		$(function(){
			//tree-view 
			$(".filetree").treeview({collapsed: false});
		});
	</script>

<div class="page-body" >
<?php if (isset($survey_title)):?>
		<h2><?php echo $survey_title;?></h2>
<?php endif;?>

<?php echo $body;?>
</div>

<div class="ddi-sidebar" >
	<?php echo isset($sidebar) ? $sidebar : ''; ?>
</div>
<br style="clear:both;"/>