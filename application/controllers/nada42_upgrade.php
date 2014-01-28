<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
*
* Upgrade nada41 to 42
*
**/
class Nada42_upgrade extends CI_Controller {
 
    function __construct() 
    {
        parent::__construct($skip_auth=TRUE);
		$this->load->database();
    }
  
	function index()
	{
		
	} 
	
	
	function run()
	{
	
		$sql=array();
		$sql[]="
				CREATE TABLE `survey_lic_requests` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `request_id` int(11) NOT NULL,
				  `sid` int(11) NOT NULL,
				  PRIMARY KEY (`id`),
				  KEY `uq_survey_requests` (`request_id`,`sid`)
				) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
			";
		$sql[]="	
				ALTER TABLE `lic_requests` COLLATE = utf8_general_ci , 
				ADD COLUMN `request_title` varchar(300) DEFAULT NULL;
			";

		$sql[]="
				-- update existing licensed requests
				update lic_requests lr
					join surveys on lr.surveyid=surveys.id
					set request_title =surveys.titl;
				";	
		$sql[]="
				insert into survey_lic_requests (request_id,sid)
				select id, surveyid from lic_requests;
				";

		$sql[]="		
				-- 
				-- Alter table structure for table 'sitelogs'
				-- 
				ALTER TABLE `sitelogs` COLLATE = utf8_general_ci , 
				ADD COLUMN `useragent` varchar(300) DEFAULT NULL;
				";

		$sql[]="		
				--
				-- Table structure for table `featured_surveys`
				--

				CREATE TABLE `featured_surveys` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `repoid` int(11) DEFAULT NULL,
				  `sid` int(11) DEFAULT NULL,
				  `weight` int(11) DEFAULT '0',
				  PRIMARY KEY (`id`),
				  UNIQUE KEY `survey_repo` (`repoid`,`sid`)
				) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
			";

		$sql[]="	
				insert into site_menu(pid,title,url,weight,depth,module)
				values (2,'-', '-',50,1,'catalog');
				";
				
		$sql[]="		
				insert into site_menu(pid,title,url,weight,depth,module)
				values (2,'Bulk access collections', 'admin/da_collections',40,1,'catalog');
			";
	
		foreach($sql as $q)
		{
			$result=$this->db->query($q);
			
			if (!$result)
			{
				echo '<div style="color:red;border:1px solid red; margin:5px;">';
				echo "Query Failed:".$this->db->last_query();
				echo '</div>';
			}
			else
			{
				echo '<div style="color:green;border-bottom:1px solid red;margin-bottom:15px;">';
				echo "Query executed:".$this->db->last_query();
				echo '</div>';
			}
		}
		
		echo "Upgrade completed!";
	}
}//end class