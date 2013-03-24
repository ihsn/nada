<div id="slides" class="slide-show">
<div class="slides_container">

<div> <a href="http://localhost/microdata.worldbank.org/index.php/catalog/pets/about"><img src="files/pets-fp-02.jpeg" alt="Service Delivery Facility Surveys" /></a>
  <h1><a href="http://localhost/microdata.worldbank.org/index.php/catalog/pets/about">Featured Catalog: Service Delivery Facility Surveys</a></h1>
  <p>The service facility survey catalog provides access to data along with accompanying survey documents from facility level surveys conducted by the World Bank. Service delivery surveys are tools to measure the effectiveness of basic services such as education, health, and water and sanitation... <a href="http://localhost/microdata.worldbank.org/index.php/catalog/pets/about">Read More&raquo;</a></p>
</div>
<div> <a href="http://localhost/microdata.worldbank.org/index.php/catalog/datafirst/about"><img src="http://localhost/microdata.worldbank.org//files/df-fp-02.gif"></a>
  <h1><a href="http://localhost/microdata.worldbank.org/index.php/catalog/datafirst/about">Featured Catalog: DataFirst, University of Cape Town, South Africa</a></h1>
  <p>DataFirst is a research support unit at the University of Cape Town, South Africa, which operates a Research Data Centre and provides basic and advanced training in microdata managment and analysis. DataFirst is also an international web portal for South African census and survey data... <a href="http://localhost/microdata.worldbank.org/index.php/catalog/datafirst/about">Read More&raquo;</a></p>
</div>
<div> <a href="http://localhost/microdata.worldbank.org/index.php/catalog/migration_remittances/about"><img src="http://localhost/microdata.worldbank.org//files/mrs-fp-02.gif"></a>
  <h1><a href="http://localhost/microdata.worldbank.org/index.php/catalog/migration_remittances/about">Featured Catalog: Migration and Remittances Surveys</a></h1>
  <p>A repository of surveys conducted to improve our knowledge base on migration and remittances, and to provide rich and detailed information on the impact of migration and remittances at the household level. These datasets aim to increase our ability to maximize the socio-economic impact... <a href="http://localhost/microdata.worldbank.org/index.php/catalog/migration_remittances/about">Read More&raquo;</a></p>
</div>

</div>
</div>

<?php return;?>
<div class="wb-box-main with-bottom-spacing">
  <div class="wb-box">
    <div id="slides" class="slide-show">
      <div class="slides_container">
        <?php if (isset($slides)):?>
        <?php foreach($slides as $slide):?>
        <?php 
						$text=str_replace("[site_url]",site_url(),$slide['text']);
						$text=str_replace("[base_url]",base_url(),$text);
						echo $text;	
					?>
        <?php endforeach;?>
        <?php endif;?>
        <!--
            <?php if (isset($popular_surveys) && is_array($popular_surveys)):?>
            <div>
              <h1>Most popular surveys in the last 30 days</h1>
              <ul class="bl">
              <?php foreach($popular_surveys as $survey): ?>
                <li><a href="<?php echo site_url();?>/catalog/<?php echo $survey['id'];?>"><?php echo $survey['nation'];?> - <?php echo $survey['titl'];?></a></li>
              <?php endforeach;?>
              </ul>
            </div>
            <?php endif;?>
            <?php if (isset($latest_surveys) && is_array($latest_surveys)):?>
            <div>
              <h1>Recent additions</h1>
              <ul class="bl no-action">
              <?php foreach($latest_surveys as $survey): ?>
                <li><a href="<?php echo site_url();?>/catalog/<?php echo $survey['id'];?>"><?php echo $survey['nation'];?> - <?php echo $survey['titl'];?></a></li>
              <?php endforeach;?>
              </ul>
            </div>
            <?php endif;?>
            -->
      </div>
    </div>
  </div>
</div>
