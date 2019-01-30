--
-- For MYSQL 5.6 and later. INNODB with Fulltext and geospatial features.
--

--
-- change tables to INNODB
--

ALTER TABLE `api_keys` ENGINE = Innodb;
ALTER TABLE `api_logs` ENGINE = Innodb;
ALTER TABLE `cache` ENGINE = Innodb;
ALTER TABLE `ci_sessions` ENGINE = Innodb;
ALTER TABLE `citation_authors` ENGINE = Innodb;
ALTER TABLE `citations` ENGINE = Innodb;
ALTER TABLE `configurations` ENGINE = Innodb;
ALTER TABLE `countries` ENGINE = Innodb;
ALTER TABLE `country_aliases` ENGINE = Innodb;
ALTER TABLE `da_collection_surveys` ENGINE = Innodb;
ALTER TABLE `da_collections` ENGINE = Innodb;
ALTER TABLE `data_files` ENGINE = Innodb;
ALTER TABLE `data_files_resources` ENGINE = Innodb;
ALTER TABLE `dcformats` ENGINE = Innodb;
ALTER TABLE `dctypes` ENGINE = Innodb;
ALTER TABLE `featured_surveys` ENGINE = Innodb;
ALTER TABLE `forms` ENGINE = Innodb;
ALTER TABLE `group_permissions` ENGINE = Innodb;
ALTER TABLE `group_repo_access` ENGINE = Innodb;
ALTER TABLE `groups` ENGINE = Innodb;
ALTER TABLE `lic_file_downloads` ENGINE = Innodb;
ALTER TABLE `lic_files` ENGINE = Innodb;
ALTER TABLE `lic_files_log` ENGINE = Innodb;
ALTER TABLE `lic_requests` ENGINE = Innodb;
ALTER TABLE `lic_requests_history` ENGINE = Innodb;
ALTER TABLE `login_attempts` ENGINE = Innodb;
ALTER TABLE `menus` ENGINE = Innodb;
ALTER TABLE `meta` ENGINE = Innodb;
ALTER TABLE `permission_urls` ENGINE = Innodb;
ALTER TABLE `permissions` ENGINE = Innodb;
ALTER TABLE `public_requests` ENGINE = Innodb;
ALTER TABLE `region_countries` ENGINE = Innodb;
ALTER TABLE `regions` ENGINE = Innodb;
ALTER TABLE `repo_perms_groups` ENGINE = Innodb;
ALTER TABLE `repo_perms_urls` ENGINE = Innodb;
ALTER TABLE `repositories` ENGINE = Innodb;
ALTER TABLE `repository_sections` ENGINE = Innodb;
ALTER TABLE `resources` ENGINE = Innodb;
ALTER TABLE `site_menu` ENGINE = Innodb;
ALTER TABLE `sitelogs` ENGINE = Innodb;
ALTER TABLE `survey_aliases` ENGINE = Innodb;
ALTER TABLE `survey_citations` ENGINE = Innodb;
ALTER TABLE `survey_countries` ENGINE = Innodb;
ALTER TABLE `survey_lic_requests` ENGINE = Innodb;
ALTER TABLE `survey_locations` ENGINE = Innodb;
ALTER TABLE `survey_notes` ENGINE = Innodb;
ALTER TABLE `survey_relationship_types` ENGINE = Innodb;
ALTER TABLE `survey_relationships` ENGINE = Innodb;
ALTER TABLE `survey_repos` ENGINE = Innodb;
ALTER TABLE `survey_tags` ENGINE = Innodb;
ALTER TABLE `survey_topics` ENGINE = Innodb;
ALTER TABLE `survey_types` ENGINE = Innodb;
ALTER TABLE `survey_years` ENGINE = Innodb;
ALTER TABLE `surveys` ENGINE = Innodb;
ALTER TABLE `tags` ENGINE = Innodb;
ALTER TABLE `terms` ENGINE = Innodb;
ALTER TABLE `url_mappings` ENGINE = Innodb;
ALTER TABLE `user_repo_permissions` ENGINE = Innodb;
ALTER TABLE `users` ENGINE = Innodb;
ALTER TABLE `users_groups` ENGINE = Innodb;
ALTER TABLE `variables` ENGINE = Innodb;
ALTER TABLE `vocabularies` ENGINE = Innodb;



/* 
-- Enable/disable foreign key checks
-- SET FOREIGN_KEY_CHECKS = 0; 
*/


ALTER TABLE da_collection_surveys
   ADD CONSTRAINT `del_da_coll_surveys`
   FOREIGN KEY (`sid`)
   REFERENCES `surveys` (`id`)
   ON DELETE CASCADE
   ON UPDATE CASCADE;
   

ALTER TABLE data_files
   ADD CONSTRAINT `cascade_data_files`
   FOREIGN KEY (`sid`)
   REFERENCES `surveys` (`id`)
   ON DELETE CASCADE
   ON UPDATE CASCADE;
   

ALTER TABLE data_files_resources
   ADD CONSTRAINT `cascade_data_files_resources`
   FOREIGN KEY (`sid`)
   REFERENCES `surveys` (`id`)
   ON DELETE CASCADE
   ON UPDATE CASCADE;  
   
   
ALTER TABLE featured_surveys
   ADD CONSTRAINT `cascade_featured_surveys`
   FOREIGN KEY (`sid`)
   REFERENCES `surveys` (`id`)
   ON DELETE CASCADE
   ON UPDATE CASCADE;
   
   
ALTER TABLE lic_files
   ADD CONSTRAINT `cascade_lic_files`
   FOREIGN KEY (`surveyid`)
   REFERENCES `surveys` (`id`)
   ON DELETE CASCADE
   ON UPDATE CASCADE;

ALTER TABLE public_requests
   ADD CONSTRAINT `cascade_pubilc_requests`
   FOREIGN KEY (`surveyid`)
   REFERENCES `surveys` (`id`)
   ON DELETE CASCADE
   ON UPDATE CASCADE;
   
ALTER TABLE resources
   ADD CONSTRAINT `cascade_resources`
   FOREIGN KEY (`survey_id`)
   REFERENCES `surveys` (`id`)
   ON DELETE CASCADE
   ON UPDATE CASCADE;


ALTER TABLE survey_aliases
   ADD CONSTRAINT `cascade_survey_aliases`
   FOREIGN KEY (`sid`)
   REFERENCES `surveys` (`id`)
   ON DELETE CASCADE
   ON UPDATE CASCADE;   


ALTER TABLE survey_citations
   ADD CONSTRAINT `cascade_survey_citations`
   FOREIGN KEY (`sid`)
   REFERENCES `surveys` (`id`)
   ON DELETE CASCADE
   ON UPDATE CASCADE;
   
   
ALTER TABLE survey_countries
   ADD CONSTRAINT `cascade_survey_countries`
   FOREIGN KEY (`sid`)
   REFERENCES `surveys` (`id`)
   ON DELETE CASCADE
   ON UPDATE CASCADE;
   
   

ALTER TABLE survey_lic_requests
   ADD CONSTRAINT `cascade_survey_lic_requests`
   FOREIGN KEY (`sid`)
   REFERENCES `surveys` (`id`)
   ON DELETE CASCADE
   ON UPDATE CASCADE;
      
ALTER TABLE survey_locations
   ADD CONSTRAINT `cascade_survey_locations`
   FOREIGN KEY (`sid`)
   REFERENCES `surveys` (`id`)
   ON DELETE CASCADE
   ON UPDATE CASCADE;
   
   
ALTER TABLE survey_notes
   ADD CONSTRAINT `cascade_survey_notes`
   FOREIGN KEY (`sid`)
   REFERENCES `surveys` (`id`)
   ON DELETE CASCADE
   ON UPDATE CASCADE;   

   
ALTER TABLE survey_repos
   ADD CONSTRAINT `cascade_survey_repos`
   FOREIGN KEY (`sid`)
   REFERENCES `surveys` (`id`)
   ON DELETE CASCADE
   ON UPDATE CASCADE;


ALTER TABLE survey_tags
   ADD CONSTRAINT `cascade_survey_tags`
   FOREIGN KEY (`sid`)
   REFERENCES `surveys` (`id`)
   ON DELETE CASCADE
   ON UPDATE CASCADE;    
   
   
ALTER TABLE survey_topics
   ADD CONSTRAINT `cascade_survey_topics`
   FOREIGN KEY (`sid`)
   REFERENCES `surveys` (`id`)
   ON DELETE CASCADE
   ON UPDATE CASCADE;

ALTER TABLE survey_years
   ADD CONSTRAINT `cascade_survey_years`
   FOREIGN KEY (`sid`)
   REFERENCES `surveys` (`id`)
   ON DELETE CASCADE
   ON UPDATE CASCADE;       
   
ALTER TABLE variables
   ADD CONSTRAINT `cascade_survey_variables`
   FOREIGN KEY (`sid`)
   REFERENCES `surveys` (`id`)
   ON DELETE CASCADE
   ON UPDATE CASCADE;          



