-- nada 5.2.1 db changes for mysql

ALTER TABLE `data_files` ADD `metadata` varchar(5000) DEFAULT NULL;
ALTER TABLE `surveys` ADD `subtitle` varchar(255) DEFAULT NULL;

