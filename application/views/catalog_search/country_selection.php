<script>
$(document).ready(function()  {
	$("#tabs").tabs({
		beforeLoad: function (event, ui) {
		//don't load content if already loaded
        if ($(ui.panel).html()) {
            event.preventDefault();
        }
		}
	});
});
</script>
<div id="tabs">
  <ul>
    <li class="first"><a href="<?php echo site_url('catalog/get_country_selection_tab/'.$repositoryid.'/alphabatical');?>#tabs-1"><?php echo t('in_alphabatic_order');?></a></li>
    <?php foreach($regions as $region):?>
    	<li><a href="<?php echo site_url('catalog/get_country_selection_tab/'.$repositoryid.'/region/'.$region['id']);?>#tabs-region-<?php echo $region['id'];?>"><?php echo t($region['title']);?></a></li>
  	<?php endforeach;?>  
  </ul>
  <div id="country-selection-tab-content"></div>
</div>