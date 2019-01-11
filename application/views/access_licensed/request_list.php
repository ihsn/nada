<?php if ($lic_requests):?>
    <div class="row">

        <div class="col-sm-12">
            <h2><?php echo t('licensed_survey_requests');?> - [<?php echo count($lic_requests);?>]</h2>
        </div>
    </div>

    <div class="row wb-tab-heading mt-lg-3 mb-5">
        <!-- tab-heading -->

        <div class="col-12 col-sm-12">

            <div class="row">

                <div class="col-12 col-sm-12">
                    <table class="table table-striped table-sm wb-table-space">
                        <tbody>
                        <tr>
                            <th><?php echo t('#ID');?></th>
                            <th><?php echo t('survey_title');?></th>
                            <th><?php echo t('status');?></th>
                            <th><?php echo t('date');?> </th>
                        </tr>
                        <?php foreach($lic_requests as $request) :?>
                            <tr>
                                <td><?php echo $request['id'];?></td>
                                <td><?php echo anchor('access_licensed/track/'.$request['id'],($request['request_title']==NULL) ?  'single study request' : $request['request_title'] );?></td>
                                <td><?php echo t($request['status']);?></td>
                                <td><?php echo date("m-d-Y",$request['created']); ?></td>
                            </tr>
                        <?php endforeach;?>
                        </tbody>
                    </table>
                </div>

            </div> <!-- /row  -->

        </div>
    </div>
<?php endif;?>
