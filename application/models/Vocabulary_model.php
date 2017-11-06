<?php
class Vocabulary_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }
	
	/**
	* Returns a list of all vocabularies
	*
	*/
	function select_all()
	{		
		$this->db->select('*');
		$this->db->from('vocabularies');
		$query = $this->db->get()->result_array();		
		return $query;
	}

	/**
	*
	* Return a single vocabulary
	*/
	function select_single($vocabularyid)
	{		
		$this->db->select('*');
		$this->db->from('vocabularies');
		$this->db->where('vid', $vocabularyid);
		$query = $this->db->get()->row_array();		
		return $query;
	}
	
	/**
	* 	Create new vocabulary
	*
	*	@title	string
	*/
	function insert($title)
	{			
		$options['title']=$title;
		$result=$this->db->insert('vocabularies', $options); 
				
		if ($result===FALSE)
		{
			throw new MY_Exception($this->db->_error_message());
		}			
		return $this->db->insert_id();
	}

		
	/**
	*
	* Update vocabulary
	*
	*/
	function update($vid, $title)
	{			
		$options['title']=$title;
		$this->db->where('vid',$vid);
		$result=$this->db->update('vocabularies', $options); 
				
		if ($result===false)
		{
			throw new MY_Exception($this->db->_error_message());
		}			
		return TRUE;
	}

	/**
	*
	* Delete a vocabulary and its terms
	*/
	function delete($vid)
	{
		//delete terms
		$this->db->where('vid', $vid);
		$result=$this->db->delete('terms');

		if (!$result)
		{
			return FALSE;
		}
		
		$this->db->where('vid', $vid);
		return $this->db->delete('vocabularies');
	}

	/**
	*
	* Find vocabulary by title
	*/
	function get_vocabulary_by_title($title)
	{		
		$this->db->select('*');
		$this->db->from('vocabularies');
		$this->db->where('title', $title);
		$query = $this->db->get();
		if(!$query)
		{
			return FALSE;
		}
		
		$output=$query->row_array();
		
		return $output;
	}
	
	private function build_tree($items) 
	{	
		$children = array();
	
	
		if (!$items)
		{
			return;
		}
		
		foreach($items as &$item) 
		{	
			$children[$item['pid']][] = &$item;
			unset($item);
		}
		
		foreach($items as &$item)
		{ 
			if (isset($children[$item['tid']]))
			{
				$item['children'] = $children[$item['tid']];
			}
		}	

        if (isset($children[0])) {
            return $children[0];
        }

        return $children;
	}
	
	
	//returns a tree of vocabulary terms
	function get_tree($vid,$active_only=FALSE,$repositoryid=NULL)
	{
		$items= $this->get_terms_array($vid,$active_only,$repositoryid);
        $parent_items=$this->get_parent_terms($vid);
        $items=array_merge($parent_items,$items);
		$tree=$this->build_tree($items);
        return $tree;
	}
	
	function get_terms_array($vid,$active_only=FALSE,$repositoryid=NULL)
	{
		$this->db->select('terms.tid,terms.pid,terms.title,count(terms.tid) as surveys_found');
		$this->db->from('terms');
		$this->db->order_by('title');
		$this->db->where('vid',$vid);
        $this->db->where('pid >',0,false);
		$this->db->group_by('terms.tid,terms.pid,terms.title');
		
		if($active_only==TRUE)
		{
			$this->db->join('survey_topics st','st.tid=terms.tid','left');
			$this->db->join('surveys', 'st.sid=surveys.id','inner');
			$this->db->where('surveys.published',1);			
		}
		
		if($repositoryid!=NULL && $active_only==TRUE)
		{
			$this->db->join('survey_repos', 'st.sid=survey_repos.sid','inner');			
			$this->db->where('survey_repos.repositoryid',$repositoryid);			
		}
		
		$query=$this->db->get();
		
		if(!$query)
		{
			return FALSE;
		}		
		
		$items=$query->result_array();

		$output=array();
		foreach($items as $row)
		{
			$output[$row['tid']]=$row;
		}

		return $output;
	}

    /**
     *
     * get parent terms by vocabulary
     * e.g. where PID=0
     *
     */
	function get_parent_terms($vid)
    {
        $this->db->select('terms.tid,terms.pid,terms.title,count(terms.tid) as surveys_found');
        $this->db->from('terms');
        $this->db->order_by('title');
        $this->db->where('vid',$vid);
        $this->db->where('pid',0);
        $this->db->group_by('terms.tid,terms.pid,terms.title');

        $query=$this->db->get();

        if(!$query)
        {
            return FALSE;
        }

        $items=$query->result_array();

        $output=array();
        foreach($items as $row)
        {
            $output[$row['tid']]=$row;
        }

        return $output;
    }
}