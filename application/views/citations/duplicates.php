<div class="matching-citations">
    <h4>Similar citations</h4>

    <?php foreach($citations as $row):?>
        <div class="citation-row row-<?php echo $row['id'];?>">
            <a href="<?php echo site_url('admin/citations/edit/'.$row['id']);?>" title="Click to edit">
                <div>
                    <span class="title"><?php echo $row['title'];?></span>
                    <span class="subtitle"><?php echo $row['subtitle'];?></span>
                    <span class="pub-year"><?php echo $row['pub_year'];?></span>
                </div>
                
                <div>
                    <span class="authors">
                    <?php $authors=array();foreach($row['authors'] as $author):?>
                        <?php $authors[]=$author['fname']. ' '.$author['lname'];?>
                    <?php endforeach;?>
                    <?php echo implode(", ", $authors);?>
                    </span>                
                </div>
                
                <?php

                $cols=array(
                    'volume',
                    'issue'
                );

                ?>

                <?php foreach($cols as $col):?>
                    <?php if ($row[$col]):?>
                        <span class="<?php echo $col;?>"><?php echo $col;?>: <?php echo $row[$col];?></span>
                    <?php endif;?>
                <?php endforeach;?>

            </a>
        </div>

    <?php endforeach;?>
</div>