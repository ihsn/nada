<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * metadata display helper function
 *
 */

if ( ! function_exists('render_field'))
{
	function render_field($type, $name, $data, $options=array())
	{
		$ci =& get_instance();

		switch($type)
		{
			case 'text':
				return render_text($name, $data,$options);
				break;
			case 'array':
			case 'table':
				return render_table($name, $data,$options);
				break;
			case 'bounding_box':
				return render_bounding_box($name, $data,$options);
				break;
            case 'var_category':
                return render_var_category($name, $data,$options);
                break;
			default:				
				return render_custom($type,$name,$data,$options);
		}
		
	}
}

if ( ! function_exists('render_custom'))
{
	function render_custom($type,$name, $data,$options=array())
	{
		$ci =& get_instance();
		$custom_field='application/views/metadata_templates/fields/field_'.$type.'.php';
		$view_file='metadata_templates/fields/field_'.$type;
		if (file_exists($custom_field)){
			return $ci->load->view($view_file,array('name'=>$name, 'data'=>$data,'options'=>$options), TRUE);
		}
		else{
			return render_table($name,$data);
		}
	}
}


 
if ( ! function_exists('render_text'))
{
	function render_text($name, $data, $options=array())
	{
		$ci =& get_instance();
		return $ci->load->view('metadata_templates/fields/field_text',array('name'=>$name, 'data'=>$data, 'options'=>$options), TRUE);
	}
}



if ( ! function_exists('render_bounding_box'))
{
	function render_bounding_box($name, $data)
	{
		$ci =& get_instance();
		return $ci->load->view('metadata_templates/fields/field_bounding_box',array('name'=>$name, 'data'=>$data), TRUE);
	}
}



if ( ! function_exists('render_table'))
{
	function render_table($name, $data, $options=array())
	{
		$ci =& get_instance();
		return $ci->load->view('metadata_templates/fields/field_array',array('name'=>$name, 'data'=>$data, 'options'=>$options), TRUE);
	}
}



function get_field_value($name,$data)
{
	/*if (array_key_exists($name, $metadata_array)){
		return $metadata_array[$name];
	}*/

	$paths = explode('.', $name);
    #$result = $metadata;
    //recursively find the path
    foreach ($paths as $path) {
        if(!isset($data[$path])){
            return false;
        }
        $data = $data[$path];
    }
    return $data;
}


if ( ! function_exists('render_group'))
{
	function render_group($name, $fields, $metadata,$options=array())
	{
		$ci =& get_instance();
		return $ci->load->view('metadata_templates/fields/section',
			array(
				'section_name'=>$name, 
				'metadata'=>$metadata,
				'fields'=>$fields,
				'options'=>$options
			)
			, TRUE);
	}
}


if ( ! function_exists('render_group_array'))
{
	function render_group_array($name, $fields, $metadata,$options=array())
	{
		$ci =& get_instance();

		$output=[];
		foreach($fields as $field_name=>$field_type){
			$value=get_field_value($field_name,$metadata);
			//$field_options=isset($field_type['options'])
			if (is_array($field_type)){
				$output[$field_name]= render_field($field_type[0],$field_name,$value,$options=$field_type['options']);
			}
			else{
				$output[$field_name]= render_field($field_type,$field_name,$value,$options);
			}
		}
    
		return $output;
	}
}


if ( ! function_exists('render_group_text'))
{
	function render_group_text($section_name, $html)
	{
		$ci =& get_instance();
		return $ci->load->view('metadata_templates/fields/section_field',
			array(
				'section_name'=>$section_name, 
				'output'=>$html				
			)
			, TRUE);
	}
}



if ( ! function_exists('render_columns')) 
{
	function render_columns($name, $fields, $metadata,$options=array())
	{
		$ci =& get_instance();
		return $ci->load->view('metadata_templates/fields/bootstrap_columns',
			array(
				'section_name'=>$name, 
				'metadata'=>$metadata,
				'fields'=>$fields,
				'options'=>$options
			)
			, TRUE);
	}
}


if ( ! function_exists('render_columns_array'))
{
	function render_columns_array($name,$fields=array(), $options=array())
	{
		$ci =& get_instance();
		return $ci->load->view('metadata_templates/fields/columns_array',
			array(
				'section_name'=>$name, 
				'fields'=>$fields,
				'options'=>$options
			)
			, TRUE);
	}
}



if ( ! function_exists('nada_category_is_missing_flag'))
{
	/**
	 * Truthy missing flag — aligned with field_var_category.php (not '', not '0', not 0).
	 */
	function nada_category_is_missing_flag($item)
	{
		$item = (array) $item;
		$ismissing_ = isset($item['is_missing']) ? $item['is_missing'] : '';
		return ($ismissing_ !== '' && $ismissing_ !== '0' && $ismissing_ !== 0);
	}
}


if ( ! function_exists('nada_var_sumstat_invd_non_wgtd'))
{
	/**
	 * First non-weighted var_sumstat row with type invd; value must be numeric and > 0.
	 *
	 * @param  array|mixed  $var_sumstat
	 * @return int|float|null
	 */
	function nada_var_sumstat_invd_non_wgtd($var_sumstat)
	{
		if (!is_array($var_sumstat)) {
			return null;
		}
		foreach ($var_sumstat as $row) {
			$row = (array) $row;
			$type = isset($row['type']) ? $row['type'] : '';
			if ($type !== 'invd') {
				continue;
			}
			$wgtd = isset($row['wgtd']) ? $row['wgtd'] : '';
			if ($wgtd === 'wgtd') {
				continue;
			}
			if (!array_key_exists('value', $row) || $row['value'] === '' || $row['value'] === null) {
				continue;
			}
			if (!is_numeric($row['value'])) {
				continue;
			}
			$v = $row['value'] + 0;
			if ($v > 0) {
				return $v;
			}
		}
		return null;
	}
}


if ( ! function_exists('merge_var_catgry_invd_sysmiss_display'))
{
	/**
	 * Append a synthetic Sysmiss frequency from var_sumstat.invd only when no category already
	 * carries a missing flag and no literal Sysmiss row exists (avoids duplicate Sysmiss).
	 *
	 * @param  array  $var_catgry
	 * @param  array  $var_sumstat
	 * @return array
	 */
	function merge_var_catgry_invd_sysmiss_display(array $var_catgry, $var_sumstat = array())
	{
		$out = array();
		foreach ($var_catgry as $row) {
			$out[] = is_array($row) ? $row : (array) $row;
		}

		foreach ($out as $item) {
			if (nada_category_is_missing_flag($item)) {
				return $out;
			}
		}

		foreach ($out as $item) {
			$item = (array) $item;
			foreach (array('value', 'labl', 'label') as $k) {
				if (!isset($item[$k]) || !is_string($item[$k])) {
					continue;
				}
				if (strcasecmp(trim($item[$k]), 'Sysmiss') === 0) {
					return $out;
				}
			}
		}

		$invd = nada_var_sumstat_invd_non_wgtd($var_sumstat);
		if ($invd === null) {
			return $out;
		}

		$out[] = array(
			'value'      => 'Sysmiss',
			'labl'       => null,
			'is_missing' => '1',
			'stats'      => array(
				array(
					'type'  => 'freq',
					'value' => $invd,
				),
			),
		);

		return $out;
	}
}


if ( ! function_exists('render_var_category'))
{
    function render_var_category($name, $data)
    {
        $ci =& get_instance();
        return $ci->load->view('metadata_templates/fields/field_var_category',array('name'=>$name, 'data'=>$data), TRUE);
    }
}




/**
 *
 * Creates a string value out of array type elements
 *
 * Note: uses \r\n for line breaks between multiple rows
 *
 **/
function get_string_value($data,$type='text')
{
    if(!$data)
    {
        return NULL;
    }

    if ($type=='text' || $type=='string')
    {
        if (!is_array($data)){
            return $data;
        }

        return implode("\r\n",$data);
    }
    else if(in_array($type, array('table','array')))
    {
        $output=array();
        foreach($data as $row)
        {
            $row_output=array();

            foreach($row as $field_name=>$field_value)
            {
                if(trim($field_value)!=''){
                    $row_output[]=$field_value;
                }
            }

            //concat a single row
            $output[]=implode(", ",$row_output);
        }

        //combine all rows with line break
        return implode("\r\n",$output);
    }

    throw new Exception("TYPE_NOT_SUPPORTED: ".$type);
}


if ( ! function_exists('authors_to_string'))
{
    function authors_to_string($authors=array())
    {
		$output=array();
        foreach($authors as $author){
			$author_name=array(
				isset($author['first_name']) ? $author['first_name'] : '', 
				isset($author['last_name']) ? $author['last_name']: ''
			);
			$output[]=implode(" ", array_filter($author_name));
		}

		return implode(", ", $output);
    }
}

/* End of file metadata_view_helper.php */
/* Location: ./application/helpers/metadata_view_helper.php */