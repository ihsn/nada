<div class="container-fluid">
<div class="pull-right page-links"> 
  <a href="<?php echo site_url('admin/citations'); ?>" class="btn btn-primary">
  <i class="fa fa-home" aria-hidden="true">&nbsp;</i>
    <?php echo t('citation_home');?>
  </a> 
</div>





<h1 class="page-title mt-5"><?php echo t('export_citations');?></h1>

<a href="<?php echo site_url('admin/citations/export/json');?>" class="btn btn-primary"><?php echo t('Download JSON');?></a>
<a href="<?php echo site_url('admin/citations/export/csv');?>" class="btn btn-primary"><?php echo t('Download CSV');?></a>

</div>
</div>