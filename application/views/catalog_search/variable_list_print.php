<?php if (isset($rows)): ?>
<?php if ($rows): ?>

<?php 
	//current page url
	$page_url=site_url().$this->uri->uri_string();
	
	//total pages
	$pages=ceil($found/$limit);	
?>
        <div class="nada-pagination nada-variable-pagination">
            <div class="row mt-3 d-flex align-items-lg-center">

                <div class="col-12 col-md-12 col-lg-12 text-center text-md-left mb-2 mb-md-0">
                    <small>
                        <?php echo sprintf(t('showing_variables'),
                            (($limit*$current_page)-$limit+1),
                            ($limit*($current_page-1))+ count($rows),
                            $found);?>
                    </small>
                </div>
            </div>
        </div>

        <table class="table table-striped table-hover grid-table">
            <thead>

            <th><?php echo t('name');?></th>
            <th><?php echo t('label');?></th>
            </thead>
            <tbody>
                <?php foreach($rows as $row):?>
                    <tr>
                        <td><?php echo $row['name'];?></td>
                        <td>
                            <h4 ><?php echo ($row['labl']!=='') ? $row['labl'] : $row['name']; ?></h4>
                            <div><?php echo $row['nation']. ' - '.$row['title']; ?></div>
                        </td>
                    </tr>
                <?php endforeach;?>

            </tbody>
        </table>
        <div class="nada-pagination">
            <div class="row mt-3 d-flex align-items-lg-center">

                <div class="col-12 col-md-12 col-lg-12 text-center text-md-left mb-2 mb-md-0">
                    <small>
                        <?php echo sprintf(t('showing_variables'),
                            (($limit*$current_page)-$limit+1),
                            ($limit*($current_page-1))+ count($rows),
                            $found);?>
                    </small>
                </div>
            </div>
        </div>

<?php else: ?>
	<?php echo t('no_records_found');?>
<?php endif; ?>
<?php endif; ?>
