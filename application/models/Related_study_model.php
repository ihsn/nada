<?php

/**
* Relationship types
*
* 	Type 1: require double entries in the db and both end have different names
*
*	Examples:
*		parent -> child
*		child -> parent
*
*
*	Type 2: require double entries and there is no need to define both ends of the relationships
*
*	Examples:
*		isWaveOf -> isWaveOf
*		or
*		isRelatedTo	->	isRelatedTo
*
*
*	Type 3: Relationship not set
*
*	uses the relationship-id=0 and only one entry is required
*
**/


class Related_study_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }

	/**
	*
	* add/update relationship
	*
	*/
	public function update_relationship($p_sid,$c_sid_arr,$relation_id)
	{
		foreach($c_sid_arr as $sid_2)
		{
      //skip if source and target survey is the same
      if ($sid_2==$p_sid){
        continue;
      }

			//get relationship pair by relation_id
			$rel_pairs=$this->get_relationship_pairs($relation_id);

			//delete existing relationship between the two surveys
			$this->delete_relationship($p_sid,$sid_2);

			//rules
			//if rel_pairs row count =1 then use the same relationshipid for both sides

			//unique key for the pair
			$pair_key=date("U");

      //var_dump($rel_pairs);
      //var_dump($relation_id);
      //die();

      //for rel_dir=2
      if (count($rel_pairs)==1 && $rel_pairs[0]['rel_dir']==2){
        $rel_pairs[]=$rel_pairs[0];
      }

			foreach($rel_pairs as $key=>$relation)
			{
				if($key==$relation_id)
				{
					$options=array(
						'sid_1'				=>	$p_sid,
						'sid_2'				=>	$sid_2,
						'relationship_id'	=>	$relation['id'],
						'pair_id'			=>	$pair_key
					);

				}
				else
				{
					$options=array(
						'sid_2'				=>	$p_sid,
						'sid_1'				=>	$sid_2,
						'relationship_id'	=>	$relation['id'],
						'pair_id'			=>	$pair_key
					);

				}


				//add new relationship
				$result=$this->db->insert('survey_relationships', $options);

				if ($result===false){
					throw new MY_Exception($this->db->_error_message());
				}
			}
		}
		return TRUE;
	}

	/**
	*
	* returns the relationship type pair by id
	**/
	public function get_relationship_pairs($relation_id)
	{
		$this->db->select("id,rel_name,rel_dir");
		$this->db->where( sprintf('rel_group_id in (select rel_group_id from survey_relationship_types where id=%d)',(int)$relation_id),NULL,FALSE);
		$rows=$this->db->get("survey_relationship_types")->result_array();

		$output=array();
		foreach($rows as $row)
		{
			$output[$row['id']]=$row;
		}

		//for relationships like isWaveOf <---> isWaveOf
		if(count($rows)==1 && $rows[0]["id"]!=0)
		{
			//duplicate the row
			$output[]=$rows[0];
		}

		return $output;
	}



	public function delete_relationship($sid_1,$sid_2,$relation_id=null)
	{

		if (!$relation_id){
			$this->db->where('sid_1',$sid_1);
			$this->db->where('sid_2',$sid_2);
			$this->db->delete('survey_relationships');

			//delete the reverse of the relationship
			$this->db->where('sid_1',$sid_2);
			$this->db->where('sid_2',$sid_1);
			$this->db->delete('survey_relationships');
			return;
		}

		//get pairs
		$rel_pairs=$this->get_relationship_pairs($relation_id);

		//delete both relationship entries
		foreach($rel_pairs as $key=>$relation){
			if($key==$relation_id){
				$p_sid=$sid_1;
				$c_sid=$sid_2;

				$this->db->where('sid_1',$p_sid);
				$this->db->where('sid_2',$c_sid);
				$this->db->where('relationship_id',$relation['id']);
				$this->db->delete('survey_relationships');
			}
			else{
				$p_sid=$sid_2;
				$c_sid=$sid_1;

				$this->db->where('sid_1',$p_sid);
				$this->db->where('sid_2',$c_sid);
				$this->db->where('relationship_id',$relation['id']);
				$this->db->delete('survey_relationships');
			}
		}

	}

  	//return a list of related survey IDs
	public function get_related_studies_id_list($sid)
	{
		$this->db->select('surveys.id');
		$this->db->where('sid_1',$sid);
		$this->db->join('surveys','surveys.id=survey_relationships.sid_2','INNER');
		$result=$this->db->get('survey_relationships')->result_array();

		$list=array();

		foreach($result as $row){
			$list[]=$row['id'];
		}

		return $list;
	}

	//return a list of related surveys
	public function get_related_studies_list($sid)
	{
		$this->db->select('surveys.id,surveys.title,surveys.nation,surveys.year_start, surveys.year_end');
		$this->db->where('sid_1',$sid);
		$this->db->join('surveys','surveys.id=survey_relationships.sid_2','INNER');
		$result=$this->db->get('survey_relationships')->result_array();
		return $result;
	}


	public function get_relationships($sid)
	{
		$this->db->select('surveys.id as sid,surveys.title, surveys.nation,surveys.year_start, survey_relationships.*');
		$this->db->where('sid_1',$sid);
		$this->db->join('surveys','surveys.id=survey_relationships.sid_2','INNER');
		return $this->db->get('survey_relationships')->result_array();
	}


	/**
	*
	* Return an array of relationship types as pairs
	**/
	public function get_relationship_types_array()
	{
		$sql='select * from (
			select
				srt1.id,
				srt1.rel_group_id,
				srt1.rel_name,
				srt1.rel_dir,
				srt2.rel_name as rel_name2,
				srt2.rel_dir as rel_dir2
			from survey_relationship_types srt1
				inner join survey_relationship_types srt2 on srt1.rel_group_id=srt2.rel_group_id
				where srt1.rel_dir=0 and srt2.rel_dir=1
			UNION ALL
			select
				srt1.id,
				srt1.rel_group_id,
				srt1.rel_name,
				srt1.rel_dir,
				srt2.rel_name as rel_name2,
				srt2.rel_dir as rel_dir2
			from survey_relationship_types srt1
				inner join survey_relationship_types srt2 on srt1.rel_group_id=srt2.rel_group_id
				where srt1.rel_dir=1 and srt2.rel_dir=0
			UNION ALL
			select
				id,
				rel_group_id,
				rel_name,
				rel_dir,
				rel_name as rel_name2,
				rel_dir as rel_dir2
			from survey_relationship_types
				where rel_dir=2
			)
			relationship_types order by id;';

		$types=$this->db->query($sql)->result_array();

		$output=array();

		$no_duplicates=array();

		foreach($types as $type)
		{
			$output[$type['id']]=$type['rel_name'].' ---> ('.$type['rel_name2'].')';
		}

		return $output;
	}
}
