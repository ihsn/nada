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
</style>
<?php
	//set default page size, if none selected
	if(!$this->input->get("ps"))
	{
		$ps=15;
	}
?>
<div class="body-container" style="padding:10px;">
<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<h1 class="page-title">
	<?php echo t('catalog_history');?>
</h1>

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

<div class="catalog-history">

<table class="grid-table" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">
            <?php if ($this->config->item("regional_search")=='yes'):?>            
                <th><?php echo create_sort_link($sort_by,$sort_order,'nation',t('country'),$page_url,array('keywords','field','ps')); ?></th>                
            <?php endif;?> 
            <th nowrap="nowrap"><?php echo create_sort_link($sort_by,$sort_order,'titl',t('title'),$page_url,array('keywords','field','ps')); ?></th>
            <th nowrap="nowrap"><?php echo create_sort_link($sort_by,$sort_order,'proddate',t('year'),$page_url,array('keywords','field','ps')); ?></th>
            <th nowrap="nowrap"><?php echo create_sort_link($sort_by,$sort_order,'created',t('created'),$page_url,array('keywords','field','ps')); ?></th>
            <th nowrap="nowrap"><?php echo create_sort_link($sort_by,$sort_order,'changed',t('modified'),$page_url,array('keywords','field','ps')); ?></th>
        </tr>
	<?php $tr_class=""; ?>
    <?php foreach($rows as $row): ?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="row <?php echo $tr_class; ?>" id="s_<?php echo $row['id']; ?>" >
			<?php if ($this->config->item("regional_search")=='yes'):?>
                <td><?php echo $row['nation'];?></td>
            <?php endif;?>
            <td><a href="<?php echo site_url();?>/catalog/<?php echo $row['id']; ?>"><?php echo $row['titl']; ?></a></td>
            <td><?php echo ($row['data_coll_start']) > 0 ? $row['data_coll_start'] : 'N/A'; ?></td>
            <td><?php echo date($this->config->item('date_format'), $row['created']); ?></td>
            <td><?php echo date($this->config->item('date_format'), $row['changed']); ?></td>
        </tr>
    <?php endforeach;?>
</table>    


<table width="100%" style="margin-top:10px;">
    <tr>
        <td>&nbsp;</td>
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