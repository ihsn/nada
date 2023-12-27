<?php
$sid=$this->uri->segment(4);
$selected_page=$this->uri->segment(5);
?>



	<div id="survey">

		<table class="table table-striped" cellspacing="0">
        <tr>
            <td nowrap="nowrap" style="width:150px;"><?php echo t('ref_no');?></td>
            <td><?php echo $idno; ?></td>
        </tr>

		<?php if(isset($survey_alias_array) && count($survey_alias_array)>0):?>
        <tr>
            <td><?php echo t('study_aliases');?></td>
            <td>
            	<span class="survey-alias">
				<?php foreach($survey_alias_array as $alias):?>
					<span class="label label-default"><?php echo $alias['alternate_id'];?></span>
                <?php endforeach;?>
                </span>
            </td>
        </tr>
        <?php endif;?>

        <tr>
            <td><?php echo t('year');?></td>
            <td><?php
				$years=array($year_start,$year_end);
				$years=array_unique($years);
				?>
				<?php echo implode(" - ",$years); ?>
            </td>
        </tr>
        <tr>
            <td><?php echo t('country');?></td>
            <td><div class="survey-countries">
				<?php foreach($countries as $country):?>
                	<?php if((int)$country['cid']<1):?>
                        <span class="country label-tag label-tag-error" id="country-<?php echo $country['id'];?>" title="<?php echo t('Fix country code');?>">
                            <a href="<?php echo site_url('admin/countries/mappings');?>"><?php echo $country['country_name'];?></a>
                        </span>
                    <?php else:?>
                    	<span class="label label-tag country" id="country-<?php echo $country['id'];?>">
                            <?php echo $country['country_name'];?>
                        </span>
                    <?php endif;?>
            	<?php endforeach;?>
                </div>
            </td>
        </tr>
        <tr>
            <td><?php echo t('folder');?></td>
            <td><?php echo $dirpath;?></td>
        </tr>
				
        <tr>
            <td><?php echo t('repository');?></td>
            <td>
            	<?php if ($repo):?>
								<?php foreach($repo as $r):?>
                	<span class="label <?php echo ($r['isadmin']==1) ? 'label-primary' : 'label-info'; ?>"><?php echo strtoupper($r['repositoryid']);?></span>
                <?php endforeach;?>
                <?php else:?>
                	N/A
                <?php endif;?>
            </td>
        </tr>
			
        <tr>
            <td><?php echo t('metadata_in_pdf');?></td>
            <td>

                <span class="actions">
                	<?php if ($pdf_documentation['status']=='na' || $pdf_documentation['status']=='outdated' ):?>
                    	<a class="btn btn-primary" href="<?php echo site_url("admin/pdf_generator/setup/$sid");?>"><?php echo t('Generate PDF');?></a>
                    <?php endif;?>
                    <span class="sep">  </span>
                    <?php if($pdf_documentation['status']!=='na'):?>
                    <a class="btn btn-default" href="<?php echo site_url("admin/pdf_generator/delete/$sid");?>"><?php echo t('delete');?></a>
                    <?php endif;?>
                </span>

				<?php if ($pdf_documentation['status']=='na'):?>
	            		<span class="label label-warning"  title="<?php echo t('pdf_not_generated');?>"><i class="glyphicon glyphicon-exclamation-sign"></i> <?php echo t('pdf_not_generated');?></span>
	                <?php else:?>
	                	<?php if ($pdf_documentation['status']=='uptodate'):?>
	                		<span class="label label-success" title="<?php echo t('pdf_uptodate');?>"><i class="glyphicon glyphicon-ok"></i> <?php echo t('pdf_uptodate');?></span>
	                    <?php else:?>
	                    	<span class="label label-warning" title="<?php echo t('pdf_outdated');?>"><i class="glyphicon glyphicon-exclamation-sign"></i> <?php echo t('pdf_outdated');?></span>
	                    <?php endif;?>
	            <?php endif;?>
            </td>
		</tr>
		
		<!-- data classification -->
		<tr>
			<td><?php echo t('data_access');?></td>
			<td>
				<div class="collapsiblex">
					<div class="box-caption">
					</div>

					<div class="box-body collapsex">
						<?php $this->load->view('catalog/data_access',null);?>
					</div>
				</div>
			</td>
		</tr>

        <tr>
            <td><?php echo t('indicator_database');?></td>
            <td>

				<div class="collapsible">
                  <div class="box-caption">
										<span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
                      <?php if ($link_indicator):?>
                          <?php echo $link_indicator;?>
                      <?php else:?>
                          ...
                      <?php endif;?>
                  </div>

                  <div class="box-body study-publish-box collapse">
						
						<?php echo form_open('admin/catalog/update');?>
							<input type="hidden" name="sid" value="<?php echo $id;?>"/>

							<div class="form-group">
							<input type="link_indicator"
									class="form-control"
									placeholder="Indicator URL"
									name="link_indicator"
									type="text"
									id="link_indicator"
									value="<?php echo get_form_value('link_indicator',isset($link_indicator) ? $link_indicator : '') ; ?>"
									/>
							</div>

							<input class="btn btn-default" type="submit" name="submit" id="submit" value="<?php echo t('update'); ?>" />
							<input type="button" value="<?php echo t('cancel');?>" name="cancel" class="cancel-toggle btn btn-link"/>
						<?php echo form_close();?>
				  </div>
				</div>

			</td>
		</tr>

		<?php 
		/*<tr>
			<td><?php echo t('study_abstract');?></td>
			<td>
					</div>

				</div>

            </td>
        </tr>
		*/?>

         <tr>
            <td><?php echo t('study_website');?></td>
            <td>
								<div class="collapsible">
										<div class="box-caption">
											<span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
												<?php if ($link_study):?>
															<?php echo $link_study;?>
												<?php else:?>
															...
												<?php endif;?>
										</div>

										<div class="box-body study-publish-box collapse">
					                
											<?php echo form_open('admin/catalog/update');?>
												<input type="hidden" name="sid" value="<?php echo $id;?>"/>

																	<div class="form-group">
																		<input
																			class="form-control"
																			placeholder="Study URL"
																			name="link_study"
																			type="text"
																			id="link_study"
																			value="<?php echo get_form_value('link_study',isset($link_study) ? $link_study : '') ; ?>"
																			/>
																	</div>

												<input type="submit" name="submit" id="submit" value="<?php echo t('update'); ?>"  class="btn btn-default"/>
												<input type="button" value="<?php echo t('cancel');?>" name="cancel" class="cancel-toggle btn btn-link"/>
											<?php echo form_close();?>
										</div>
									</div>
            </td>
        </tr>

        <tr>
        <td><?php echo t('featured_study');?></td>
        <td>
		        	<div class="featured_survey" title="<?php echo t('click_to_feature');?>">
                    <label for="chk_featured_survey">
											<input id="chk_featured_survey" type="checkbox" class="feature_study" data-repositoryid="<?php echo $repositoryid;?>" data-sid="<?php echo $sid;?>" <?php echo ($is_featured ===TRUE ) ? 'checked="checked"' : '';?>/>
											<?php echo t('mark_as_featured');?>
										</label>
                </div>
        </td>
        </tr>

				 <tr>
					 <td><?php echo t('Tags');?></td>
					 <td>
						    <div class="box-body survey-tags"><?php echo $tags;?></div>
					 </td>
				 </tr>

				 <tr>
					 <td>
						 <?php echo t('study_collections');?>
					 </td>
				 	<td>
				 	<div id="survey-collection-list"><?php echo $collections;?></div>
			 </td>
		 </tr>

		 <tr>
			 <td><?php echo t('study_aliases');?></td>
			 <td>
				 		<?php echo $survey_aliases; ?>
			 </td>
		 </tr>

		 <tr>
            <td><?php echo t('DOI');?></td>
            <td>
				<div class="doi-container">
					<?php echo form_open('admin/catalog/update_doi');?>
						<input type="hidden" name="sid" value="<?php echo $id;?>"/>

						<div class="input-group">
							<input
								class="form-control"
								placeholder="DOI Handle"
								name="doi"
								type="text"
								id="doi"
								value="<?php echo get_form_value('doi',isset($doi) ? $doi : '') ; ?>"
							/>
							<span class="input-group-btn">
								<input type="submit" name="submit" id="submit" value="<?php echo t('update'); ?>"  class="btn btn-primary"/>
							</span>
						</div>

						<p class="small">Click here to <a href="<?php echo site_url('admin/catalog/doi/'.$id);?>">generate a new DOI</a></p>
					<?php echo form_close();?>
				</div>
            </td>
        </tr>

        </table>
		<input name="tmp_id" type="hidden" id="tmp_id" value="<?php echo get_form_value('tmp_id',isset($tmp_id) ? $tmp_id: $this->uri->segment(4)); ?>"/>
		
	</div>
	<!--end survey info block-->
