<?php
class Datadeposit extends MX_Controller {

	private $_study_grid_ids      = array();

	private $_uploaded_files       = false;

	public function __construct() {
		parent::__construct();
		$this->load->model('Projects_model');
		$this->load->model('Study_model');
		$this->lang->load("dashboard");
		$this->lang->load('general');
		$this->load->library('form_validation');
		$this->lang->load('licensed_request');
		$this->lang->load('projects');
		$this->template->set_template('admin');	
	}
	
	public function id($id) {
								
		$this->template->add_css('themes/opendata/datadeposit.css');
		$this->config->load('datadeposit');
		$this->load->model('user_model');
        $this->load->library('session');
        $this->load->library('ion_auth');
		$this->template->add_css('javascript/jquery/themes/base/ui.all.css');
		$this->template->add_js('javascript/jquery/ui/ui.core.js');
		$this->template->add_js('javascript/jquery/ui/ui.tabs.js');	
		$this->load->model('dd_Resource_model');
		$data['project'] = $this->Projects_model->project_id($id); 
		$data['row']     = $this->Study_model->get_study($data['project'][0]->id);
		$data['files']   = $this->dd_Resource_model->get_project_resources_to_array($id);
		$data['status']  = $data['project'][0]->status;
		$data['fields']  = $this->config->item('datadeposit');
		$data['history'] = $this->Projects_model->history_id($data['project'][0]->id);
		$location        = md5($data['project'][0]->id . $data['project'][0]->created_on);
		$targetDir       = dirname(__FILE__) . '/../../../datadeposit/datafiles/datadeposit/' . $location;
		$user            = $this->ion_auth->get_user($data['project'][0]->uid);
		$data['folder']  = @scandir($targetDir);
		$grids                         = array();
		$grids['methods']              = array(
			'titles' => array (
				'Text'           => 'text', 
				'Vocabulary'     => 'vocab',
				'Vocabulary URI' => 'uri'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->overview_methods) ? $data['row'][0]->overview_methods : null))
		);
		$grids['topic_class']          = array(
			'titles' => array (
				'Text'           => 'text', 
				'Vocabulary'     => 'vocab',
				'Vocabulary URI' => 'uri'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->scope_class) ? $data['row'][0]->scope_class : null))
		);
		$grids['country']              = array(
			'titles' => array (
				'Name'           => 'name', 
				'Abbreviation'   => 'abbr'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->coverage_country) ? $data['row'][0]->coverage_country : null))
		);
		$grids['prim_investigator']    = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_investigator) ? $data['row'][0]->prod_s_investigator : null))
		);
		$grids['other_producers']      = array(
			'titles' => array (
				'Name'         => 'name', 
				'Abbreviation' => 'abbr',
				'Affiliation'  => 'affiliation',
				'Role'         => 'role'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_other_prod) ? $data['row'][0]->prod_s_other_prod : null))
		);
		$grids['funding']              = array(
			'titles' => array (
				'Name'         => 'name', 
				'Abbreviation' => 'abbr',
				'Affiliation'  => 'affiliation',
				'Role'         => 'role'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_funding) ? $data['row'][0]->prod_s_funding : null))
		);
		$grids['acknowledgements']     = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Role'         => 'role'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_acknowledgements) ? $data['row'][0]->prod_s_acknowledgements : null))
		);
		$grids['dates_datacollection'] = array(
			'titles' => array (
				'Start'     => 'start', 
				'End'       => 'end',
				'Cycle'     => 'cycle'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->coll_dates) ? $data['row'][0]->coll_dates : null))
		);
		$grids['time_periods']         = array(
			'titles' => array (
				'Start'     => 'start', 
				'End'       => 'end',
				'Cycle'     => 'cycle'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->coll_periods) ? $data['row'][0]->coll_periods : null))
		);
		$grids['data_collectors']      = array(
			'titles' => array (
				'Name'         => 'name', 
				'Abbreviation' => 'abbr',
				'Affiliation'  => 'affiliation',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->coll_collectors) ? $data['row'][0]->coll_collectors : null))
		);
		$grids['access_authority']     = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->access_authority) ? $data['row'][0]->access_authority : null))
		);
		$grids['contacts']             = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->contacts_contacts) ? $data['row'][0]->contacts_contacts : null))
		);
		$grids['impact_wb_lead']    = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->impact_wb_lead) ? $data['row'][0]->impact_wb_lead : null))
		);
		$grids['impact_wb_members']    = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
		);
		// add our grids to the data variable with their html representation
		foreach ($grids as $grid_id => $grid_data) {
			$data[$grid_id] = $this->_study_grid($grid_id, $grid_data, true);
		}	
		
		if ($this->input->post('update')) {
			$update = array();
			if ($this->input->post('status')) {
				$update['status'] = strtolower($this->input->post('status'));
			}
			$update['admin_comments']   = $this->input->post('comments');
			$update['administrated_by'] = $this->session->userdata('username');
			$update['administer_date']  = date("Y:m:d H:i:s");
			$this->Projects_model->update($id, $update);
			if ($this->input->post('notify')) {
				$this->_email($user->email, nl2br($update['admin_comments']));
			}
			$this->_write_history_entry('Admin updated status', $data['project'][0]->id, $update['status']);
			$this->session->set_flashdata('message', t('submitted'));
			redirect('admin/datadeposit');
		}
		
		if ($this->input->get('print') && $this->input->get('print') == 'yes') {
			$this->load->view('datadeposit/summary', $data);
		} else {
			$content=$this->load->view('datadeposit/datadeposit', $data, true);
			//pass data to the site's template
			$this->template->write('content', $content,true);
		
			//set page title
			$this->template->write('title', t('title_project_management'),true);

			//render final output
		  	$this->template->render();
		}
	}
	
	public function index() {
							$this->template->add_css('themes/wb_intranet/datadeposit.css');

		$result['fields']   = array(
			'title'         => 'Title',         'shortname'  => 'Shortname',
			'created_on'    => 'Created on',    'created_by' => 'Created by',
			'collaborators' => 'Collaborators', 'status'     => 'Status'
		);

		$this->sort_by    = $this->input->get('sort_by')    ? $this->input->get('sort_by'): 'created_on';
		$this->sort_order = $this->input->get('sort_order') ? $this->input->get('sort_order'): 'desc';
		//get array of db rows		
		$result['projects']=$this->Projects_model->all_projects();
		//load the contents of the page into a variable
		$content=$this->load->view('datadeposit/index', $result,true);

		//pass data to the site's template
		$this->template->write('content', $content,true);
		
		//set page title
		$this->template->write('title', t('title_project_management'),true);

		//render final output
	  	$this->template->render();	
	}
	
	private function _grid_data_encode($input, $json=true) {
		if (!is_array($input)) {
			return '';
		}
		$array = array();
		$x     = 0;
		// here we prepare the post data array back to our documented format
		foreach($input as $columns) {
			foreach($columns as $rows) {
				$array[$x++][] = current($rows);
			}
			$x = 0;
		}
		// if an array (row) has all empty elements, remove it; do this for the entire grid.
		$array = array_filter($array, create_function('&$value', '
			if (is_array($value)) {
			foreach ($value as $vals) {
				if (!empty($vals)) return 1;
			}
			// is empty
			return 0;
		}')
		);

		return ($json) ? json_encode($array) : $array;
	}
	
	private function _grid_data_decode($data) {
		return ($data) ? (array) json_decode($data) : null;
	}

	private function _write_history_entry($comment, $project_id, $status) {
		$data = array(
			'project_id'     => (int) $project_id,
			'user_identity'  => $this->session->userdata('email'),
			'created_on'     => date("Y:m:d H:i:s"),
			'project_status' => $status,
			'comments'       => $comment,
		);
		$this->Projects_model->log_history($data);
	}

	private function _email($email, $message) {
		$this->load->library('email');
		$this->email->clear();		
		$config['mailtype'] = 'html';
		$this->email->initialize($config);//intialize using the settings in mail
		$this->email->set_newline("\r\n");
		$this->email->from($this->config->item('website_webmaster_email'), $this->config->item('website_title'));
		$this->email->to($email);
		$this->email->subject('Project Status Changed');
		$this->email->message($message);
		$this->email->send();
	}
		
	/* Grid data array:
	 //                      col 1 header, html class
	 $data['titles'] = array('title 1' => 'class1', ...); 
	 $data['data']   = array(
	    // row 1   col 1, col 2, col 3
	 	0 => array($var1, $var2, $var3),
	    // row 2   col 1,  col 2, col 3
	 	1 => array($var1, $var2, $var3)
	  );
	*/
	 
	private function _study_grid($id, array $data, $disabled = true) {
		// validate id
		/* for now */ if (!isset($data['data'])) $data['data'] = array();
		if (!preg_match('/^[$A-Z_][0-9A-Z_$]*$/i', $id)) {
			throw new Exception("id `{$id}' is invalid");
		}
		// prevent duplicate id's
		if (in_array($id, $this->_study_grid_ids)) {
			throw new Exception('duplicate grid id\'s detected');
		} else {
			$this->_study_grid_ids[] = $id;
		}
		// each grid has a unique javascript 'counter' for field additions, along with an id
		$grid    = '<script type="text/javascript">var index_' . $id . ' = ' . sizeof($data['data']) . '; </script>' . PHP_EOL;
		$grid   .= '<div class="">' . PHP_EOL . '		<table cellspacing="0" cellpadding="0" style="width:100%;margin:10px" class="left" id="' . $id . '" name="' . $id . '" >';
		$grid   .= PHP_EOL . '<tbody><tr>' . PHP_EOL; 
		$index   = 'index_' . $id;
		
		foreach ($data['titles'] as $title => $class) {
			$grid  .= '<th cellspacing="0" cellpadding="0"  style="border: 1px solid gainsboro;" class="' . $class .'">' . $title . '</th>' . PHP_EOL;
		}
		
		$grid   .= PHP_EOL;
	
		$grid   .= '</tr>' . PHP_EOL;
		/* Now we load the data from the database into the grid, if any */
		$check = sizeof($data['titles']) && sizeof(current($data['data'])); // a little housekeeping
		if (!empty($data) && !$check) {
			throw new Exception("title columns and data columns do not match in length");
		}
		if (empty($data['data'])) {
			// This is an empty grid, so allow for user to add data with 0 rows
			$grid .= '</tbody></table></div>' . PHP_EOL;
			// If $empty is true and there is no data, just return an empty string
			return '';
		}
		// otherwise, present the data in our tabular grid
		$titles = $data['titles'];
		$temp   = $titles;
		$y      = 0;
		foreach ($data['data'] as $rows) {
			$grid       .= '<tr>' . PHP_EOL;
			foreach ($rows as $cols) {	
				$is      = ($disabled) ? 'disabled="disabled"' : null;
				$grid   .= "<td cellspacing=\"0\" cellpadding=\"0\"  style='border: 1px solid gainsboro;' width='10%'>{$cols}</td>";
				$grid   .= PHP_EOL;  
			}
			$titles = $temp;
			$y++;
			if ($disabled !== true) {
				$grid       .= '<td class="last"><div class="button-del">-</div></td>' . PHP_EOL;
			}
			$grid       .= '</tr>' . PHP_EOL;
		}
	$grid .= '</tbody></table></div>' . PHP_EOL;
	return $grid;
	}	
}