<!--
<pre>
<?php //var_dump($rows);?>
</pre>
-->
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
.unlink-study .linked{padding-left:20px;}
.row{margin-bottom:10px;padding-bottom:10px;border-bottom:1px solid gainsboro;}
.survey-row .links{text-align:right;margin-right:10px;}
h3{font-size:16px;margin-top:0px;margin-bottom:5px;}
#survey-tags .count{color:gray;padding-left:5px;}
#survey-tags .tag{padding:5px;margin-bottom:5px;display:block}
#survey-tags .survey-tags-body{max-height:400px;overflow:auto;overflow-x:hidden;}
</style>
<?php
	//set default page size, if none selected
	if(!$this->input->get("ps"))
	{
		$ps=15;
	}
?>
<?php if ($rows): ?>
<?php		
	//pagination 
	$page_nums=$this->pagination->create_links();
	$current_page=($this->pagination->cur_page == 0) ? 1 : $this->pagination->cur_page;

	$sort_by=$this->input->get("sort_by");
	$sort_order=$this->input->get("sort_order");
	
	if (!$sort_by)
	{
		$sort_by='created';
	}
	
	//current page url
	$page_url=site_url().'/'.$this->uri->uri_string();
?>
<?php
	if ($this->pagination->cur_page>0) {
		$to_page=$this->pagination->per_page*$this->pagination->cur_page;

		if ($to_page> $this->pagination->total_rows) 
		{
			$to_page=$this->pagination->total_rows;
		}

		$pager=sprintf(t('showing %d-%d of %d')
						,(($this->pagination->cur_page-1)*$this->pagination->per_page+(1))
						,$to_page
						,$this->pagination->total_rows);
	}
	else
	{
		$pager=sprintf(t('showing %d-%d of %d')
				,$current_page
				,$this->pagination->total_rows
				,$this->pagination->total_rows);
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
    <?php if ( isset($this->active_repo->id)):?>
    	<span class="active-repo">[<?php echo $this->active_repo->title;?>]</span><span class="link-change"><?php echo anchor('admin/repositories/select',t('change_repo'));?></span>
    <?php endif;?>
</h1>

<!-- search form-->
<form class="left-pad" style="margin-bottom:10px;" method="GET" id="catalog-search">	

<div class="">  
  <select name="field" id="field" class="" style="margin-right:5px;">
    <option value="all"		<?php echo ($this->input->get('field')=='all') ? 'selected="selected"' : '' ; ?> ><?php echo t('all_fields');?></option>
    <option value="titl"	<?php echo ($this->input->get('field')=='titl') ? 'selected="selected"' : '' ; ?> ><?php echo t('title');?></option>
    <option value="nation"	<?php echo ($this->input->get('field')=='nation') ? 'selected="selected"' : '' ; ?> ><?php echo t('country');?></option>
    <option value="surveyid"><?php echo t('survey_id');?></option>
    <option value="authenty"><?php echo t('producer');?></option>
    <option value="sponsor"><?php echo t('sponsor');?></option>
    <option value="repositoryid"><?php echo t('repository');?></option>
  </select>
  
  <input  type="text" size="40" name="keywords" id="keywords" value="<?php echo form_prep($this->input->get('keywords')); ?>"/>
   <input class="" type="submit" value="<?php echo t('search');?>" name="search"/>
  <?php if ($this->input->get("keywords")!=''): ?>
    <a  class="btn-link" href="<?php echo site_url();?>/admin/catalog"><?php echo t('reset');?></a>
  <?php endif; ?>

</div>

<br/><br/>


<div class="row-fluid">
	<div id="surveys" class="span10">
	<table width="100%" style="background:gainsboro;">
    <tr>
        <td>
            <input type="checkbox" value="-1" id="chk_toggle" style="margin-left:8px;"/>
            <select id="batch_actions" >
                <option value="-1"><?php echo t('batch_actions');?></option>
                <option value="transfer"><?php echo t('transfer_ownership');?></option>
                <option value="publish"><?php echo t('publish');?></option>
                <option value="unpublish"><?php echo t('unpublish');?></option>
                <option value="delete"><?php echo t('delete');?></option>
            </select>
            <input class="" type="button" id="batch_actions_apply" name="batch_actions_apply" value="<?php echo t('apply');?>"/>
           	<span style="padding-right:20px"></span>
	        </td>
        <td align="right">

            <div class="btn-group" style="margin:5px;text-align:left;">
              <button class="btn btn-mini">Sort (<?php echo t($sort_by);?>)</button>
              <button class="btn btn-mini dropdown-toggle" data-toggle="dropdown">
                <span class="caret"></span>
              </button>
              <ul class="dropdown-menu">
                    <?php if ($this->config->item("regional_search")=='yes'):?>            
                    <li><?php echo create_sort_link($sort_by,$sort_order,'repositoryid',t('repositoryid'),$page_url,array('keywords','field','ps')); ?></li>
                    <li><?php echo create_sort_link($sort_by,$sort_order,'nation',t('country'),$page_url,array('keywords','field','ps')); ?>          </li>
                <?php endif;?> 
                <li><?php echo create_sort_link($sort_by,$sort_order,'titl',t('title'),$page_url,array('keywords','field','ps')); ?></li>
                <li><?php echo create_sort_link($sort_by,$sort_order,'surveyid',t('surveyid'),$page_url,array('keywords','field','ps')); ?></li>
                <li><?php echo create_sort_link($sort_by,$sort_order,'changed',t('modified'),$page_url,array('keywords','field','ps')); ?></li>
                <li><?php echo create_sort_link($sort_by,$sort_order,'published',t('published'),$page_url,array('keywords','field','ps')); ?></li>
              </ul>
            </div>

        </td>
    </tr>
	</table>

    <table class="grid-table" width="100%" cellspacing="0" cellpadding="0" >
        <?php $tr_class=""; ?>
        <?php foreach($rows as $row): ?>
            <?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
            <tr class="<?php echo $tr_class; ?>" id="s_<?php echo $row['id']; ?>"  valign="top">
                <td><input type="checkbox" value="<?php echo $row['id']; ?>" class="chk"/></td>
                <td>
                        <div class="survey-row">
                            <h3>
                            	<a href="<?php echo site_url().'/admin/catalog/edit/'.$row['id'];?>">
                                <?php if ($this->config->item("regional_search")=='yes'):?> 
                                    <?php echo $row['nation'];?> -
                                <?php endif;?>
                                <?php echo $row['titl'];?>
                                </a>
                            </h3>
                            <div>Producers: <?php echo $row['authenty'];?></div>
                            <div>Repository: <?php echo $row['repositoryid'];?>, 
                             modified on: <?php echo date($this->config->item('date_format'), $row['changed']); ?></div>
                            
                            <div class="links">
                            <span class="label"><a href="<?php echo site_url();?>/admin/catalog/edit/<?php echo $row['id'];?>">Edit</a></span>
                            <span class="label">Delete</span>
                            
                            <?php if ($row['repo_isadmin']==0):?>
                                <span class="label" title="<?php echo t('is_harvested_study');?>">Linked</span>
                            <?php elseif ($row['repo_isadmin']==1):?>
                                <span class="label label-success" title="<?php echo t('study_owned');?>">Owned</span>
                            <?php endif;?>                        
                            <?php if ($row['published']):?>
                                <span class="label label-success" title="<?php echo t('published');?>">Published</span>
                            <?php else:?>
                                <span class="label" title="<?php echo t('unpublished');?>">Draft</span>
                            <?php endif;?>
                            </div>
                        </div>
                </td>            
            </tr>
        <?php endforeach;?>
    </table>    

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

    <div id="survey-tags" class="span2">
    	<h3>Tags</h3>
        <div class="survey-tags-body">
		<?php foreach($this->catalog_tags as $tag):?>
            <div class="tag"><i class="icon-tag"></i> <?php echo $tag['tag'];?> <span class="badge"><?php echo $tag['total'];?></span></div>
        <?php endforeach;?>
        </div>
    </div>

</form>
</div>
</div>

<script type='text/javascript'>
//translations	
var i18n={
		'no_item_selected':"<?php echo t('js_no_item_selected');?>",
		'confirm_delete':"<?php echo t('js_confirm_delete');?>",
		'js_loading':"<?php echo t('js_loading');?>"
		};
</script>