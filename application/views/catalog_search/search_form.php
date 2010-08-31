<table width="100%" class="catalog-page-title" cellpadding="0" cellspacing="0" border="0">
<tr valign="baseline">
<td><h1><?php echo t('title_data_catalog');?></h1></td>
<td align="right">
<div class="page-links">
	<a id="link_export" title="<?php echo t('link_export_search');?>" href="<?php echo site_url();?>/catalog/export"><img src="images/export.gif" border="0" alt="Export"/></a>
	<?php echo anchor('catalog/help','<img src="images/linkto_help.gif" border="0" alt="Help"/>', array('class'=>'dlg','title'=>t('link_search_help')));?>
    <a title="<?php echo t('rss_feed');?>" href="<?php echo site_url();?>/catalog/rss" target="_blank"><img src="images/rss_icon.png" border="0" alt="RSS"/></a>
</div>
</td>
</tr>
</table>

<form name="search_form" id="search_form" method="get" autocomplete = "off">
<input type="hidden" id="view" name="view" value="<?php echo ($this->input->get_post('view')!=='v') ? 's': 'v'; ?>"/>

<div id="accordion" > 
	<?php if ($this->regional_search=='yes'):?>
	<h3><a href="#"><?php echo t('filter_by_country');?><span id="selected-countries" style="font-size:11px;padding-left:10px;"></span></a></h3> 
	<div id="countries-list" style="height:150px;font-size:11px;"><div style="text-align:right;">
    	<a  href="#" onclick="select_countries('all');return false;"><?php echo t('link_select_all');?></a> | 
        <a  href="#" onclick="select_countries('none');return false;"><?php echo t('link_clear');?></a> | 
        <a  href="#" onclick="select_countries('toggle');return false;"><?php echo t('link_toggle');?></a>
    </div> 
		<?php foreach($countries as $country): ?>
        	<div class="country">
                <input class="chk-country" type="checkbox" name="country[]" 
                	value="<?php echo form_prep($country['nation']); ?>" 
                    id="c-<?php echo form_prep($country['nation']); ?>"
                 />
                <label for="c-<?php echo form_prep($country['nation']); ?>">
                	<?php echo substr($country['nation'],0,25); ?> (<?php echo $country['surveys_found']; ?>)
                </label>
            </div>
        <?php endforeach;?>
	</div>
    <?php endif;?>
    <?php if ($this->topic_search=='yes'):?>
    	<!-- topics -->
        <h3><a href="#"><?php echo t('filter_by_topic');?> <span id="selected-topics" style="font-size:11px;padding-left:10px;"></span></a></h3> 
        <div id="topics-list" style="height:150px;font-size:11px;"> 
            <div style="text-align:right;">
            	<a  href="#" onclick="select_topics('all');return false;"><?php echo t('link_select_all');?></a> | 
                <a  href="#" onclick="select_topics('none');return false;"><?php echo t('link_clear');?></a>
            </div>
            <?php //echo create_topic_list($topics) ;?>
            <?php echo $topics_formatted;?>
            <br style="clear:both;">
        </div>
        <!-- end topics -->    
    <?php endif;?>
</div>
	<div class="ui-accordion-header ui-helper-reset ui-state-default ui-corner-all" style="padding:5px;">
   	<?php if ($this->config->item("year_search")=='yes'):?>
        <input type="hidden"/>
        <div style="margin-left:35px;font-style:normal;color:black;font-weight:normal;margin-bottom:5px;">
        <table border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td><?php echo t('show_studies_conducted_between');?>&nbsp;</td>
                <td><input type="hidden"/><?php echo form_dropdown('from', $years, get_form_value("from",isset($from) ? $from: ''), 'id="from"'); ?></td>
                <td>&nbsp;<?php echo t('and');?>&nbsp;</td>
                <td><?php echo form_dropdown('to', $years, get_form_value("to",isset($to) ? $to: end($years)),'id="to"'); ?></td>
            </tr>
        </table>
        </div>
	<?php endif;?>	
		<div class="variable-search">
    		<?php echo t('find');?> <input maxlength="100" type="text" name="sk" value="<?php echo form_prep(get_form_value('sk',isset($_REQUEST['sk']) ? $this->input->get_post('sk'): '')) ; ?>" style="margin-left:10px;margin-right:5px;width:65%;"/> <?php echo t('in_study_description');?>
		</div>    
        
        <div class="variable-search" style="margin-top:5px;">
        	<?php echo t('find');?> <input maxlength="100"  type="text" name="vk" value="<?php echo form_prep(get_form_value('vk',isset($_REQUEST['vk']) ? $this->input->get_post('vk'): '')) ; ?>" style="margin-left:10px;margin-right:5px;width:65%;"/> <?php echo t('in_variable_description');?>

			<div style="margin-left:37px;font-size:11px;margin-top:5px;">
            <?php echo t('variable_description_includes');?> 
			<input type="checkbox" name="vf[]" id="name" value="name" checked="checked"/><label for="label"><?php echo t('name');?> </label>
            <input type="checkbox" name="vf[]" id="label" value="labl" checked="checked"/><label for="label"><?php echo t('label');?> </label>
            <input type="checkbox" name="vf[]" id="question" value="qstn"  checked="checked"/><label for="question"><?php echo t('question');?> </label>
            <input type="checkbox" name="vf[]" id="categories" value="catgry"  checked="checked"/><label for="categories"><?php echo t('classification');?> </label>
			</div>            
        </div>
      
		<div style="text-align:right;margin-top:-15px;">
        	<input class="button" type="submit" id="btnsearch" name="search" value="<?php echo t('search');?>"/>
            <input class="button" style="background-color:gray;margin-left:5px;" type="button" name="search" value="<?php echo t('reset');?>" onclick="window.location.reload();"/>
		</div> 
    </div>

	<div id="surveys"><?php echo $search_result; ?></div>
</form> 
 

<script type="text/javascript">
//translations	
var i18n=
{
'searching':"<?php echo t('js_searching');?>",
'loading':"<?php echo t('js_loading');?>",
'invalid_year_range_selected':"<?php echo t('js_invalid_year_range_selected');?>",
'topic_selected':"<?php echo t('js_topic_selected');?>",
'topics_selected':"<?php echo t('js_topics_selected');?>",
'country_selected':"<?php echo t('js_country_selected');?>",
'countries_selected':"<?php echo t('js_countries_selected');?>",
'cancel':"<?php echo t('cancel');?>"
};

//min/max years
var years = {'from': '<?php reset($years);echo current($years); ?>', 'to': '<?php echo end($years); ?>'}; 

</script>