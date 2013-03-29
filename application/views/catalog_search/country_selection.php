<?php 
//fix for UI tabs
$referer=$this->input->get("referer");
if (strpos($referer,site_url())===false)
{
	$referer=site_url('catalog');
}
?>

<script>
$(document).ready(function()  {
	$( "#tabs" ).tabs();
});
</script>
<div id="tabs">
  <ul>
    <li class="first"><a href="<?php echo $referer;?>#tabs-1">In Alphabatic order</a></li>
    <?php foreach($regions as $region):?>
    	<li><a href="<?php echo $referer;?>#tabs-region-<?php echo $region['id'];?>"><?php echo $region['title'];?></a></li>
  	<?php endforeach;?>  
  </ul>
  <div id="tabs-1">
    <!--tab-alphabatic-country-list-->
    <div class="container" >
	<?php 
        $index=array();
        $letters=array();
		foreach (range('a', 'z') as $alphabet)
		{
			$letters[strtoupper($alphabet)]='';
		}
    ?>
    <?php foreach($countries as $country):?>
        <?php 
            $letter=strtoupper(substr($country['nation'],0,1));
            $index[$letter][]=$country;
            $letters[$letter]=$letter;
        ?>
    <?php endforeach;?>
    
    <div class="index">
    <?php foreach($letters as $i):?>
        <span data-id="country-<?php echo $i;?>"><?php echo $i;?> </span>
    <?php endforeach;?>
    </div>
    
    <div class="rows-container">
    <?php foreach($index as $letter=>$countries): ?>
        <div class="row" id="country-<?php echo $letter;?>">
            <div class="col-1-s letter letter-caption" ><?php echo $letter;?></div>
            <div class="col-2-s cnt">
            <?php foreach($countries as $country): ?>
                    <div class="country item" >
                        <input class="chk-item" type="checkbox" 
                            value="<?php echo form_prep($country['cid']); ?>" 
                            id="ca-<?php echo form_prep($country['cid']); ?>"
                            data-type="child"
                            data-name="c-<?php echo form_prep($country['cid']); ?>"
                         />
                        <label for="ca-<?php echo form_prep($country['cid']); ?>">
                            <?php echo $country['nation']; ?> <span class="count">(<?php echo $country['surveys_found']; ?>)</span>
                        </label>
                    </div>
            <?php endforeach;?>
            </div>    
        </div>
    <?php endforeach;?>
    </div>
    </div>
    <!--end-tab-alphabatic-country-list-->
  </div>

    <?php $this->load->view('catalog_search/country_selection_regions');?>
</div>

