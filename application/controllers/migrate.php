<?php
class Migrate extends MY_Controller {

    function __construct()
    {
        	parent::__construct();
		    $this->load->database();

			//load upgrade configurations
			$this->config->load('migrate');
			$this->load->library('ion_auth');
			$this->load->helper('email');

			session_start();
    }


	function index()
	{
		if (!$this->input->post("upgrade"))
		{
			//loading page first time
			$this->load->view('migrate');
			return;
		}

		//---------------------------------------------------
		// test the upgrade requirements
		//---------------------------------------------------

		//load nada2 database
		$this->db_nada=$this->load->database('nada2',TRUE);

		$conn=$this->test_db();

		if ($conn===FALSE)
		{
			echo 'Failed to connect to NADA 2.0 Database.';
			return;
		}

		//---------------------------------------------------
		// test upgrade config settings
		//---------------------------------------------------
		$nada2_folder_path=$this->config->item("nada2_folder_path");

		if($nada2_folder_path===FALSE)
		{
			show_error('NADA 2.0 installation folder was not set.');
		}

		if($nada2_folder_path=='')
		{
			show_error('NADA 2.0 installation folder was not set.');
		}

		if(!file_exists($nada2_folder_path))
		{
			show_error('NADA 2.0 installation folder was not found.');
		}

		$nada2_repository_id=$this->config->item("nada2_repository_id");

		if($nada2_repository_id===FALSE)
		{
			show_error('NADA 2.0 Repository Identifier not set.');
		}


		//---------------------------------------------------
		// start upgrade
		//---------------------------------------------------
		$this->upgrade_repository();	//upgrade repository info
		$this->upgrade_surveys();		//upgrade surveys
		$this->upgrade_resources();
		$this->upgrade_survey_links();	//survey links - MUST BE RUN before upgrade_resources();
		$this->upgrade_menus();			//upgrade menus
		
		set_time_limit(0);
		$this->upgrade_users();			//upgrade users

		set_time_limit(0);
		$this->upgrade_variables();	//variables

		set_time_limit(0);
		//$this->upgrade_sitelogs();	//site logs

		set_time_limit(0);
		$this->upgrade_public_requests();//public requests

		set_time_limit(0);
		$this->upgrade_lic_requests(); //licensed requests

		set_time_limit(0);
		$this->refresh_from_ddi(); //refresh survey collection dates, topics, etc from the ddi

		echo '<HR><a href="'.site_url().'">Click here to return to the website</a>';
	}



	/**
	*
	* Test database connection
	*
	**/
	function test_db()
	{
		$prefix=$this->db_nada->dbprefix;
		$sql=sprintf('select id from %smenu', $prefix);
		$query=$this->db_nada->query($sql);
		return $query->conn_id;
	}


	/**
	* Main script to run the upgrade
	*
	**/
	function _remap()
	{
		$method=$this->uri->segment(2);

		switch($method)
		{
			case 'refresh_from_ddi':
				//$this->refresh_from_ddi();
			break;

			case false:
				$this->index();
				break;

			default:
				show_404();
		}

	}



	function upgrade_surveys()
	{
		//get data from nada2
		$prefix=$this->db_nada->dbprefix;
		
		//remove deleted surveys
		//$sql_del=sprintf('delete from %ssurvey where isdeleted=1',$prefix);
		//$query=$this->db_nada->query($sql_del);
		
		$sql=sprintf('select s.*,f.model from %ssurvey s left join %sforms f on s.orderform=f.formid where s.isdeleted!=1', $prefix, $prefix);
		$query=$this->db_nada->query($sql);

		if (!$query)
		{
			show_error('Failed to upgrade survey table');
		}

		$rows=$query->result_array();

		//empty the target table
		$empty_sql=sprintf('truncate %ssurveys',$this->db->dbprefix);
		$this->db->query($empty_sql);

		$k=0;
		foreach($rows as $row)
		{
			//build data for import
			$data=array(
					'id'			=>$row['id'],
					'repositoryid'	=>$row['repositoryid'],
					'surveyid'		=>$row['surveyid'],
					'titl'			=>$row['titl'],
					'titlstmt'		=>$row['titlstmt'],
					'authenty'		=>$row['authenty'],
					'geogcover'		=>$row['geogcover'],
					'nation'		=>$row['nation'],
					'topic'			=>$row['topic'],
					'scope'			=>$row['scope'],
					'sername'		=>$row['sername'],
					'producer'		=>$row['producer'],
					'sponsor'		=>$row['sponsor'],
					'refno'			=>$row['refno'],
					'proddate'		=>$row['proddate'],
					'varcount'		=>$row['varcount'],
					'ddifilename'	=>$row['ddifilename'],
					'dirpath'		=>$row['dirpath'],
					'link_study'	=>$row['surveyweb'],
					'formid'		=>$this->_get_nada3_formid($row['model']),
					'isshared'		=>$row['isshared'],
					'isdeleted'		=>$row['isdeleted'],
					'created'		=>$row['datecreated'],
					'changed'		=>$row['lastmodified'],
					'link_indicator'=>$row['devinfo']
				);

			//insert into nada3.surveys
			$result=$this->db->insert('surveys',$data);

			if (!$result)
			{
				echo $this->db->_error_message().'<HR>';
				echo $this->db->last_query().'<BR>';
				return;
			}

			$k++;
		}
		echo '<BR>';
		echo $k.' - survey were copied<br>';
	}

	/**
	*
	* copy survey links (reports, indicators) as external resources
	*
	**/
	function upgrade_survey_links()
	{
		//get data from nada2
		$prefix=$this->db_nada->dbprefix;
		$sql=sprintf('select s.id,overview_pdf as report, surveypackage as zip_package, questionnaire from %ssurvey s where isdeleted!=1', $prefix);

		$query=$this->db_nada->query($sql);

		if (!$query)
		{
			show_error('Failed to upgrade survey table');
		}

		$rows=$query->result_array();

		//delete existing resources
		//$empty_sql=sprintf('truncate %sresources',$this->db->dbprefix);
		//$this->db->query($empty_sql);

		$k=0;
		foreach($rows as $row)
		{
			//build data for import

			$data=array();
			//report
			if ($row['report']!='')
			{
				$data[]=array(
						'survey_id'		=>$row['id'],
						'dctype'		=>0,
						'dcformat'		=>0,
						'title'			=>'Reports and analytical outputs',
						'filename'		=>$row['report'],
						'changed'		=>date("U"),
					);
			}
			//zip
			if ($row['zip_package']!='')
			{
				$data[]=array(
						'survey_id'		=>$row['id'],
						'title'			=>'Technical documentation',
						'filename'		=>$row['zip_package'],
						'changed'		=>date("U"),
						'dctype'		=>0,
						'dcformat'		=>0,
					);
			}
			//questionnaire
			if ($row['questionnaire']!='')
			{
				$data[]=array(
						'survey_id'		=>$row['id'],
						'title'			=>'Questionnaire',
						'filename'		=>$row['questionnaire'],
						'changed'		=>date("U"),
						'dctype'		=>0,
						'dcformat'		=>0,
					);
			}

			foreach($data as $d)
			{
				//insert into nada3.resources
				$result=$this->db->insert('resources',$d);

				if (!$result)
				{
					echo $this->db->_error_message().'<HR>';
					echo $this->db->last_query().'<BR>';
					echo '<HR>';
					return;
				}

				$k++;
			}
		}
		echo '<BR>';
		echo $k.' - resource-links were copied<br>';
	}


	function upgrade_variables()
	{
		//get data from nada2
		$prefix=$this->db_nada->dbprefix;
		$sql=sprintf('select uid,varid,name,labl,qstn,catgry,surveyid_fk from %svariable', $prefix);

		$query=$this->db_nada->query($sql);

		if (!$query)
		{
			show_error('Failed to upgrade variable table');
		}

		$rows=$query->result_array();

		//empty the target table
		$empty_sql=sprintf('truncate %svariables',$this->db->dbprefix);
		$this->db->query($empty_sql);

		$k=0;
		foreach($rows as $row)
		{
			//build data for import
			$data=array(
					'uid'			=>$row['uid'],
					'varid'			=>$row['varid'],
					'name'			=>$row['name'],
					'labl'			=>$row['labl'],
					'qstn'			=>$row['qstn'],
					'catgry'		=>$row['catgry'],
					'surveyid_fk'	=>$row['surveyid_fk'],
				);

			//insert into nada3.variables
			$result=$this->db->insert('variables',$data);

			if (!$result)
			{
				echo $this->db->_error_message().'<HR>';
				echo $this->db->last_query().'<BR>';
				echo '<HR>';
				return;
			}

			$k++;
			set_time_limit(0);
		}
		echo '<BR>';
		echo $k.' - variables were copied<br>';
	}

	function upgrade_sitelogs()
	{
		//get data from nada2
		$prefix=$this->db_nada->dbprefix;
		$sql=sprintf('select id,sessionid,logtime,ip,url,logtype,surveyid,section,keyword,username from %ssitelogs', $prefix);

		$query=$this->db_nada->query($sql);

		if (!$query)
		{
			echo 'Failed to upgrade sitelogs table<BR>';
			echo 'DB ERROR:'. $this->db_nada->_error_message();
			echo '<BR>';
			return;
		}

		$rows=$query->result_array();

		//empty the target table
		$empty_sql=sprintf('truncate %ssitelogs',$this->db->dbprefix);
		$this->db->query($empty_sql);

		$k=0;
		foreach($rows as $row)
		{
			//build data for import
			$data=array(
					'id'			=>$row['id'],
					'sessionid'		=>$row['sessionid'],
					'logtime'		=>$row['logtime'],
					'ip'			=>$row['ip'],
					'url'			=>$row['url'],
					'logtype'		=>$row['logtype'],
					'surveyid'		=>$row['surveyid'],
					'section'		=>$row['section'],
					'keyword'		=>$row['keyword'],
					'username'		=>$row['username'],
				);

			//insert into nada3.variables
			$result=$this->db->insert('sitelogs',$data);

			if (!$result)
			{
				echo $this->db->_error_message().'<HR>';
				echo $this->db->last_query().'<BR>';
				echo '<HR>';
				return;
			}

			$k++;
			set_time_limit(0);
		}
		echo '<BR>';
		echo $k.' - sitelogs were copied<br>';
	}

	function upgrade_public_requests()
	{
		//get data from nada2
		$prefix=$this->db_nada->dbprefix;
		$sql=sprintf('select p.id,p.userid,surveyid,abstract,postdate,u.email from %spublic_requests p
						inner join %susers u on p.userid=u.id', $prefix,$prefix);

		$query=$this->db_nada->query($sql);

		if (!$query)
		{
			echo ('Failed to upgrade public_requests table');
			return;
		}

		$rows=$query->result_array();

		//empty the target table
		$empty_sql=sprintf('truncate %spublic_requests',$this->db->dbprefix);
		$this->db->query($empty_sql);

		$k=0;
		foreach($rows as $row)
		{
			//get user by email
			$user=$this->ion_auth->get_user_by_email($row['email']);
			$userid=0;

			if ($user)
			{
				$userid=$user->id;
			}

			//build data for import
			$data=array(
					'id'			=>$row['id'],
					'userid'		=>$userid,
					'surveyid'		=>$row['surveyid'],
					'abstract'		=>$row['abstract'],
					'posted'		=>$row['postdate'],
				);

			//insert into nada3.variables
			$result=$this->db->insert('public_requests',$data);

			if (!$result)
			{
				echo $this->db->_error_message().'<HR>';
				echo $this->db->last_query().'<BR>';
				echo '<HR>';
				return;
			}

			$k++;
			set_time_limit(0);
		}
		echo '<BR>';
		echo $k.' - public_requests were copied<br>';
	}


	function upgrade_lic_requests()
	{
		//get data from nada2
		$prefix=$this->db_nada->dbprefix;
		$fields='requestid,fname,lname,email,title,org,country,surveyid,org_rec,orgtype,paddress,
				tel,fax, datause,outputs,compdate,datamatching,mergedatasets,team, access_whole,access_subset,date_created,
				status, date_updated,request_ref';

		$sql=sprintf('select %s from %slic_requests',$fields, $prefix);

		$query=$this->db_nada->query($sql);

		if (!$query)
		{
			show_error('Failed to upgrade lic_requests table');
		}

		$rows=$query->result_array();

		//empty the target table
		$empty_sql=sprintf('truncate %slic_requests',$this->db->dbprefix);
		$this->db->query($empty_sql);

		$k=0;
		foreach($rows as $row)
		{
			//user data
			$data= array(
					'email'		=>$row['email'],
					'fname'		=>$row['fname'],
					'lname'		=>$row['lname'],
					'title'		=>$row['title'],
					'org'		=>$row['org'],
					'country'	=>$row['country'],
					'tel'		=>$row['tel'],
			);

			//get the user id or create a new one
			$user_id=$this->_create_lic_user($data);

			//skip importing row if not a valid user
			if (!$user_id)
			{
				echo 'Licensed request['.$row['requestid'].'] from ['.$data['email']. '] was marked as SPAM and was not imported.<BR>';
				//break;
			}

			//build data for import
			$lic_data=array(
					'id'			=>$row['requestid'],
					'userid'		=>$user_id,
					'surveyid'		=>$row['surveyid'],
					'org_rec'		=>$row['org_rec'],
					'org_type'		=>$row['orgtype'],
					'address'		=>$row['paddress'],
					'tel'			=>$row['tel'],
					'fax'			=>$row['fax'],
					'datause'		=>$row['datause'],
					'outputs'		=>$row['outputs'],
					'compdate'		=>$row['compdate'],
					'datamatching'	=>$row['datamatching'],
					'mergedatasets'	=>$row['mergedatasets'],
					'team'			=>$row['team'],
					'dataset_access'=>$row['access_whole'],
					'created'		=>$row['date_created'],
					'status'		=>$row['status'],
					'comments'		=>'',
					'locked'		=>1,
					'updated'		=>$row['date_updated'],
					'updatedby'		=>'',
					'ip_limit'		=>'',
				);

			if ($user_id)
			{
				//insert into nada3.variables
				$result=$this->db->insert('lic_requests',$lic_data);

				if (!$result)
				{
					echo $this->db->_error_message().'<HR>';
					echo $this->db->last_query().'<BR>';
					echo '<HR>';
					return;
				}

				$k++;
			}
			set_time_limit(0);
		}
		echo '<BR>';
		echo $k.' - lic_requests were copied<br>';
	}


	/**
	*
	* Create a new user or get existing user id
	* for licensed requests
	*
	**/
	function _create_lic_user($data=NULL)
	{
		if (!is_array($data))
		{
			return FALSE;
		}

		//validate email, name
		if ( $data['email'] =='' || $data['fname'] =='' || $data['lname'] ==''  )
		{
			return FALSE;
		}

		//validate email format
		if (!valid_email($data['email']))
		{
			return FALSE;
		}

		//check if the user [email] already exists
		$user=$this->ion_auth->get_user_by_email($data['email']);

		if ($user)
		{
			return $user->id;
		}

		$additional_data = array('first_name' => $data['fname'],
       							 'last_name'  => $data['lname'],
       							 'company'    => $data['org'],
       							 'phone'      => substr($data['tel'],0,20),
								 'country'    => $data['country'],
       							);
		//create new user
		$result=$this->ion_auth_model->register(
						$username=$data['fname']. ' ' . $data['lname'],
						$password=substr(md5($data['email']),0,8),
						$email=$data['email'],
						$additional_data = false,
						$group_name = 'members');

		if (!$result)
		{
			echo 'Failed to create user - '. $data['email'].'<BR>';
			return FALSE;
		}
		else
		{
			echo '<BR>user created - '. $data['email'].'<BR>';
		}

		//get the user id by email
		$user=$this->ion_auth->get_user_by_email($data['email']);

		if ($user)
		{
			return $user->id;
		}

		return FALSE;
	}



	function upgrade_resources()
	{
		$prefix=$this->db_nada->dbprefix;
		$sql=sprintf('select r.* from %sresources r', $prefix);

		//get data from nada2
		$query=$this->db_nada->query($sql);

		if (!$query)
		{
			show_error('Failed to get data from nada2 database');
		}

		$rows=$query->result_array();

		//empty the target table
		$empty_sql=sprintf('truncate %sresources',$this->db->dbprefix);
		$result=$this->db->query($empty_sql);


		$k=0;
		foreach($rows as $row)
		{
			//build data for import
			$data=array(
					'resource_id'	=>$row['resource_id'],
					'survey_id'		=>$row['survey_id'],
					'dctype'		=>$row['type'],
					'title'			=>$row['title'],
					'subtitle'		=>$row['subtitle'],
					'author'		=>$row['author'],
					'dcdate'		=>$row['dcdate'],
					'country'		=>$row['country'],
					'language'		=>$row['language'],
					'id_number'		=>$row['id_number'],
					'contributor'	=>$row['contributor'],
					'publisher'		=>$row['publisher'],
					'rights'		=>$row['rights'],
					'description'	=>$row['description'],
					'abstract'		=>$row['abstract'],
					'toc'			=>$row['toc'],
					'subjects'		=>$row['subjects'],
					'filename'		=>$row['filename'],
					'dcformat'		=>$row['format'],
					'changed'		=>date("U")
				);

			//insert into nada3.surveys
			$result=$this->db->insert('resources',$data);

			if (!$result)
			{
				echo $this->db->_error_message().'<HR>';
				echo $this->db->last_query().'<BR>';
			}

			$k++;
		}

		echo $k. ' - resources were copied<br>';
	}



	function upgrade_repository()
	{
		//nada 2 repository id
		$nada2_repository_id=$this->config->item("nada2_repository_id");

		if($nada2_repository_id===FALSE)
		{
			show_error('NADA 2.0 Repository Identifier not set.');
		}

		$prefix=$this->db->dbprefix;

		$data=array('value'=>$nada2_repository_id);

		$this->db->where('name','repository_identifier');
		$query=$this->db->update('configurations',$data);

		if (!$query)
		{
			show_error('Failed to update repository identifier');
		}

		echo 'Repository Identifier updated!';
	}



	function upgrade_menus()
	{
		//folder where nada2 is installed
		$nada2_folder_path=$this->config->item("nada2_folder_path");

		if($nada2_folder_path===FALSE)
		{
			show_error('NADA 2.0 installation folder was not set.');
		}

		if($nada2_folder_path=='' || !file_exists($nada2_folder_path))
		{
			show_error('NADA 2.0 installation folder was not not.');
		}

		$this->load->helper('url');
		$prefix=$this->db_nada->dbprefix;
		$sql=sprintf('select * from %smenu', $prefix);

		//get data from nada2
		$query=$this->db_nada->query($sql);

		if (!$query)
		{
			show_error('Failed to get data from nada2 database');
		}

		$rows=$query->result_array();

		$k=0;
		foreach($rows as $row)
		{
			//build data for import
			$data=array(
					'title'			=>$row['text'],
					'url'			=>url_title($row['text']),
					'linktype'		=>($row['linktype']=='external' ? 1: 0),
					'target'		=>($row['target']=='same' ? 0: 1),
					'published'		=>($row['published']=='yes' ? 1: 0),
					'weight'		=>$row['pos'],
					'pid'			=>0,
					'body'			=>'<h1>'.$row['text'].'</h1>'.@file_get_contents($nada2_folder_path.'pages/'.$row['page']),
					'changed'		=>date("U")
				);

			if ($data['url']!='catalog')
			{
				$del_where=array('url'=>$data['url']);
				$this->db->delete('menus',$del_where);

				//insert into nada3.surveys
				$result=$this->db->insert('menus',$data);
			}

			if (!$result)
			{
				echo $this->db->_error_message().'<HR>';
				echo $this->db->last_query().'<BR>';
			}
			$k++;
		}

		echo $k.' - menu rows were copied<br>';
	}




	function upgrade_users()
	{
		$prefix=$this->db_nada->dbprefix;
		$sql=sprintf('select * from %susers u left join %suser_rights r on u.id=r.userid', $prefix,$prefix);

		//get data from nada2
		$query=$this->db_nada->query($sql);

		if (!$query)
		{
			show_error('Failed to get data from nada2 database');
		}

		$rows=$query->result_array();

		$k=0;
		foreach($rows as $row)
		{
			if (trim($row['status'])=='')
			{
				$row['status']='ACTIVE';
			}

			//build data for import
			$user=array(
					'email'			=>$row['email'],
					'created_on'	=>$row['datecreated'],
					'username'		=>$row['username'],
					'password'		=>$row['password'],
					//'country'		=>$row['account_expiry'],
					'active'		=>($row['status']=='ACTIVE' ? 1 : 0),
					'last_login'	=>$row['datecreated'],
					'group_id'		=>($row['roleid']==1 ? 1 :2),
					'ip_address'	=>'',
					);

			$del_where=array('email'=>$user['email']);
			$this->db->delete('users',$del_where);

			//insert into nada3.surveys
			$result=$this->db->insert('users',$user);
			$user_id=$this->db->insert_id();

			if (!$result)
			{
				echo $this->db->_error_message().'<HR>';
				echo $this->db->last_query().'<BR>';
				return;
			}

			//insert user meta info
			$meta=array(
					'first_name'	=>$row['fname'],
					'last_name'		=>$row['lname'],
					'company'		=>$row['organization'],
					'country'		=>$row['country'],
					'phone'			=>substr($row['telephone'],0,20),
					'user_id'		=>$user_id,
				);

			$del_where=array('user_id'=>$meta['user_id']);
			$this->db->delete('meta',$del_where);

			//insert into nada3.surveys
			$result=$this->db->insert('meta',$meta);

			if (!$result)
			{
				echo $this->db->_error_message().'<HR>';
				echo $this->db->last_query().'<BR>';
				return;
			}

			$k++;
		}

		echo $k.' - users were copied<br>';
	}


	function _get_nada3_formid($model)
	{
			//get data from nada2
		$sql=sprintf('select * from %s where model=%s', $this->db->dbprefix('forms'), $this->db->escape($model));

		$query=$this->db->query($sql);

		if (!$query)
		{
			return 0;
		}

		$row=$query->row_array();

		if ($row)
		{
			return $row['formid'];
		}
	}


	/**
	*
	* Refresh DDI Information in the database
	*
	* Note: Useful for updating study information in the database for existing DDIs
	**/
	function refresh_from_ddi()
	{
		$prefix=$this->db_nada->dbprefix;
		$sql=sprintf('select id from %ssurveys',$this->db->dbprefix);

		//get data from nada3
		$query=$this->db->query($sql);

		if (!$query)
		{
			show_error('Failed to get data from nada3.0 database');
		}

		$rows=$query->result_array();

		echo '<hr>Refreshing study description from DDI<br>';

		foreach($rows as $row)
		{
			set_time_limit(0);
			$this->_refresh_study($row['id']);
			echo '<HR>';
		}
	}

	function _refresh_study($id=NULL)
	{
		if (!is_numeric($id))
		{
			return FALSE;
		}

		//load DDI Parser Library
		$this->load->library('DDI_Parser');
		$this->load->library('DDI_Import','','DDI_Import');
		$this->load->model('Catalog_model');

		//get survey ddi file path by id
		$ddi_file=$this->Catalog_model->get_survey_ddi_path($id);

		if (!file_exists($ddi_file))
		{
			echo ('DDI_NOT_FOUND - '. $ddi_file.'<BR>');
			return FALSE;
		}

		//load DDI Parser Library
		$this->load->library('DDI_Parser');
		$this->load->library('DDI_Import','','DDI_Import');

		//set file for parsing
		$this->ddi_parser->ddi_file=$ddi_file;

		//only available for xml_reader
		$this->ddi_parser->use_xml_reader=TRUE;

		//validate DDI file
		if ($this->ddi_parser->validate()===false)
		{
			$error= 'Invalid DDI file: '.$ddi_file;
			echo $error.'<br>';
			return FALSE;
		}

		//parse ddi study to array
		$data['study']=$this->ddi_parser->get_study_array();

		//pass study data
		$this->DDI_Import->ddi_array=$data;

		//import to study data to db
		$result=$this->DDI_Import->import_study();

		//import failed
		if ($result===FALSE)
		{
			echo 'FAILED - study description - <em>'. $data['study']['id']. '</em><BR>';
		}

		//display import success
		echo 'Updated - study description - <em>'. $data['study']['id']. '</em><BR>';
	}


}//end class