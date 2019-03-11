<?php
class Term_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }
	
	/**
	* Returns all terms by vocabulary id
	*
	*/
	function select_all($vid)
	{		
		$this->db->select('*');
		$this->db->select('vid',$vid);
		$this->db->from('terms');
		$query = $this->db->get()->result_array();		
		return $query;
	}

	/**
	*
	* Return a single term by tid
	*/
	function select_single($tid)
	{		
		$this->db->select('*');
		$this->db->from('terms');
		$this->db->where('tid', $tid);
		$query = $this->db->get()->row_array();		
		return $query;
	}
	

	/**
	*
	* Return a single term by tid
	*/
	function find_term($title,$vid=NULL)
	{		
		$this->db->select('*');
		$this->db->from('terms');
		$this->db->where('title', $title);
		if ($vid!=NULL)
		{
			$this->db->where('vid', $vid);
		}
		$query = $this->db->get()->result_array();
		return $query;
	}	
	
	/**
	*
	* Return a single term by tid
	*/
	function get_vocabulary_title($vid)
	{		
		$this->db->select('title');
		$this->db->from('vocabularies');
		$this->db->where('vid', $vid);
		$query = $this->db->get()->row_array();
		
		if($query)
		{
			return $query['title'];
		}
		return FALSE;
	}
	
	/**
	* 	Create new term
	*
	*	@options	array
	*/
	function insert($options)
	{			
		//allowed fields
		$valid_fields=array(
						'vid',
						'pid',
						'title'
						);

		$data=array();
		
		foreach($options as $key=>$value)
		{
			if (in_array($key,$valid_fields) )
			{
				$data[$key]=$value;
			}
		}
		
		//insert
		$result=$this->db->insert('terms', $data); 

		if ($result===false)
		{
			throw new MY_Exception($this->db->_error_message());
		}			
		return TRUE;
	}

	/**
	*
	* Update vocabulary
	*
	*/
	function update($tid, $options)
	{			
		//allowed fields
		$valid_fields=array(
						'vid',
						'pid',
						'title'
						);

		$data=array();
		
		foreach($options as $key=>$value)
		{
			if (in_array($key,$valid_fields) )
			{
				$data[$key]=$value;
			}
		}
		
		//update
		$this->db->where('tid',$tid);
		$result=$this->db->update('terms', $data); 

		if ($result===false)
		{
			throw new MY_Exception($this->db->_error_message());
		}			
		return TRUE;
	}

	/**
	*
	* Delete a vocabulary
	*/
	function delete($tid)
	{
		$this->db->where('tid', $tid); 
		return $this->db->delete('terms');
	}
	
	/**
	*
	* Get children terms by Vocabulary and Parent term
	*
	**/
	function get_children($vid,$pid)
	{
		$this->db->select("*");
		$this->db->where('pid', $pid); 
		$this->db->where('vid', $vid);
		return $this->db->get('terms')->result_array();
	}
	
	
	function get_terms_tree($vid,$tid=0,$show_root=FALSE)
	{
		$items=$this->get_children($vid,$tid);

		$result=NULL;
		if ($show_root==TRUE)
		{
			$result[0]='--select--';
		}

		foreach($items as $item)
		{
			$item=(object)$item;
			
			$result[$item->tid]=$item->title;
			$sub=$this->_children($vid,$item->tid);

			if (is_array($sub))
			{
				//merge array
				foreach($sub as $key=>$value){
					$result[$key]=$value;
				}
			}	
		}

		return $result;
	}
	
	function _children($vid,$tid,$level='--')
	{
		$sub=$this->get_children($vid,$tid);
		
		$output=NULL;
		
		foreach($sub as $i)
		{
			$output[$i['tid']]=$level.$i['title'];
			$sub=$this->_children($vid,$i['tid'],$level.$level);

			if (is_array($sub))
			{
				//merge array
				foreach($sub as $key=>$value){
					$output[$key]=$value;
				}
				//$output=array_merge($output,$sub);
			}
		}
		
		return $output;		
	}

	/**
	*
	* Returns an multi-dimensional hierarichal array of all terms by vocabulary
	*
	**/
	function get_terms_tree_array($vid,$tid=0,$filter=NULL)
	{
		//get parent level items
		$items=$this->get_children($vid,$tid);

		$result=array();

		foreach($items as $item)
		{
			$item=(object)$item;
			
			$tmp=array();
			$tmp['tid']=$item->tid;
			$tmp['title']=$item->title;
			
			//get children terms
			$sub=$this->get_terms_tree_array($vid,$item->tid);

			if (is_array($sub) && count($sub)>0)
			{
				$tmp['children']=$sub;
			}
			$result[]=$tmp;
		}

		return $result;
	}

	/**
	*
	* Creates a formatted list of terms
	*
	* @dependency _get_formatted_terms_tree, _flat_term
	**/
	function get_formatted_terms_tree($vid,$tid=0,$filter=NULL)
	{
		//heirarchical array of terms	
		$tree=$this->get_terms_tree_array($vid,$tid,$filter);
		
		return $this->_get_formatted_terms_tree($tree,$filter);	
	}
	
	function _get_formatted_terms_tree($tree,$filter)
	{
		$pattern = '/\[[0-9.]*\]/i';
		
		$result='<ul>';
		
		foreach($tree as $term)
		{
			$term=(object)$term;
						
			if (isset($term->children))
			{			
				//$result.=$this->_get_formatted_terms_tree($term->children);
				
				//get all children and grand children as a flat array
				$children=$this->_flat_term($term->children,'',$filter);
				
				if (is_array($children) && count($children)>0)
				{
					//first level terms - only if they have children
					$result.=sprintf('<li class="topic-heading"><input type="checkbox" value="%d" id="t-%d" name="topic[]" class="chk-topic-hd"/><label for="t-%d">%s</label>',
								$term->tid,
								$term->tid,
								$term->tid,
								preg_replace($pattern, '', $term->title));

				}
				
				$result.='<ul>';
				foreach($children as $child)
				{	
					$child=(object)$child;			
					//$result.='<li id="'.$child->tid.'">'.$child->title.'</li>';
					$result.=sprintf('<li class="topic"><input type="checkbox" value="%d" id="t-%d" name="topic[]" class="chk-topic"/><label for="t-%d">%s</label></li>',
								$child->tid,
								$child->tid,
								$child->tid,
								preg_replace($pattern, '', $child->title));
				}
				$result.='</ul>';	
			}
			
			$result.='</li>';
		}
		
		$result.='</ul>';
		
		return $result;
		
		//remove brackets from the topic title
		$pattern = '/\[[0-9.]*\]/i';
		$replacement = '';
		return preg_replace($pattern, $replacement, $result);
	}
	
	
	/**
	*
	* Flatten secondary and grand children terms into a flat array
	* this is because the system can only handle two level categories.
	* THis way we can have unlimited levels of children and they can still work
	**/
	function _flat_term($tree,$parent='',$filter=NULL)
	{
		$result=array();
		foreach($tree as $term)
		{
			$term=(object)$term;
			
			$term_path=($parent=='') ? $term->title : $parent.'/'.$term->title;
			
			if ($filter==NULL)
			{
				$result[]=array('tid'=>$term->tid,'title'=>$term_path);
			}
			else if(in_array($term->tid,$filter))
			{
				$result[]=array('tid'=>$term->tid,'title'=>$term_path);
			}
					
			if (isset($term->children))
			{
				$children=$this->_flat_term($term->children,$parent=$term->title,$filter);
				$result=array_merge($result,$children);				
			}
		}		
		return $result;
	}

	/**
	*
	* Return an array of topics related to surveys
	*
	**/
	function get_survey_topics_array()
	{
		$this->db->select("tid");
		$this->db->from('survey_topics');
		$this->db->group_by("tid"); 

		$query=$this->db->get();
		
		$result=array();
		
		if ($query)
		{
			$rows=$query->result_array();
			foreach($rows as $row)
			{
				$result[]=$row['tid'];
			}
		}
		return $result;
	}

	/**
	*
	* Return an array of terms related to surveys by vocabularyid
	*
	**/
	function get_terms_by_vocabulary($vid)
	{
		$this->db->select("*");
		$this->db->where("vid",$vid);
		$query=$this->db->get("terms");

		if ($query)
		{
			$rows=$query->result_array();
			return $rows;
		}
		
		return NULL;
	}
	
	
	/**
	*
	* Return an array of terms attached to a study
	*
	**/
	function get_survey_collections($sid)
	{
		$this->db->select("tid");
		$this->db->from('survey_collections');
		$this->db->where("sid",$sid);

		$query=$this->db->get();
		
		$result=array();
		
		if ($query)
		{
			$rows=$query->result_array();
			
			foreach($rows as $row)
			{
				$result[]=$row['tid'];
			}
			
			return $result;
		}
		return NULL;
	}
	
	/**
	*
	* Return an array of collections related to surveys
	*
	**/
	function get_survey_collections_array()
	{
		$this->db->select("tid");
		$this->db->from('survey_collections');
		$this->db->group_by("tid"); 

		$query=$this->db->get();
		
		$result=array();
		
		if ($query)
		{
			$rows=$query->result_array();
			foreach($rows as $row)
			{
				$result[]=$row['tid'];
			}
		}
		return $result;
	}


	function get_terms_by_repo($vid,$active_only=FALSE,$repositoryid=NULL)
	{
		$this->db->select('terms.tid,terms.pid,terms.title,count(terms.tid) as surveys_found');
		$this->db->from('terms');
		$this->db->order_by('title');
		$this->db->where('vid',$vid);
        //$this->db->where('pid >',0,false);
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
		
		if(!$query){
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