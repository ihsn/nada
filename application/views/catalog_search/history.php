
<?php
	//set default page size, if none selected
	if(!$this->input->get("ps"))
	{
		$ps=15;
	}
?>
<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>
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

<h3><?php echo t('catalog_history');?></h3>
<table class="table table-striped">
    <thead>
        <tr>
            <?php if ($this->config->item("regional_search")=='yes'):?>
                <th><?php echo create_sort_link($sort_by,$sort_order,'nation',t('country'),$page_url,array('keywords','field','ps')); ?></th>
            <?php endif;?>
            <th nowrap="nowrap"><?php echo create_sort_link($sort_by,$sort_order,'title',t('title'),$page_url,array('keywords','field','ps')); ?></th>
            <th nowrap="nowrap"><?php echo create_sort_link($sort_by,$sort_order,'year_start',t('year'),$page_url,array('keywords','field','ps')); ?></th>
            <th nowrap="nowrap"><?php echo create_sort_link($sort_by,$sort_order,'created',t('created'),$page_url,array('keywords','field','ps')); ?></th>
            <th nowrap="nowrap"><?php echo create_sort_link($sort_by,$sort_order,'changed',t('modified'),$page_url,array('keywords','field','ps')); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach($rows as $row): ?>
    <tr id="s_<?php echo $row['id']; ?>">
        <?php if ($this->config->item("regional_search")=='yes'):?>
            <td><?php echo $row['nation'];?></td>
        <?php endif;?>
        <td><a href="<?php echo site_url();?>/catalog/<?php echo $row['id']; ?>"><?php echo $row['title']; ?></a></td>
        <td><?php echo ($row['year_start']) > 0 ? $row['year_start'] : 'N/A'; ?></td>
        <td><?php echo date($this->config->item('date_format'), $row['created']); ?></td>
        <td><?php echo date($this->config->item('date_format'), $row['changed']); ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
    <div class="nada-pagination">
        <div class="row mt-3 d-flex align-items-lg-center">

            <div class="col-12 col-md-3 col-lg-4 text-center text-md-left mb-2 mb-md-0">
                <small><?php echo $pager; ?></small>
            </div>

            <div class="col-12 col-md-9 col-lg-8 d-flex justify-content-center justify-content-lg-end text-center">
                <nav aria-label="Page navigation example">
                        <?php echo $page_nums;?>
                </nav>
            </div>
        </div>

    </div>

<?php else: ?>
<?php echo t('no_records_found');?>
<?php endif; ?>


