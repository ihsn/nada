<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * metadata display helper function
 *
 */

if ( ! function_exists('render_field'))
{
	function render_field($type, $name, $data)
	{
		$ci =& get_instance();

		switch($type)
		{
			case 'text':
				return render_text($name, $data);
				break;
			case 'array':
			case 'table':
				return render_table($name, $data);
				break;
			case 'bounding_box':
				return render_bounding_box($name, $data);
				break;
            case 'var_category':
                return render_var_category($name, $data);
                break;
			default:				
				return render_custom($type,$name,$data);
		}
		
	}
}

if ( ! function_exists('render_custom'))
{
	function render_custom($type,$name, $data)
	{
		$ci =& get_instance();
		$custom_field='application/views/metadata_templates/fields/field_'.$type.'.php';
		$view_file='metadata_templates/fields/field_'.$type;
		if (file_exists($custom_field)){
			return $ci->load->view($view_file,array('name'=>$name, 'data'=>$data), TRUE);
		}
		else{
			return render_table($name,$data);
		}
	}
}


 
if ( ! function_exists('render_text'))
{
	function render_text($name, $data)
	{
		$ci =& get_instance();
		return $ci->load->view('metadata_templates/fields/field_text',array('name'=>$name, 'data'=>$data), TRUE);
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
	function render_table($name, $data, $columns=array())
	{
		$ci =& get_instance();
		return $ci->load->view('metadata_templates/fields/field_array',array('name'=>$name, 'data'=>$data,'field_columns'=>$columns), TRUE);
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
	function render_group($name, $fields, $metadata)
	{
		$ci =& get_instance();
		return $ci->load->view('metadata_templates/fields/section',array('section_name'=>$name, 'metadata'=>$metadata,'fields'=>$fields), TRUE);
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
        $output=NULL;
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

/* End of file search_helper.php */
/* Location: ./application/helpers/search_helper.php */