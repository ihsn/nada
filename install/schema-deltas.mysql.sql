---
--- v 5.0.6
---

-- variables add keywords field for indexing notes and other fields

alter table variables add `keywords` text default NULL;

ALTER TABLE `variables` 
DROP INDEX `idx_nm_lbl_cat_qstn` ,
ADD FULLTEXT INDEX `idx_nm_lbl_cat_qstn` (`name` ASC, `labl` ASC, `catgry` ASC, `qstn` ASC, `keywords` ASC);
