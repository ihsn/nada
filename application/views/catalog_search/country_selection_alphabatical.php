<div id="tabs-country-alphabatical">
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
    <?php if($countries):?>
	<?php foreach($countries as $country):?>
        <?php 
            $letter=strtoupper(substr($country['nation'],0,1));
            $index[$letter][]=$country;
            $letters[$letter]=$letter;
        ?>
    <?php endforeach;?>
    <?php endif;?>

    <div class="index">
    <?php foreach($letters as $key=>$value):?>
    	<?php if($value==''):?>
        <span class="in-active" data-id="country-<?php echo $key;?>"><?php echo $key;?> </span>
        <?php else:?>
        <span class="active" data-id="country-<?php echo $key;?>"><?php echo $key;?> </span>
        <?php endif;?>
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