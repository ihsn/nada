<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 
 *  get plain text from multidimensional array
 * 
 */	
if ( ! function_exists('array_to_plain_text'))
{
	function array_to_plain_text($data)
	{
		$output = array();
        $item = new RecursiveIteratorIterator(new RecursiveArrayIterator($data));
        foreach($item as $value) {
            $output[] = $value;
        }  
        return implode(' ', $output);        
	}
}


/**
 * 
 * 
 * Return array value by key
 * 
 */
if ( ! function_exists('array_get_value'))
{
	function array_get_value($data,$key)
	{
        if(array_key_exists($key,$data)){
            return $data[$key];
        }
        return false;
    }
}




/**
 * @param array $array
 * @param array|string $parents
 * @param string $glue
 * @return mixed
 */
if ( ! function_exists('get_array_nested_value'))
{
    function get_array_nested_value(array &$array, $parents, $glue = '/')
    {
        if (!is_array($parents)) {
            $parents = explode($glue, $parents);
        }

        $ref = &$array;

        foreach ((array) $parents as $parent) {
            if (is_array($ref) && array_key_exists($parent, $ref)) {
                $ref = &$ref[$parent];
            } else {
                return null;
            }
        }
        return $ref;
    }
}


/**
 * @param array $array
 * @param array|string $parents
 * @param mixed $value
 * @param string $glue
 */
if ( ! function_exists('set_array_nested_value'))
{
    function set_array_nested_value(array &$array, $parents, $value, $glue = '/')
    {
        if (!is_array($parents)) {
            $parents = explode($glue, (string) $parents);
        }

        $ref = &$array;

        foreach ($parents as $parent) {
            if (isset($ref) && !is_array($ref)) {
                $ref = array();
            }

            $ref = &$ref[$parent];
        }

        $ref = $value;
    }
}


/**
 * @param array $array
 * @param array|string $parents
 * @param string $glue
 */
if ( ! function_exists('array_unset_value'))
{
    function array_unset_value(&$array, $parents, $glue = '.')
    {
        if (!is_array($parents)) {
            $parents = explode($glue, $parents);
        }

        $key = array_shift($parents);

        if (empty($parents)) {
            unset($array[$key]);
        } else {
            array_unset_value($array[$key], $parents);
        }
    }
}


if ( ! function_exists('array_remove_nulls'))
{
    function array_remove_nulls(array &$array)
    {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $value = array_remove_nulls($value);
            }
            if ($value===null) {
                unset($array[$key]);
            }
        }
        return $array;
    }
}



if ( ! function_exists('is_indexed_array'))
{
    //check if array has numeric keys
    function is_indexed_array($arr)
    {
        if (!is_array($arr)){
            return false;
        }
        if (array() === $arr) return false;
        $k = array_keys( $arr ); 
        return $k === array_keys( $k );
    }
}


if ( ! function_exists('is_simple_indexed_array'))
{
    //array has numeric keys and contain no sub arrays
    function is_simple_indexed_array($arr)
    {
        $indexed=is_indexed_array($arr);

        if ($indexed==true){
            foreach($arr as $key=>$value){
                if (is_array($value)){
                    foreach($value as $child){
                        if (is_array($child)){
                            return false;
                        }
                    }
                }
            }        
        }

        return $indexed;
    }
}

if ( ! function_exists('array_indexed_elements'))
{
    //return array nodes with simple arrays
    function array_indexed_elements($arr,$parent=null)
    {
        $output=array();
        foreach($arr as $node_key=>$node){

            $el_path=implode("/",array_filter(array($parent, $node_key)));

            if(is_simple_indexed_array($node)){
                $output[]= $el_path;
            }

            if (is_array($node)){
                $output=array_merge($output,array_indexed_elements($node,$el_path));
            }
        }

        return $output;
    }
}