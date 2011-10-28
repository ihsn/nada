<?php
class Page extends MY_Controller {
 
    public function __construct()
    {
        parent::__construct($skip_auth=TRUE);
		$this->lang->load('general');
		//$this->output->enable_profiler(TRUE);
		
		//set template for print
		if ($this->input->get("print")==='yes')
		{
			$this->template->set_template('blank');
		}
    }
    
	function index()
	{	
		if (in_array($this->uri->segment(1), array('page','pages')) )
		{
			if ($this->uri->segment(2)!='' )
			{
				//get page data
				$data=$this->Menu_model->get_page($this->uri->segment(2));
			}
			else{
				//this part will never get executed
				
				//get home page contente
				$data['title']='Home page';
				$data['body']='home page content here....';				
			}			
		}
		else
		{		
			//get default home page
			$default_home=$this->config->item("default_home_page");			
			$page_=$this->uri->segment(1);

			//show home page
			if ($page_==false)
			{							
				if ($default_home!==FALSE)
				{
					//check if the page is a link or a static page
					$data=$this->Menu_model->get_page($default_home);
					
					if($data)
					{
						if ($data['linktype']!==0)
						{
							//redirect
							redirect($default_home);return;
						}
					}
					else
					{
							//redirect
							redirect($default_home);return;
					}
				}

				//no default home page set				
				//get the page with minimum weight to be the home page
				$data=$this->Menu_model->get_page_by_min_weight();
				
				if ($data)
				{
					//link or page
					if ($data['linktype']!==0)
					{
						//link
						redirect($data['url']);
					}
				}
			}			
			else //static pages in the database
			{
				$data=$this->Menu_model->get_page($page_);
			}
		}
		
		//page not found in the database
		if ( empty($data) )
		{
			show_404();
			return;			
		}
		
		if (isset($data['css_links']) && trim($data['css_links'])!=='')
		{
			$css_arr=explode("\r",$data['css_links']);
			foreach($css_arr as $css)
			{
				$this->template->add_css(trim($css));
			}
		}

		if (isset($data['js_inline']) && trim($data['js_inline'])!=='')
		{
			$this->template->add_js($data['js_inline'],'embed');
		}

		if (isset($data['js_links']) && trim($data['js_links'])!=='')
		{
			$js_arr=explode("\r",$data['js_links']);
			foreach($js_arr as $js)
			{
				$this->template->add_js(trim($js));
			}
		}

		
		//load the contents of the page into a variable
		$content=$this->load->view('page_index', $data,true);
		
		//set page title
		$this->template->write('title', $data['title'],true);
		
		//pass data to the site's template
		$this->template->write('content', $content,true);
		
		//render final output
	  	$this->template->render();
	}
	
	function user_bar()
	{
		$this->load->view('user_bar');
	}
	
	function switch_language($lang=NULL)
	{
		if ($lang==NULL)
		{
			show_404();
		}
		
		$valid_languages=$this->config->item("supported_languages");
		
		if (in_array($lang,$valid_languages))
		{
			//set language in the user session cooke
			$this->session->set_userdata('language',strtolower($lang));
			
			$destination=site_home();
			
			if ($this->input->get("destination"))
			{
				$destination=$this->input->get("destination");
			}			
			redirect($destination);
		}
		else
		{
			show_error("Invalid Language selected!");
		}
	}
	
	
	
}
/* End of file page.php */
/* Location: ./controllers/page.php */