<?php

$facet_bg=array(
    '#2364aa',
    '#3da5d9',
    '#73bfb8',
    '#fec601',
    '#ea7317',
    '#99B898',
    '#FECEAB',
    '#FF847C',
    '#E84A5F',
    '#2A363B',

    '#F8B195',
    '#F67280',
    '#C06C84',
    '#6C5B7B',
    '#355C7D',
    '#99B898',
    '#FECEAB',
    '#FF847C',
    '#E84A5F',
);
$bg=0;
?>
   <?php foreach($facets as $facet_key=>$facet):?>
		<?php if(isset($facet['type']) && isset($facet['type'])=='user'):?>
                <?php if (isset($search_options->{$facet_key}) && is_array($search_options->{$facet_key})):?>
                    <?php foreach($search_options->{$facet_key} as $facet_value):?>
                        <?php if (array_key_exists($facet_value,$facets[$facet_key]['values'])):?>
                            <span 
                                class="badge badge-default badge-secondary wb-badge-close remove-filter type" 
                                style="background:<?php echo $facet_bg[$bg];?>" 
                                data-type="<?php echo $facet_key;?>[]" data-value="<?php echo html_escape($facet_value);?>">
                                <?php echo html_escape($facets[$facet_key]['values'][$facet_value]['title']);?><i class="fa fa-close fas fa-times"></i>
                            </span>                        
                        <?php endif;?>                        
                    <?php endforeach;?>
                    <?php $bg++;?>
                <?php endif;?>
		<?php endif;?>        
	<?php endforeach;?>
    
    <?php if (isset($search_options->repo) && $search_options->repo!=''):?>
    <?php if (isset($active_repo) && $active_repo['title']):?>
        <a href="<?php echo site_url('catalog');?>"><span class="badge badge-primary wb-badge-close repo remove-filter-x repo" data-type="repo" data-value=""><?php echo html_escape($active_repo['title']);?><i class="fa fa-close fas fa-times"></i></span></a>
    <?php endif;?>
    <?php endif;?>

    <?php if (is_array($search_options->type)):?>
        <?php foreach($search_options->type as $type):?>
            <?php if ($search_options->tab_type==$type){continue;};?>                
                <span class="badge badge-default badge-secondary wb-badge-close remove-filter type" data-type="type[]" data-value="<?php echo $type;?>"><?php echo $type;?><i class="fas fa-times"></i></span>
        <?php endforeach;?>
    <?php endif;?>

    <?php if (is_array($search_options->tag)):?>
        <?php foreach($search_options->tag as $tag):?>
            <?php //if (array_key_exists($tag,$tags)):?>
                <span class="badge badge-default badge-secondary wb-badge-close remove-filter type" data-type="tag[]" data-value="<?php echo html_escape($tag);?>"><?php echo html_escape($tag);?><i class="fas fa-times"></i></span>
            <?php //endif;?>    
        <?php endforeach;?>
    <?php endif;?>

    <?php if (is_array($search_options->country)):?>
        <?php foreach($search_options->country as $country):?>
            <?php if (is_array($countries) && array_key_exists($country,$countries)):?>
                <span class="badge badge-default wb-badge-close remove-filter country" data-type="country[]" data-value="<?php echo $country;?>"><?php echo $countries[$country];?><i class="fas fa-times"></i></span>
            <?php endif;?>
        <?php endforeach;?>
    <?php endif;?>
    
    <?php if (is_array($search_options->collection)):?>
        <?php foreach($search_options->collection as $collection):?>
            <?php if (array_key_exists($collection,$repositories)):?>
                <span class="badge badge-default wb-badge-close  remove-filter country collection" data-type="collection[]" data-value="<?php echo $collection;?>"><?php echo $repositories[$collection];?><i class="fas fa-times"></i></span>
            <?php endif;?>
        <?php endforeach;?>
    <?php endif;?>

    <?php if (isset($search_options->region) && is_array($search_options->region)):?>
        <?php foreach($search_options->region as $region):?>
            <?php if (array_key_exists($region,$regions)):?>
                <span class="badge badge-default wb-badge-close country remove-filter region" data-type="region[]" data-value="<?php echo $region;?>"><?php echo $regions[$region]['title'];?><i class="fas fa-times"></i></span>
            <?php endif;?>
        <?php endforeach;?>
    <?php endif;?>

    <?php if (isset($search_options->dtype) && is_array($search_options->dtype)):?>
        <?php foreach($search_options->dtype as $dtype):?>
            <?php if (array_key_exists($dtype,$data_access_types)):?>
                <span class="badge badge-default wb-badge-close remove-filter dtype" data-type="dtype[]" data-value="<?php echo $dtype;?>">
                    <?php echo $data_access_types[$dtype]['title'];?><i class="fas fa-times"></i>
                </span>
            <?php endif;?>
        <?php endforeach;?>
    <?php endif;?>

    <?php if (isset($search_options->data_class) && is_array($search_options->data_class)):?>
        <?php foreach($search_options->data_class as $data_class):?> 
            <?php if (array_key_exists($data_class,$data_classifications)):?>
                <span class="badge badge-default badge-secondary wb-badge-close remove-filter data_class" data-type="data_class[]" data-value="<?php echo $data_class;?>">
                    <?php echo $data_classifications[$data_class]['title'];?><i class="fas fa-times"></i>
                </span>
            <?php endif;?>
        <?php endforeach;?>
    <?php endif;?>

    <?php if ((int)$search_options->from>0 && (int)$search_options->to>0):?>
            <span class="badge badge-default wb-badge-close remove-filter years" data-type="years" data-value="0"><?php echo t('between');?> <?php echo $search_options->from;?>-<?php echo $search_options->to;?><i class="fas fa-times"></i></span>
    <?php endif;?>

    <?php if (isset($search_options->sk) && $search_options->sk!=''):?>
        <span class="badge badge-default wb-badge-close remove-filter sk" data-type="sk" data-value=""><?php echo html_escape(substr($search_options->sk,0,50));?><i class="fas fa-times"></i></span>
    <?php endif;?>

    <?php if (isset($search_options->vk) && $search_options->vk!=''):?>
        <span class="badge badge-default wb-badge-close country remove-filter vk" data-type="vk" data-value=""><?php echo html_escape(substr($search_options->vk,0,50));?><i class="fa fa-close fas fa-times"></i></span>
    <?php endif;?>

    <?php if (isset($search_options->sid) && $search_options->sid!=''):?>
        <span class="badge badge-default wb-badge-close country remove-filter sid" data-type="sid" data-value=""><?php echo html_escape(substr($search_options->sid,0,50)).'... ';?><i class="fa fa-close fas fa-times"></i></span>
    <?php endif;?>
