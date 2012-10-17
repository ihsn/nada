<div style="background:white;overflow:auto;">
<h1><?php echo t('collections');?></h1>
<div class="wb-box-main with-bottom-spacing" style="width:100%;">
      <div class="wb-box">
	<?php 
		//get a list of all collections
        $data['rows']=$this->collections_model->select_all();
		
		//display
        $this->load->view("collections/index_public",$data);                    
    ?>
	</div>
</div>
</div>