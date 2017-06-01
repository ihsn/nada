<?php if (!isset($hide_form)):?>
<h1 class="left-pad">External Resources</h1>
<form class="left-pad" style="margin-bottom:10px;" method="GET" id="catalog-search">
  Search
  <input type="text" size="40" name="keywords" id="keywords" value="<?php echo $this->input->get('keywords'); ?>"/>
  <select name="field" id="field">
    <option value="all"		<?php echo ($this->input->get('field')=='all') ? 'selected="selected"' : '' ; ?> >All fields</option>
    <option value="titl"	<?php echo ($this->input->get('field')=='titl') ? 'selected="selected"' : '' ; ?> >Title</option>
    <option value="surveyid">Survey ID</option>
    <option value="authenty">Producer</option>
    <option value="sponsor">Sponsoor</option>
    <option value="repositoryid">Repository</option>
  </select>
  <input type="submit" value="Search" name="search"/>
  <?php if ($this->input->get("keywords")!=''): ?>
    <a href="<?php echo site_url();?>/admin/catalog">Reset</a>
  <?php endif; ?>
</form>
<?php endif; ?>
<?php if ($rows): ?>
<?php		
		$sort_by=$this->input->get("sort_by");
		$sort_order=$this->input->get("sort_order");	
		
		function get_sort_link($sort_by,$sort_order,$field,$label)
		{
				$img_sort_asc='<img src="images/arrow-desc.png" alt="DESC" border="0"/>';
				$img_sort_desc='<img src="images/arrow-asc.png" alt="ASC" border="0"/>';
		
				if ($field==$sort_by){
					//set sort order, if it was ascending, set to desc for the link or vice versa
					if ($sort_order=='' || $sort_order=='asc' ){
						$sort_order_alter='desc';
						$img_sort_order=$img_sort_asc;
					}
					else{
						$sort_order_alter='asc';
						$img_sort_order=$img_sort_desc;
					}
					//column with the asc/desc image
					return '<a href="index.php/admin/catalog/?sort_by='.$field.'&sort_order='.$sort_order_alter.'">'.$label.' '.$img_sort_order.'</a>';
				}
				else{
					//column without the asc/desc image
					return '<a href="index.php/admin/catalog/?sort_by='.$field.'&sort_order=asc">'.$label.'</a>';
				}
		}
?>
<?php 
	//pagination 
	$page_nums=$this->pagination->create_links();
	$current_page=($this->pagination->cur_page == 0) ? 1 : $this->pagination->cur_page;
	
	//sort
	$sort_by=$this->input->get("sort_by");
	$sort_order=$this->input->get("sort_order");
	
	//current page url
	$page_url=site_url().$this->uri->uri_string();
?>

<div id="resources">
	<div>Total records found: <?php echo $this->pagination->get_total_rows(); ?></div>
	
    <table class="grid-table" width="100%" cellspacing="0" cellpadding="0">
    	<tr>
        	<td>ID</td>
            <td><?php echo create_sort_link($sort_by,$sort_order,'title','Title',$page_url); ?></td>
            <td><?php echo create_sort_link($sort_by,$sort_order,'author','Author(s)',$page_url); ?></td>
            <td><?php echo create_sort_link($sort_by,$sort_order,'type','Type',$page_url); ?></td>
			<td>Actions</td>
        </tr>
	<?php $tr_class=""; ?>
	<?php foreach($rows as $row): ?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="<?php echo $tr_class; ?>">			
        	<td><input type="checkbox" value="<?php echo $row['resource_id']; ?>"/></td>
            <td><a href="<?php echo $page_url.'/'.$row['resource_id']; ?>"><?php echo $row['title']; ?></a></td>
            <td><?php echo $row['author']; ?>&nbsp;</td>
            <td><?php echo $row['type']; ?></td>
			<td>Edit | Delete</td>
        </tr>
    <?php endforeach;?>
    </table>
    <div class="pagination">
        <table width="100%" border="0" cellpadding="0" cellspacing="0">
        	<tr valign="top">
            	<td><?php echo 'showing page '.$current_page.' of '.ceil($this->pagination->get_total_rows()/$this->pagination->per_page); ?></td>
                <td align="right"><?php echo $page_nums;?></td>
            </tr>
        </table>
    </div>
</div>
<?php else: ?>
No surveys found
<?php endif; ?>