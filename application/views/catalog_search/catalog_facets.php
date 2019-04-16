<?php
$repo_ref=$this->uri->segment(2);

//if not CENTRAL or NULL then check if exists in system
//blocks loading page for invalid repository names
if($repo_ref!='central' && $repo_ref)
{
	if (!$result=$this->repository_model->repository_exists($repo_ref))
	{
        //return false;
        $repo_ref=''; //set to empty for invalid repositories
	}
}
?>
<div class="col-12 col-md-4 col-lg-3 mt-3">
    <form name="search_form" id="search_form" method="get" autocomplete = "off">
        <input type="hidden" id="view" name="view" value="<?php echo (isset($this->view) && $this->view=='v') ? 'v': 's'; ?>"/>
        <input type="hidden" id="ps" name="ps" value="<?php echo $this->limit; ?>"/>
        <input type="hidden" id="page" name="page" value="<?php echo intval($current_page); ?>"/>
        <input type="hidden" id="repo" name="repo" value="<?php echo $active_repo; ?>"/>
        <input type="hidden" id="repo_ref" name="repo_ref" value="<?php echo $repo_ref; ?>"/>
        <input type="hidden" id="sid" name="sid" value="<?php echo isset($sid) ? $sid : ''; ?>"/>
        <input type="hidden" id="_r" name="_r" value=""/>

        <div class="refine-list filter-container">


            <!--<div class="filter-container-heading refine-list">
                <h3>Refine List</h3>
            </div>-->
            <?php if((string)$active_repo!='' && $active_repo!='central'):?>
            <div class="sidebar-filter wb-ihsn-sidebar-filter filter-box back-to-catalog">
                <a class="btn-central-catalog back-to-catalog btn btn-primary btn-block" href="<?php echo site_url('catalog/central');?>" 
                    title="<?php echo t('Return to central catalog');?>">
                        <i class="fa fa-chevron-circle-left" aria-hidden="true"></i>
                        <?php echo t('Central Catalog');?>
                </a>
            </div>

            <?php endif;?>

            <?php
            $fac_filters=array();
            ?>

            <!--keywords filter-->
            <?php  $this->load->view("catalog_search/filter_keywords",array('repoid'=>$active_repo)); ?>

            <!--year filter-->
            <?php if ($this->config->item("year_search")=='yes'):?>
                <?php $fac_filters[(int)$this->config->item("year_search_weight")]['year']= $this->load->view("catalog_search/filter_years",array('repoid'=>$active_repo),TRUE); ?>
            <?php endif;?>

            <!-- country filter-->
            <?php if ($this->regional_search=='yes'):?>
                <?php  $fac_filters[(int)$this->config->item("regional_search_weight")]['country']=$this->load->view("catalog_search/filter_countries",array('active_repo',$active_repo),TRUE); ?>
            <?php endif;?>

            <!-- da filter -->
            <?php if ($this->config->item("da_search")=='yes' && is_array($da_types) && count($da_types)>0):?>
                <?php  $fac_filters[(int)$this->config->item("da_search_weight")]['da']=$this->load->view("catalog_search/filter_da",NULL,TRUE); ?>
            <?php endif;?>
            <!-- end da filter -->

            <?php if ($this->collection_search=='yes' && $active_repo==''):?>
                <?php  $fac_filters[(int)$this->config->item("collection_search_weight")]['collection']=$this->load->view("catalog_search/filter_collections",NULL,TRUE); ?>
            <?php endif;?>

            <?php if($this->topic_search==='yes'):?>
                <?php  $fac_filters[(int)$this->config->item("topic_search_weight")]['topic']=$this->load->view("catalog_search/filter_topics",NULL,TRUE); ?>
            <?php endif;?>

            <?php ksort($fac_filters);?>
            <?php foreach($fac_filters as $key=>$filter):?>
                <?php if(is_array($filter)):?>
                    <?php echo implode("",$filter);?>
                <?php else:?>
                    <?php echo $filter;?>
                <?php endif;?>
            <?php endforeach;?>

        </div>
    </form>
</div>
<?php /*?>
<table class="table table-striped catalog-page-title">
<tr valign="baseline">
<td><h2><?php echo $this->page_title;?></h2></td>
<td class="float-right">
<div class="page-links">
	<a id="link_export" title="<?php echo t('link_export_search');?>" href="<?php echo site_url();?>/catalog/export"><img src="images/export.gif" border="0" alt="Export"/></a>
    <a title="<?php echo t('rss_feed');?>" href="<?php echo site_url();?>/catalog/rss" target="_blank"><img src="images/rss_icon.png" border="0" alt="RSS"/></a>
</div>
</td>
</tr>
</table>
<?php */?>


<script type="text/javascript">
//min/max years
var years = {'from': '<?php reset($years);echo current($years); ?>', 'to': '<?php echo end($years); ?>'};
</script>
