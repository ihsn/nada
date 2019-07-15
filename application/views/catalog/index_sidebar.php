<style>
	.close_{
		display:none;
	}
	.iscollapsed .open_{
		display:none;
	}

	.iscollapsed .close_{
		display:inline;
	}
	
</style>

<?php /*
*
* RIGHT SIDE BAR
*
*/?>
<form id="form_filter">
	<input type="hidden" name="ps" value="<?php echo get_form_value('ps',isset($ps) ? $ps : '15') ; ?>" id="ps"/>
	<!--
		<div style="font-size:smaller;text-align:right;">
    	<a href="<?php echo site_url();?>/admin/catalog/?reset=reset"><?php echo t('clear_filter');?></a>
	</div>
	-->
    <div class="box">
		<div class="box-header"><?php echo t('filter');?>
		   <span class="pull-right" title="toggle_box">
		   	<i class="fa fa-chevron-circle-down open_" aria-hidden="true"></i>
			<i class="fa fa-chevron-circle-right close_ " aria-hidden="true"></i>
		   </span>
		</div>
		<div class="box-body">
		<div class="pad10">
		<?php 
			$c=0;
			$search_fields=array('title','idno');
		?>

		<?php foreach($search_fields as $field): $c++?>
			<div class="keyword-filter-item" style="margin-bottom:10px">
			<label for="field-<?php echo $field;?>"><?php echo t($field);?></label><br/>
			<div class="input-group">
				<input class="form-control" type="textbox" id="field-<?php echo $field;?>" name="<?php echo $field;?>" value="<?php echo get_form_value($field,'') ; ?>"/>
				<span class="input-group-btn apply-filter">
				<button class="btn btn-default" type="button">
					<span class="glyphicon glyphicon-search" aria-hidden="true"></span>
				</button>
				</span>
			</div>
			</div>
		<?php endforeach;?>
        
        	<div class="field">
                <label for="field-published"><?php echo t('study_status');?></label><br/>
                <select name="published" id="survey-status">
	                <option value=""  <?php echo my_set_dropdown('published', '');?>><?php echo t('all');?></option>
                	<option value="1" <?php echo my_set_dropdown('published', '1');?>><?php echo t('published');?></option>
                    <option value="0" <?php echo my_set_dropdown('published', '0');?>><?php echo t('unpublished');?></option>                    
                </select>
			</div>

        	<div class="field">
                <label for="field-no-questionnaire">
				<input type="checkbox" id="field-no-questionnaire" name="no_question" value="1"/>
				<?php echo t('study_no_questionnaire');?>
                </label>                
			</div>
        	<div class="field">
                <label for="field-no-datafile">
				<input type="checkbox" id="field-no-datafile" name="no_datafile" value="1"/>
				<?php echo t('study_no_datafile');?>
                </label>                
			</div>


		</div>
		</div>
   </div>


	<div class="box">
		<div class="box-header"><?php echo t('countries');?>
		   <span class="pull-right" title="toggle_box">
		   	<i class="fa fa-chevron-circle-down open_" aria-hidden="true"></i>
			<i class="fa fa-chevron-circle-right close_ " aria-hidden="true"></i>
		   </span>
		</div>
		<div class="box-body">
		<div class="scrollable" >
		<?php $c=0;?>
		<?php foreach($this->catalog_countries as $country): $c++?>
			<div>
			<label for="nation-<?php echo $c;?>">
			<input type="checkbox" id="nation-<?php echo $c;?>" name="nation[]" value="<?php echo $country['country_name'];?>" <?php echo my_set_checkbox('nation', $country['country_name']);?>/>			
			<?php echo $country['country_name'];?> <span class="result-count">(<?php echo $country['total'];?>)</span>
			</label>
			</div>
		<?php endforeach;?>
		</div>
		</div>       
   </div>
	
	<?php if (isset($this->catalog_tags) && count($this->catalog_tags)>0):?>
	<div class="box survey-tags">
		<div class="box-header"><?php echo t('tags');?>
		   <span class="pull-right" title="toggle_box">
		   	<i class="fa fa-chevron-circle-down open_" aria-hidden="true"></i>
			<i class="fa fa-chevron-circle-right close_ " aria-hidden="true"></i>
		   </span>
		</div>
		<div class="survey-tags-body box-body">
			<div class="pad5 scrollable">
			<?php $c=0;?>
			<?php foreach($this->catalog_tags as $tag): $c++?>
				<div>
					<label for="tag-<?php echo $c;?>">
					<input type="checkbox" id="tag-<?php echo $c;?>" name="tag[]" value="<?php echo $tag['tag'];?>" <?php echo my_set_checkbox('tag', $tag['tag']);?>/>
					<?php echo $tag['tag'];?> <span class="result-count">(<?php echo $tag['total'];?>)</span>
					</label>
				</div>
			<?php endforeach;?>
			</div>
		</div>
	</div>
	<?php endif;?>
	
	<!-- data access filter-->
    <div class="box da-filter">
		<div class="box-header"><?php echo t('data_access');?>
		   <span class="shx pull-right" title="toggle_box">
			   <i class="fa fa-chevron-circle-down open_" aria-hidden="true"></i>
			   <i class="fa fa-chevron-circle-right close_ " aria-hidden="true"></i>
			</span>
		</div>
		<div class="filter-da-body box-body">
			<div class="pad5"><?php $this->load->view('catalog/filter_da');?></div>
		</div>    
	</div>

</form>