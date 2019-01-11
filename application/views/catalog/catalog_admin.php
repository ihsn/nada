<style>
.filter-box{margin:5px;margin-right:20px;}
.filter-box li{font-size:11px;}
.filter-box a{text-decoration:none;color:black;display:block;padding:3px;padding-left:15px;background:url('images/bullet_green.png') left top no-repeat;}
.filter-box a:hover{background:black;color:white;}
.filter-field{
border: 1px solid gainsboro;
-moz-border-radius: 5px;
-webkit-border-radius: 5px;
color: #333;
margin-bottom:10px;
}
.filter-title {
	font-size: 14px;
	text-transform: uppercase;
	padding: 5px;
	background: gainsboro;
}
span.active-repo{font-size:smaller;color:gray;}
span.link-change{font-size:10px;padding-left:5px;}
</style>
<?php
	//set default page size, if none selected
	if(!$this->input->get("ps"))
	{
		$ps=15;
	}
?>
<div class="body-container" style="padding:10px;">
<?php include 'catalog_page_links.php';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<h1 class="page-title">
	<?php echo t('catalog_maintenance');?>
    <?php if (isset($this->active_repo) && $this->active_repo!=NULL):?>
    	<span class="active-repo">[<?php echo $this->active_repo->title;?>]</span><span class="link-change"><?php echo anchor('admin/repositories/select',t('change_repo'));?></span>
    <?php endif;?>
</h1>

<!-- search form-->
<form class="left-pad" style="margin-bottom:10px;" method="GET" id="catalog-search">	
  <input type="text" size="40" name="keywords" id="keywords" value="<?php echo form_prep($this->input->get('keywords')); ?>"/>
  <select name="field" id="field">
    <option value="all"		<?php echo ($this->input->get('field')=='all') ? 'selected="selected"' : '' ; ?> ><?php echo t('all_fields');?></option>
    <option value="titl"	<?php echo ($this->input->get('field')=='title') ? 'selected="selected"' : '' ; ?> ><?php echo t('title');?></option>
    <option value="nation"	<?php echo ($this->input->get('field')=='nation') ? 'selected="selected"' : '' ; ?> ><?php echo t('country');?></option>
    <option value="surveyid"><?php echo t('survey_id');?></option>
    <option value="authenty"><?php echo t('producer');?></option>
    <option value="sponsor"><?php echo t('sponsor');?></option>
    <option value="repositoryid"><?php echo t('repository');?></option>
  </select>
  <input type="submit" value="<?php echo t('search');?>" name="search"/>
  <?php if ($this->input->get("keywords")!=''): ?>
    <a href="<?php echo site_url();?>/admin/catalog"><?php echo t('reset');?></a>
  <?php endif; ?>
<br/><br/>

<?php if ($rows): ?>
<?php		
	//pagination 
	$page_nums=$this->pagination->create_links();
	$current_page=($this->pagination->cur_page == 0) ? 1 : $this->pagination->cur_page;

	$sort_by=$this->input->get("sort_by");
	$sort_order=$this->input->get("sort_order");			
	
	//current page url
	$page_url=site_url().'/'.$this->uri->uri_string();
?>
<?php
	if ($this->pagination->cur_page>0) {
		$to_page=$this->pagination->per_page*$this->pagination->cur_page;

		if ($to_page> $this->pagination->get_total_rows()) 
		{
			$to_page=$this->pagination->get_total_rows();
		}

		$pager=sprintf(t('showing %d-%d of %d')
						,(($this->pagination->cur_page-1)*$this->pagination->per_page+(1))
						,$to_page
						,$this->pagination->get_total_rows());
	}
	else
	{
		$pager=sprintf(t('showing %d-%d of %d')
				,$current_page
				,$this->pagination->get_total_rows()
				,$this->pagination->get_total_rows());
	}
?>

<table width="100%">
    <tr>
        <td>
			<select id="batch_actions">
                    <option value="-1"><?php echo t('batch_actions');?></option>
                    <option value="delete"><?php echo t('delete');?></option>
                </select>
                <input type="button" id="batch_actions_apply" name="batch_actions_apply" value="<?php echo t('apply');?>"/>              
        </td>
        <td align="right">
            <div class="pagination"><em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?></div>
        </td>
    </tr>
</table>

<div id="surveys">
    <div class="row-header">
        <div class="row-title">
          <div style="float:left;"><input type="checkbox" id="chk_toggle"/></div>
          <div class="row-text">
			<?php if ($this->config->item("regional_search")=='yes'):?>
                <span style="float:left;display:block;width:150px;overflow:hidden;"><?php echo create_sort_link($sort_by,$sort_order,'nation',t('country'),$page_url,array('keywords','field','ps')); ?></span>
            <?php endif;?>                
			<?php echo create_sort_link($sort_by,$sort_order,'title',t('title'),$page_url,array('keywords','field','ps')); ?></div>
          <div class="row-date"><?php echo create_sort_link($sort_by,$sort_order,'changed',t('modified'),$page_url,array('keywords','field','ps')); ?></div>
        </div>
    </div>
	    
	<?php $tr_class=""; ?>
	<?php foreach($rows as $row): ?>
	    <?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
      <div class="row <?php echo $tr_class; ?>" id="<?php print $row['id']; ?>" >
        <div class="row-title" >
        	<div style="float:left;padding:0px;margin-top:-2px;"><input type="checkbox" class="chk" value="<?php print $row['id']; ?>"/></div>
          <div class="row-text">
          	<?php if ($this->config->item("regional_search")=='yes'):?>
			  	<span style="float:left;display:block;width:150px;overflow:hidden;"><b><?php echo $row['repositoryid'];?></b> <?php echo $row['nation'];?></span>
            <?php endif;?>
          	<?php echo $row['title']; ?>
          </div>
          <div class="row-date"><?php echo date($this->config->item('date_format_long'), $row['changed']); ?> <img src="images/bullet-green.gif"/></div>
        </div>
      </div>
    <?php endforeach;?>

<table width="100%">
    <tr>
        <td>
        <?php echo t("select_number_of_records_per_page");?>:
        <?php echo form_dropdown('ps', array(5=>5,10=>10,15=>15,30=>30,50=>50,100=>100,500=>t('ALL')), get_form_value("ps",isset($ps) ? $ps : ''),'id="ps" style="font-size:10px;"'); ?>
        </td>
        <td>    
            <div class="pagination">
                    <em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?>
            </div>
		</td>
    </tr>
</table>
</div>
<?php else: ?>
<?php echo t('no_records_found');?>
<?php endif; ?>
</form>


</div>

<script type='text/javascript'>
//translations	
var i18n={
		'no_item_selected':"<?php echo t('js_no_item_selected');?>",
		'confirm_delete':"<?php echo t('js_confirm_delete');?>",
		'js_loading':"<?php echo t('js_loading');?>"
		};
 
function IFrameDialog(href){
	//$.fn.ceebox.overlay();
	//$.fn.ceebox.popup(href,{onload:true});
	//return false;
}

function popup_dialog(obj) {
	var settings = {
			centerBrowser:1,
			centerScreen:1,
			height:600,
			left:0,
			location:0,
			menubar:0,
			resizable:1,
			scrollbars:1,
			status:0,
			width:800,
			windowName:$(obj).attr("id"),
			windowURL:$(obj).attr("href"),
			top:0,
			toolbar:0
		};
		var windowFeatures =    'height=' + settings.height +
								',width=' + settings.width +
								',toolbar=' + settings.toolbar +
								',scrollbars=' + settings.scrollbars +
								',status=' + settings.status + 
								',resizable=' + settings.resizable +
								',location=' + settings.location +
								',menuBar=' + settings.menubar;
		
		if ($.browser.msie) {//IE hack
			centeredY = (window.screenTop - 120) + ((((document.documentElement.clientHeight + 120)/2) - (settings.height/2)));
			centeredX = window.screenLeft + ((((document.body.offsetWidth + 20)/2) - (settings.width/2)));
		}else{
			centeredY = window.screenY + (((window.outerHeight/2) - (settings.height/2)));
			centeredX = window.screenX + (((window.outerWidth/2) - (settings.width/2)));
		}

		window.open(settings.windowURL, settings.windowName, windowFeatures+",left="+centeredX+",top="+centeredY).focus();
		return false;
}


function UploadDialog(href){
	$.fn.ceebox.overlay();
	$.fn.ceebox.popup(href,{onload:true, htmlWidth:600,htmlHeight:450, unload:function(){location.reload();}});
	return false;		  	
}

//checkbox select/deselect
jQuery(document).ready(function(){
	$("#chk_toggle").click(
			function (e) 
			{
				$('.chk').each(function(){ 
                    this.checked = (e.target).checked; 
                }); 
			}
	);
	$(".chk").click(
			function (e) 
			{
			   if (this.checked==false){
				$("#chk_toggle").attr('checked', false);
			   }			   
			}
	);			
	$("#batch_actions_apply").click(
		function (e){
			if( $("#batch_actions").val()=="delete"){
				batch_delete();
			}
		}
	);
});

function batch_delete(){
	if ($('.chk:checked').length==0){
		alert(i18n.no_item_selected);
		return false;
	}
	if (!confirm(i18n.confirm_delete))
	{
		return false;
	}
	selected='';
	$('.chk:checked').each(function(){ 
		if (selected!=''){selected+=',';}
        selected+= this.value; 
     });
	
	$.ajax({
		timeout:1000*120,
		cache:false,
        dataType: "json",
		data:{ submit: "submit"},
		type:'POST', 
		url: CI.base_url+'/admin/catalog/delete/'+selected+'/?ajax=true',
		success: function(data) {
			if (data.success){
				location.reload();
			}
			else{
				alert(data.error);
			}
		},
		error: function(XHR, textStatus, thrownError) {
			alert("Error occured " + XHR.status);
		}
	});	
}

function share_ddi(e,surveyid)
{
	share=0;
	if ($("#"+e.id).is(':checked')==true) {share=1;}
	url=CI.base_url+'/admin/catalog/shareddi/'+surveyid+'/'+share;
	$.get(url);
}
//page change
$('#ps').change(function() {
  $('#catalog-search').submit();
});
</script>