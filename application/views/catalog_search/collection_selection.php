<div class="container collections-container" >
    	<div class="rows-container">
			<?php foreach($repositories as $section):if(!isset($section['children'])){continue;}?>
            <div class="row parent-row" id="sub-collection-row-<?php echo $section['id'];?>">
                <div class="col-1-collection">
                         <input class="chk-sub-collection parent" type="checkbox"                             
                            id="repo-sec-<?php echo $section['id'];?>"
                            data-type="parent"
                         />
						<label for="repo-sec-<?php echo $section['id'];?>">
                            <?php echo $section['title']; ?>
                        </label>
                </div>
                <div class="col-2-collection-items cnt">
                <?php foreach($section['children'] as $collection):?>
                    <div class="collection item" >
                        <label for="repo-<?php echo $section['id']?>-<?php echo form_prep($collection['repositoryid']); ?>">
                            <div class="repo-title">
                                <input class="chk-item" type="checkbox"
                                       value="<?php echo form_prep($collection['repositoryid']); ?>"
                                       id="repo-<?php echo $section['id']?>-<?php echo form_prep($collection['repositoryid']); ?>"
                                       data-type="child"
                                       data-name="repo-<?php echo form_prep($collection['repositoryid']); ?>"
                                    />
                                <?php echo $collection['title']; ?> <span class="count">(<?php echo $collection['surveys_found']; ?>)</span>
                            </div>
                            <div class="repo-short-text"><?php echo $collection['short_text'];?></div>
                        </label>
                    </div>
                <?php endforeach;?>
                </div>
            </div>    
            <?php endforeach;?>
		</div>
    
</div>