-- ============================================================
-- NADA 5.6 Search Performance Indexes - MySQL
--
-- Adds indexes required for catalog search and variable search
-- filtering. Safe to re-run — duplicate index errors are skipped
-- by the migration runner.
-- ============================================================


-- surveys
ALTER TABLE `surveys` ADD INDEX `idx_surveys_published` (`published`);
ALTER TABLE `surveys` ADD INDEX `idx_surveys_type` (`type`);
ALTER TABLE `surveys` ADD INDEX `idx_surveys_repositoryid` (`repositoryid`);
ALTER TABLE `surveys` ADD INDEX `idx_surveys_formid` (`formid`);
ALTER TABLE `surveys` ADD INDEX `idx_surveys_data_class_id` (`data_class_id`);
ALTER TABLE `surveys` ADD INDEX `idx_surveys_year_start` (`year_start`);
ALTER TABLE `surveys` ADD INDEX `idx_surveys_total_views` (`total_views`);
ALTER TABLE `surveys` ADD INDEX `idx_surveys_changed` (`changed`);
ALTER TABLE `surveys` ADD INDEX `idx_surveys_created` (`created`);

-- survey_years
ALTER TABLE `survey_years` ADD INDEX `idx_survey_years_year_sid` (`data_coll_year`, `sid`);

-- survey_countries
ALTER TABLE `survey_countries` ADD INDEX `idx_survey_countries_cid` (`cid`, `sid`);

-- survey_repos
ALTER TABLE `survey_repos` ADD INDEX `idx_survey_repos_repositoryid` (`repositoryid`, `sid`);
ALTER TABLE `survey_repos` ADD INDEX `idx_survey_repos_sid` (`sid`);

-- survey_facets
ALTER TABLE `survey_facets` ADD INDEX `idx_survey_facets_term_id` (`term_id`, `sid`);
ALTER TABLE `survey_facets` ADD INDEX `idx_survey_facets_sid` (`sid`);

-- survey_tags
ALTER TABLE `survey_tags` ADD INDEX `idx_survey_tags_tag` (`tag`, `sid`);
