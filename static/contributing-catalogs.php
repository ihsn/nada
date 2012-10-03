<div style="background:white;overflow:auto;">
<h1>Contributing Catalogs</h1>
<p>Our <a href="<?php echo site_url();?>/catalog/central">Central Microdata Catalog</a> operates as a portal for datasets originating from the World Bank and other international, regional and national organizations. These datasets and  the related metadata are provided by various contributing catalogs.</p>
<div class="wb-box-main with-bottom-spacing" style="width:100%;">
      <div class="wb-box">
	<?php 
        $data['rows']=$this->repository_model->get_repositories($published=TRUE, $system=FALSE);//list of repos                    
<<<<<<< HEAD
        $this->load->view("repositories/index_public",$data);                    
=======
        $this->load->view("microdata.worldbank.org/home/index_public",$data);                    
>>>>>>> 0df80238506a3fa904ffbc982da373dfec446f9c
    ?>
	</div>
</div>
</div>