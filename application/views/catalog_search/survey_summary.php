<style>
/*grid/table listing format*/
.grid-table{width:100%;}
.grid-table .header{background-color:none;text-align:left;}
.grid-table .header th{padding:5px;border-bottom:2px solid #C1DAD7;border-top:2px solid #C1DAD7;}
.grid-table .header a, .grid-table .header {color:black;}
.grid-table tr td{padding:5px;border-bottom:1px solid #C1DAD7;}
.grid-table .alternate{background-color:#FBFBFB}
.grid-table a{color:#00679C;text-decoration:none;}
.grid-table a:hover{color:maroon;}
.table-heading-row{}
.table-heading-cell{font-weight:bold;font-size:12px;border-bottom:2px solid #CFD9FE;}
.table-row{}
.table-row-alternate{background:#F7F7F7}
.table-cell,.table-cell-alternate{padding:5px;border-bottom:1px solid #F7F7F7;}
.table a{text-decoration:none;color:#003366}
.table a:hover{text-decoration:underline;color:black}
</style>

<div class="content-container">
<?php if (!$this->input->get("print")) :?>
<div style="text-align:right;padding-bottom:20px;">
<a target="_blank" 	title="<?php echo t('print');?>"	href="<?php echo site_url();?>/catalog/<?php echo $id; ?>/?print=yes" ><img src="images/print.gif" border="0"/></a>
<a target="_blank" 	title="<?php echo t('share_with_facebook');?>"		href="http://www.facebook.com/sharer.php?u=<?php echo current_url(); ?>&t=<?php echo ($nation. ' - '.$titl); ?>&src=sp"><img src="images/facebook.png"/></a>
<a target="_blank"  title="<?php echo t('share_with_twitter');?>"		href="http://twitter.com/share?_=<?php echo date("U");?>&count=none&original_referer=<?php echo current_url();?>&text=<?php echo ($nation. ' - '.$titl); ?>&url=<?php echo current_url();?>"><img src="images/twitter.png"/></a>
<a target="_blank"  title="<?php echo t('share_with_delicious');?>"	href="http://www.delicious.com/save?v=5&noui&jump=close&url=<?php echo current_url(); ?>&title=<?php echo ($nation. ' - '.$titl); ?>"><img src="images/delicious.png"/></a>
</div>

<?php endif;?>
<h1><?php echo $nation;?> - <?php echo $titl;?></h1>
<table class="grid-table" cellspacing="0">
	<tr class="header" >
    	<td><?php echo t('year');?></td>
        <td><?php 
				if ($data_coll_start==$data_coll_end)
				{
					echo $data_coll_start;
				}
				else
				{
					if ($data_coll_start!='')
					{
						$dates[]=$data_coll_start;
					}
					if ($data_coll_end!='')
					{
						$dates[]=$data_coll_end;
					}						
					echo implode(" - ", $dates);
				}
				
			?>
        </td>
    </tr>
	<tr>
    	<td><?php echo t('producers');?></td>
        <td><?php echo $authenty;?></td>
    </tr>
    <?php if (strlen($sponsor)>5):?>
	<tr>
    	<td><?php echo t('sponsors');?></td>
        <td><?php echo $sponsor;?></td>
    </tr>
    <?php endif;?>    
    <?php if (array_key_exists($repositoryid,$this->repositories)):?>
	<tr>
    	<td><?php echo t('source');?></td>
        <td><?php 
				$repo_link=sprintf('<a target="_blank" href="%s">%s</a>',$this->repositories[$repositoryid]['url'],$this->repositories[$repositoryid]['title']);
				$repo_source=sprintf(t('source_catalog'),$repo_link);
				echo $repo_source;
			?>
        </td>
    </tr>
    <?php endif;?>
    <tr>
    	<td>&nbsp;</td>
        <td><?php echo anchor("ddibrowser/$id",t('click_to_browse_metadata'), array('target'=>'_blank'));?></td>
    </tr>

</table>

<?php $this->load->view('catalog_search/survey_summary_resources',$resources);?>
<?php if ($citations):?>
<?php $this->load->view('catalog_search/survey_summary_citations',$citations);?>
<?php endif;?>
</div>