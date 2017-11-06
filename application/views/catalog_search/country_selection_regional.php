<?php foreach($regions as $region):?>
<?php if ($region_id!=$region['id']){continue;} ?>

  <div id="tabs-region-<?php echo $region['id'];?>">
	<!--star-tab-->
    <div class="container" >

        <div class="index">
			<?php foreach($region['children'] as $sub):?>
            	<?php if (isset($sub['countries']) && count($sub['countries'])==0){continue;}?>
                <span class="active" data-id="region-sub-row-<?php echo $sub['id'];?>"><?php echo $sub['title'];?></span>&nbsp;
            <?php endforeach;?>
        </div>

    	<div class="rows-container">
			<?php foreach($region['children'] as $sub):?>
            <?php if (isset($sub['countries']) && count($sub['countries'])==0){continue;}?>
            <div class="row" id="region-sub-row-<?php echo $sub['id'];?>">
                <div class="col-1">
                         <input class="chk-section parent" type="checkbox"                             
                            id="region-sub-<?php echo $sub['id'];?>"
                            data-type="parent"
                         />
						<label for="region-sub-<?php echo $sub['id'];?>">
                            <?php echo $sub['title']; ?>
                        </label>
                </div>
                <div class="col-2">
                <?php if($sub['countries']):?>
				<?php foreach($sub['countries'] as $country):?>
                    <div class="country item" >
                        <input class="chk-item" type="checkbox" 
                            value="<?php echo form_prep($country['countryid']); ?>" 
                            id="cr-<?php echo $sub['id']?>-<?php echo form_prep($country['countryid']); ?>"
                            data-type="child"
                            data-name="c-<?php echo form_prep($country['countryid']); ?>"
                         />
                        <label for="cr-<?php echo $sub['id']?>-<?php echo form_prep($country['countryid']); ?>">
                            <?php echo $country['name']; ?> <span class="count">(<?php echo $country['total']; ?>)</span>
                        </label>
                    </div>
                <?php endforeach;?>
                <?php endif;?>
                </div>
            </div>    
            <?php endforeach;?>
		</div>
    
	</div>
    <!--end-tab-->
  </div>

<?php endforeach;?>