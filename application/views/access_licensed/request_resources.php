<div class="resources-container">
    <div class="survey-resources">
        <!--<div class="survey-info">
            <h3><?php echo $request['surveys'][$sid]['title'];?></h3>
            <h4><?php echo $request['surveys'][$sid]['nation'];?>, <?php echo $request['surveys'][$sid]['year_start'];?></h4>
        </div>-->
    
        <?php $this->load->view('access_licensed/survey_resources_microdata',array('resources_microdata'=>$microdata_resources,'request_id'=>$request['id']));?>
        <br style="margin-top:20px;"/>
        <?php $this->load->view('access_licensed/survey_resources',array('resources'=>$external_resources,'request_id'=>$request['id']));?>
    </div>
</div>