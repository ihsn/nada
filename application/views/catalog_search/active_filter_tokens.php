<div class="active-filters-container">

<div class="search-count">
<?php if ($surveys['found']==1):?>
	<?php echo sprintf(t('found_study'),$surveys['found'],$surveys['total']);?>
<?php else:?>
	<?php echo sprintf(t('found_studies'),$surveys['found'],$surveys['total']);?>
<?php endif;?>
</div>

<div class="active-filters">
	<?php if (is_array($search_options->country)):?>
		<?php foreach($search_options->country as $country):?>
        	<?php if (array_key_exists($country,$countries)):?>
            <span class="remove-filter country" data-type="country" data-value="<?php echo $country;?>"><?php echo $countries[$country]['nation'];?></span>
            <?php endif;?>
        <?php endforeach;?>
    <?php endif;?>
	<?php if (is_array($search_options->topic)):?>
		<?php foreach($search_options->topic as $topic):?>
        	<?php if (array_key_exists($topic,$topics)):?>
            <span class="remove-filter topic" data-type="topic" data-value="<?php echo $topic;?>"><?php echo substr($topics[$topic]['title'],0,strpos($topics[$topic]['title'],'[',0)); ?></span>
            <?php endif;?>
        <?php endforeach;?>
    <?php endif;?>

	<?php if (is_array($search_options->collection)):?>
		<?php foreach($search_options->collection as $collection):?>
        	<?php if (array_key_exists($collection,$repositories)):?>
            <span class="remove-filter collection" data-type="collection" data-value="<?php echo $collection;?>"><?php echo $repositories[$collection]['title'];?></span>
            <?php endif;?>
        <?php endforeach;?>
    <?php endif;?>

    <?php if (isset($search_options->dtype) && is_array($search_options->dtype)):?>
		<?php foreach($search_options->dtype as $dtype):?>
            <span class="remove-filter dtype" data-type="dtype" data-value="<?php echo $dtype;?>"><?php echo $data_access_types[$dtype];?></span>
        <?php endforeach;?>
    <?php endif;?>
    <?php if ($search_options->from!='' && $search_options->to!=''):?>
		<?php if ( $search_options->from!=$min_year || $search_options->to!=$max_year ):?>
            <span class="remove-filter years" data-type="years" data-value="0">between <?php echo $search_options->from;?>-<?php echo $search_options->to;?></span>
        <?php endif;?>
    <?php endif;?>
    
    <?php if (isset($search_options->sk) && $search_options->sk!=''):?>
    <span class="remove-filter sk" data-type="sk" data-value=""><?php echo $search_options->sk;?></span>
    <?php endif;?>
    
	<?php if (isset($search_options->vk) && $search_options->vk!=''):?>
    <span class="remove-filter vk" data-type="vk" data-value=""><?php echo $search_options->vk;?></span>
    <?php endif;?>
</div>
<div class="filter-action-bar">
	<a href="#save-search" class="save-search">Save this search</a>
    <a href="#share-search" class="share-search">Share this search</a>
    <a href="#print-search" class="print-search">Print list</a>
</div>
</div>