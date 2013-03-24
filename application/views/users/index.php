<div class="body-container" style="padding:10px;">
<?php if (!isset($hide_form)):?>
<div class="page-links">
	<a href="<?php echo site_url();?>/admin/users/add" class="button"><img src="images/icon_plus.gif"/><?php echo t('create_user_account');?></a> 
	<a href="<?php echo site_url();?>/admin/user_groups" class="button"><img src="images/page_white.png"><?php echo t('user_groups');?></a> 
</div>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<h1 class="page-title"><?php echo t('title_user_management');?></h1>
<form class="left-pad" style="margin-bottom:10px;" method="GET" id="user-search">
  <input type="text" size="40" name="keywords" id="keywords" value="<?php echo form_prep($this->input->get('keywords')); ?>"/>
  <select name="field" id="field">
    <option value="all"		<?php echo ($this->input->get('field')=='all') ? 'selected="selected"' : '' ; ?> ><?php echo t('all_fields');?></option>
    <option value="username"	<?php echo ($this->input->get('field')=='username') ? 'selected="selected"' : '' ; ?> ><?php echo t('username');?></option>
    <option value="email"	<?php echo ($this->input->get('field')=='email') ? 'selected="selected"' : '' ; ?> ><?php echo t('email');?></option>
  </select>
  <input type="submit" value="<?php echo t('search');?>" name="search"/>
  <?php if ($this->input->get("keywords")!=''): ?>
    <a href="<?php echo current_url();?>"><?php echo t('reset');?></a>
  <?php endif; ?>
</form>
<?php endif; ?>
<?php if ($rows): ?>
<?php 
	//pagination 
	$page_nums=$this->pagination->create_links();
	$current_page=($this->pagination->cur_page == 0) ? 1 : $this->pagination->cur_page;
	
	//sort
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

    <div class="pagination">
		<em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?>
    </div>
    <table class="grid-table" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">
        	<th><input type="checkbox" value="-1"/></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'username',t('username'),$page_url); ?></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'email',t('email'),$page_url); ?></th>  
            <!--<th><?php echo create_sort_link($sort_by,$sort_order,'group_name',t('group'),$page_url); ?></th>-->
			<th><?php echo create_sort_link($sort_by,$sort_order,'active',t('status'),$page_url); ?></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'country',t('country'),$page_url); ?></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'company',t('company'),$page_url); ?></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'created_on',t('join_date'),$page_url); ?></th>
            <th><?php echo create_sort_link($sort_by,$sort_order,'last_login',t('last_login'),$page_url); ?></th>			
			<th><?php echo t('actions');?></th>
        </tr>
	<?php $tr_class=""; ?>
	<?php foreach($rows as $row): ?>
    	<?php $row=(object)$row;?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="<?php echo $tr_class; ?>">
        	<td><input type="checkbox" value="<?php echo $row->id; ?>"/></td>
            <td><a href="<?php echo current_url().'/edit/'.$row->id; ?>"><?php echo form_prep($row->username); ?></a></td>
            <td><?php echo form_prep($row->email); ?>&nbsp;</td>
       		<!--<td><?php //echo strtoupper($row->group_name); ?></td>-->
			<td><?php echo ((int)$row->active)==1 ? t('ACTIVE') : t('DISABLED'); ?></td>
            <td><?php echo form_prep($row->country); ?></td>
            <td><?php echo form_prep(substr($row->company,0,60)); ?></td>
            <td><?php echo date("m-d-Y",$row->created_on); ?></td>
            <?php if ($row->last_login>$row->created_on): ?>
				<td><?php echo date("m-d-Y",$row->last_login); ?></td>
            <?php else: ?>                    
	            <td>-</td>
            <?php endif; ?>
            <td>
            	<a href="<?php echo current_url();?>/edit/<?php echo $row->id;?>"><?php echo t('edit');?></a> |
            	<a href="<?php echo current_url();?>/delete/<?php echo $row->id;?>"><?php echo t('delete');?></a>
            </td>
        </tr>
    <?php endforeach;?>
    </table>
    <div class="pagination">
		<em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?>
    </div>

<?php else: ?>
<?php echo t('no_records_found');?>
<?php endif; ?>
</div>