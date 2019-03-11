<?php
//active repositoryid
$repoid='';
if (isset($this->active_repo) && $this->active_repo!==FALSE)
{
	$repoid=$this->active_repo['repositoryid'].'/';
}

//css/js to hide the accordion content to fix the flash of unstyle html
$script='document.documentElement.className = "js";';
$css='.js .flash {display: none;}
#countries-list, #topics-list{height:150px;}
.js #countries-list, .js #topics-list{height:auto;}
';
$this->template->add_css($css, $type = 'embed');
$this->template->add_js($script, $type = 'embed');
?>
<?php /*?>
<table width="100%" class="catalog-page-title" cellpadding="0" cellspacing="0" border="0">
<tr valign="baseline">
<td><h2><?php echo $this->page_title;?></h2></td>
<td align="right">
<div class="page-links">
	<a id="link_export" title="<?php echo t('link_export_search');?>" href="<?php echo site_url();?>/catalog/export"><img src="images/export.gif" border="0" alt="Export"/></a>
    <a title="<?php echo t('rss_feed');?>" href="<?php echo site_url();?>/catalog/rss" target="_blank"><img src="images/rss_icon.png" border="0" alt="RSS"/></a>
</div>
</td>
</tr>
</table>
<?php */?>

<form name="search_form" id="search_form" method="get" autocomplete = "off">
<input type="hidden" id="view" name="view" value="<?php echo (isset($this->view) && $this->view=='v') ? 'v': 's'; ?>"/>
<input type="hidden" id="ps" name="ps" value="<?php echo $this->limit; ?>"/>
<input type="hidden" id="repo" name="repo" value="<?php echo $this->filter->repo; ?>"/>
<div id="accordion" > 
	<?php if ($this->regional_search=='yes'):?>
	<h3 class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-top">
    	<a href="#"><?php echo t('filter_by_country');?><span id="selected-countries" style="font-size:11px;padding-left:10px;"></span></a>
    </h3> 
	<div class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom" id="countries-list" style="font-size:11px;xheight:150px;">
    	<div class="flash">
        <div style="text-align:right;">
            <a  href="#" onclick="select_countries('all');return false;"><?php echo t('link_select_all');?></a> | 
            <a  href="#" onclick="select_countries('none');return false;"><?php echo t('link_clear');?></a> | 
            <a  href="#" onclick="select_countries('toggle');return false;"><?php echo t('link_toggle');?></a>
        </div>

		<?php foreach($countries as $country): ?>
        	<div class="country" >
                <input class="chk-country" type="checkbox" name="country[]" 
                	value="<?php echo form_prep($country['nation']); ?>" 
                    id="c-<?php echo form_prep($country['nation']); ?>"
                    <?php if($this->country!='' && in_array($country['nation'],$this->country)):?>
                    checked="checked"
                    <?php endif;?>
                 />
                <label for="c-<?php echo form_prep($country['nation']); ?>">
                	<?php echo substr($country['nation'],0,25); ?> (<?php echo $country['surveys_found']; ?>)
                </label>
            </div>            
        <?php endforeach;?>

        </div>
	</div>
    <?php endif;?>


	<!-- da filter -->
    <?php if (is_array($this->da_types) && count($this->da_types)>0):?>
    	<?php  $this->load->view("catalog_search/filter_da"); ?>
    <?php endif;?>    
    <!-- end da filter -->
    
    <?php if ($this->collection_search=='yes'):?>
        <!-- center filter-->
        <?php  $this->load->view("catalog_search/filter_collections"); ?>
	<?php endif;?>    
    

    <?php if ($this->topic_search=='yes'):?>
    	<!-- topics -->
        <h3 class="ui-accordion-header ui-helper-reset ui-state-default ui-corner-all">
        <a href="#"><?php echo t('filter_by_topic');?> <span id="selected-topics" style="font-size:11px;padding-left:10px;"></span></a>
        </h3> 
        <div class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom" id="topics-list" style="font-size:11px;xheight:150px;"> 
        	<div class="flash">
            <div style="text-align:right;">
            	<a  href="#" onclick="select_topics('all');return false;"><?php echo t('link_select_all');?></a> | 
                <a  href="#" onclick="select_topics('none');return false;"><?php echo t('link_clear');?></a>
            </div>
            <?php echo $topics_formatted;?>
            </div>
            <br style="clear:both;">
        </div>
        <!-- end topics -->    
    <?php endif;?>
</div>
	<div class="ui-accordion-header ui-helper-reset ui-state-default ui-corner-all" style="padding:5px;">
   	<?php if ($this->config->item("year_search")=='yes'):?>
        <input type="hidden"/>
        <div style="margin-left:35px;font-style:normal;color:black;font-weight:normal;margin-bottom:5px;clear:both;">
        <table border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td><?php echo t('show_studies_conducted_between');?>&nbsp;</td>
                <td><input type="hidden"/><?php echo form_dropdown('from', $years, ((isset($this->from) && $this->from!='') ? $this->from : end($years)), 'id="from"'); ?></td>
                <td>&nbsp;<?php echo t('and');?>&nbsp;</td>
                <td><?php echo form_dropdown('to', $years, (isset($this->to) && $this->to!='') ? $this->to: '','id="to"'); ?></td>
            </tr>
        </table>
        </div>
	<?php endif;?>	
		<div class="variable-search">
    		<?php echo t('find');?> <input maxlength="100" type="text" name="sk" value="<?php echo isset($this->sk) ? $this->sk : '' ; ?>" style="margin-left:10px;margin-right:5px;width:65%;"/> <?php echo t('in_study_description');?>
		</div>    
        
        <div class="variable-search" style="margin-top:5px;">
        	<?php echo t('find');?> <input maxlength="100"  type="text" name="vk" value="<?php echo isset($this->vk) ? $this->vk : '' ; ?>" style="margin-left:10px;margin-right:5px;width:65%;"/> <?php echo t('in_variable_description');?>

			<div style="margin-left:37px;font-size:11px;margin-top:5px;">
            <?php echo t('variable_description_includes');?> 
			<input type="checkbox" name="vf[]" id="name" value="name"  <?php if(isset($this->vf) && $this->vf!='' && in_array('name',$this->vf)){ echo 'checked="checked"';}?>/><label for="label"><?php echo t('name');?> </label>
            <input type="checkbox" name="vf[]" id="label" value="labl" <?php if(isset($this->vf) && $this->vf!='' && in_array('labl',$this->vf)){ echo 'checked="checked"';}?>/><label for="label"><?php echo t('label');?> </label>
            <input type="checkbox" name="vf[]" id="question" value="qstn"  <?php if(isset($this->vf) && $this->vf!='' && in_array('qstn',$this->vf)){ echo 'checked="checked"';}?>/><label for="question"><?php echo t('question');?> </label>
            <input type="checkbox" name="vf[]" id="categories" value="catgry"  <?php if(isset($this->vf) && $this->vf!='' && in_array('catgry',$this->vf)){ echo 'checked="checked"';}?>/><label for="categories"><?php echo t('classification');?> </label>
			</div>                    
        </div>

		<div style="text-align:right;margin-top:-15px;" class="search-buttons">
        	<input class="button" type="submit" id="btnsearch" name="search" value="<?php echo t('search');?>"/>
            <input class="btn-cancel" type="button" id="reset" name="reset" onclick="window.location.href='<?php echo site_url();?>/catalog/<?php echo $repoid;?>?reset=reset'"  value="<?php echo t('reset');?>"/>
		</div>
    </div>
	<div id="surveys" ><?php echo $search_result; ?></div>
</form>

<div class="da-legend">
<label title="<?php echo t('link_data_direct');?>" for="da_direct"><img src="images/form_direct.gif" /> <?php echo t('legend_direct_access');?></label>
<label title="<?php echo t('link_data_public_hover');?>" for="da_public"><img src="images/form_public.gif" /> <?php echo t('legend_data_public');?></label>
<label title="<?php echo t('link_data_licensed_hover');?>" for="da_licensed"><img src="images/form_licensed.gif" /> <?php echo t('legend_data_licensed');?></label>
<label title="<?php echo t('link_data_enclave_hover');?>" for="da_enclave"><img src="images/form_enclave.gif" /> <?php echo t('legend_data_enclave');?></label>
<label title="<?php echo t('link_data_remote_hover');?>" for="da_remote"><img src="images/form_remote.gif" /> <?php echo t('legend_data_remote');?></label>
<br />
<label title="<?php echo t('link_citations_hover');?>" for="citation"><img src="images/book_open.png" /> <?php echo t('legend_citations');?></label>
</div>


<script type="text/javascript">
//translations	
var i18n=
{
'searching':"<?php echo t('js_searching');?>",
'loading':"<?php echo t('js_loading');?>",
'invalid_year_range_selected':"<?php echo t('js_invalid_year_range_selected');?>",
'topic_selected':"<?php echo t('js_topic_selected');?>",
'topics_selected':"<?php echo t('js_topics_selected');?>",
'collection_selected':"<?php echo t('js_collection_selected');?>",
'collections_selected':"<?php echo t('js_collections_selected');?>",
'country_selected':"<?php echo t('js_country_selected');?>",
'countries_selected':"<?php echo t('js_countries_selected');?>",
'collection_selected':"<?php echo t('js_collection_selected');?>",
'collections_selected':"<?php echo t('js_collections_selected');?>",
'cancel':"<?php echo t('cancel');?>"
};

//min/max years
var years = {'from': '<?php reset($years);echo current($years); ?>', 'to': '<?php echo end($years); ?>'}; 
</script>