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

		if ($this->config->item("maintenance_mode")!==1){
			show_error("MAINTENANCE_MODE_OFF");
		}
    }
  
	function index()
	{
		
	} 
	
	//update language files with new translations
	function update_translations()
	{
		$this->load->library('translator');
		
		//application language folder
		$language_folder=APPPATH.'language/';
		
		//template language 
		$language='base';
		
		//path to the language folder
		$language_path=$language_folder.$language;
		
		//get a list of all translation files for the given language 
		$files=$this->translator->get_language_files_array($language_path);
		
		//will be updated with additons from the BASE language file
		$target_language=$this->config->item("language");
		
		if (!$target_language)
		{
			echo "language $target_language was not found";
			return;
		}
		
		foreach($files as $file)
		{
			$target_filepath=$language_folder.'/'.$target_language.'/'.$file;

			//merge source and target language file to fill in missing translations for the target language
			$data['translations']=$this->translator->merge_language_keys($language_path.'/'.$file,$target_filepath);
			$data['language_name']=$target_language;
			$data['translation_file']=$file;
			
			//target language file content
			$content='<?php '."\n\n".$this->load->view('translator/save',$data,TRUE);
			
			//update the target language file
			$save_result=@file_put_contents($target_filepath,$content);
			
			if($save_result)
			{
				echo "Language file updated: " . $target_filepath.'<br />';
			}
			else
			{
				echo "<div style='color:red;'>Language file update failed: " . $target_filepath.'</div>';
			}
		}		
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
			
		$sql[]="delete from site_menu where module='catalog' and weight=50 and depth=1 and pid=2 and title='-';";
		$sql[]="delete from site_menu where module='catalog' and weight=40 and depth=1 and url='admin/da_collections';";

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
		
		//update translations
		$this->update_translations();
		
		echo "Upgrade completed!";
	}
}//end class