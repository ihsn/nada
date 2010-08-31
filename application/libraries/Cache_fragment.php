<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
// Fragment caching library for CI
// =============================================================================
// written by nir gavish 2010
// nirg@tantalum.co.il
// http://www.webweb.co.il/ (hebrew site)
// =============================================================================
class Cache_fragment{
    private $fragment_path = 'application/cache/'; // make sure this is a valid dir
    private $fragment_name;
    private $newly_cached = false;
    private $CI;

    function Cache_fragment(){
        $this->CI =& get_instance();
    }
    
    function start($lifespan){
        if ($this->fragment_name!=''){die('Nested fragment cache not supported.');}
        $x = debug_backtrace();

        $this->fragment_name = md5($this->CI->uri->uri_string().'||'.$x[0]['line']);

        // if file does not exist, make preparations to cache and return true, so segment is executed
        if(!file_exists($this->fragment_path . $this->fragment_name)){
            $this->newly_cached = true;
            ob_start();
            return true;
        }else{
            // cache exists, let's see if it is still valid by checking it's age against the $lifespan variable
            $fModify = filemtime($this->fragment_path . $this->fragment_name);
            $fAge = time() - $fModify;
            if ($fAge > ($lifespan * 60)){
                // file is old, let's re-cache
                $this->newly_cached = true;
                ob_start();
                return true;
            }
            // no need to redo
            return false;
        }
    }
    
    function end(){
        if($this->newly_cached==true){
            $new_cache = ob_get_clean();
            
            $fname = $this->fragment_path . $this->fragment_name;
            $fhandle = fopen($fname,"w+");
            $content = $new_cache;
            fwrite($fhandle,$content);
            fclose($fhandle);
        }
        include $this->fragment_path . $this->fragment_name;

        $this->newly_cached = false;
        $this->fragment_name = null;
    }
}
/* End of file Cache_fragment.php */
/* Location: ./application/libraries/Cache_fragment.php */