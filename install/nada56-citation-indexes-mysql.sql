-- ============================================================
-- NADA 5.6 Citation Performance Indexes - MySQL
--
-- Adds indexes required for citation search filtering and
-- batch author/survey-count loading. Safe to run on existing
-- databases — each statement uses IF NOT EXISTS / CREATE INDEX
-- only when the index does not already exist.
-- ============================================================


-- ============================================================
-- citations
-- Filters: published, ctype, pub_year, flag, url_status
-- JOIN to users table: created_by, changed_by
-- ============================================================

ALTER TABLE `citations`
    ADD INDEX IF NOT EXISTS `idx_citations_published`  (`published`),
    ADD INDEX IF NOT EXISTS `idx_citations_ctype`      (`ctype`),
    ADD INDEX IF NOT EXISTS `idx_citations_pub_year`   (`pub_year`),
    ADD INDEX IF NOT EXISTS `idx_citations_flag`       (`flag`),
    ADD INDEX IF NOT EXISTS `idx_citations_url_status` (`url_status`),
    ADD INDEX IF NOT EXISTS `idx_citations_created_by` (`created_by`),
    ADD INDEX IF NOT EXISTS `idx_citations_changed_by` (`changed_by`);


-- ============================================================
-- survey_citations
-- The existing (sid, citationid) unique key cannot efficiently
-- serve citationid-leading lookups used for:
--   - Batch survey count:  WHERE citationid IN (...)
--   - Repository filter:   WHERE citationid IN (subquery)
--   - no_survey_attached:  NOT EXISTS ... WHERE citationid = citations.id
-- ============================================================

ALTER TABLE `survey_citations`
    ADD INDEX IF NOT EXISTS `idx_survey_citations_citationid` (`citationid`);
