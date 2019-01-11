CREATE TABLE `dd_projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_type` varchar(15) NOT NULL,
  `title` varchar(300) NOT NULL,
  `description` text NOT NULL,
  `shortname` varchar(150) NOT NULL,
  `collaborators` text,
  `access_policy` varchar(300) DEFAULT NULL,
  `library_notes` text,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `cc` text,
  `to_catalog` varchar(100) DEFAULT NULL,
  `is_embargoed` tinyint(4) DEFAULT NULL,
  `embargoed_notes` text,
  `disclosure_risk` text,
  `admin_comments` text,
  `data_folder_path` varchar(300) DEFAULT NULL,
  `submitted_date` int(11) DEFAULT NULL,
  `created` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `changed` int(11) NOT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `administrated_by` int(11) DEFAULT NULL,
  `administer_date` int(11) DEFAULT NULL,
  `metadata` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8




CREATE  TABLE `dd_collaborators` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `pid` INT NOT NULL ,
  `email` VARCHAR(300) NOT NULL ,
  PRIMARY KEY (`id`) );


CREATE TABLE `dd_resources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL,
  `resource_type` varchar(45) DEFAULT NULL,
  `title` varchar(45) DEFAULT NULL,
  `description` varchar(45) DEFAULT NULL,
  `filename` varchar(300) NOT NULL,
  `created` int(11) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;


CREATE TABLE `dd_citations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL,
  `citation` text DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;