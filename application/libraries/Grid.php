<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Grid Class
 *
 * Class for generating, encoding (storing), and decoding tabular grids and their data.
 *
 */

class Grid
{
	private $ci;
	private $_study_grid_ids = array();
	
	/**
	 * Constructor 
	 *
	 */

	public function __construct() {
		$this->ci =& get_instance();
		log_message('debug', "Grid Class Initialized.");
	}
	
	/**
	 * Private Methods 
	 *
	 */
	
	private function _grid_data_encode($id, $input, $json=true) {
		if (!is_array($input)) {
			return '';
		}
		$array           = array();
		$x               = 0;
		$array['id']     = $id;
		$array['titles'] = array_keys($input);
		$array['data']   = array();
		// here we prepare the post data array back to our documented format
		foreach($input as $columns) {
			foreach($columns as $rows) {
				$array['data'][$x][] = $rows;
			}
			$x++;
		}
		// if an array (row) has all empty elements, remove it; do this for the entire grid.
		$array['data'] = array_filter($array['data'], create_function('&$value', '
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


	private function _check_data(array &$data) {
		if (!preg_match('/^[$A-Z_][0-9A-Z_$]*$/i', $data['id'])) {
			throw new Exception("id `{$data['id']}' is an invalid id");
		}
		// prevent duplicate id's
		if (in_array($data['id'], $this->_study_grid_ids)) {
			throw new Exception('duplicate grid id\'s found');
		} else {
			$this->_study_grid_ids[] = $data['id'];
		}
		
		if (!isset($data['data'])) {
			// empty
			 $data['data'] = array();
		}
		$check = sizeof((array) $data['titles']) === sizeof((array) $data['data']); 
		if (!empty($data) && !$check) {
			throw new Exception("title columns and data columns do not match in length");
		}
	}

	/* Grid data array:
	 $data['id']     = 'grid_id';
	 //                      col 1 header, html class
	 $data['titles'] = array('title 1' => 'class1', ...); 
	 $data['data']   = array(
	    // row 1   col 1, col 2, col 3
	 	0 => array($var1, $var2, $var3),
	    // row 2   col 1,  col 2, col 3
	 	1 => array($var1, $var2, $var3)
	  );
	*/

	private function _generate_grid(array $data, $editable=false, $width='25%') {

		$this->_check_data($data);
		$id = $data['id'];
		
		// each grid has a unique javascript 'counter' for field additions, along with an id
		if ($editable) {
			$grid    = '<script type="text/javascript">var index_' . $id . ' = ' . sizeof($data['data']) . '; </script>' . PHP_EOL;
		} else {
			$grid    = '';
		}
		$grid   .= '<div class="grid_three">' . PHP_EOL . '		<table class="grid left" id="' . $id . '" name="' . $id . '" style="width:'.$width.';">';
		$grid   .= PHP_EOL . '<tbody><tr>' . PHP_EOL; 
		$index   = 'index_' . $id;
		
		foreach ($data['titles'] as $title => $class) {
			$grid  .= '<th class="' . $class .'">' . $title . '</th>' . PHP_EOL;
		}
		
		/* Add the javascript event code, if the grid is to be mutable */
		if ($editable) {
			$new_cols   = $data['titles'];
			$javascript = '<tr>';
			$x          = 1;
			$count      = sizeof($data['titles']);
		
			foreach ($new_cols as $title) {
				if ($x === $count) {
					// we will magically use javascript to automate the increment counter for each row
					$index .= '++';
				}
				$javascript .= '<td><input name="' . $id . '[' . $title . '][\'+'.$index.'+ \'][]" onkeypress="keyPressTest(event, this);" value="" type="text"></td>';
				$x++;
			}
			$javascript .= '<td class="last"><div onclick="$(this).parent().parent().remove();" class="button-del">-</div></td></tr>';	
			// every grid is custom, so to each respectively thier own javascript 'add row' function
			$javascript  = '<script type="text/javascript"> function ' . $id . '_add() { $(\''. $javascript . '\').insertAfter($(\'#' . $id . ' tbody tr\').last()); }</script>' . PHP_EOL;
			// we add it to the beginning of our grid
			$grid    = $javascript . $grid;
			$grid   .= '<th onclick=\'' . $id . '_add();\' class="last"><div class="button-add overviewaddRow">+</div></th>';	
		
			$grid   .= PHP_EOL;
	
			$grid   .= '</tr>' . PHP_EOL;
		}
		
		/* Now we load the data from source into the grid, if any */
		if (empty($data)) {
			// This is an empty grid, so allow for user to add data with 0 rows
			$grid .= '</tbody></table></div>' . PHP_EOL;
			return $grid;
		}
		
		// otherwise, present the data in our tabular grid
		$titles = (array) $data['titles'];
		$temp   = $titles;
		$y      = 0;
		foreach ($data['data'] as $rows) {
			$grid       .= '<tr>' . PHP_EOL;
			foreach ($rows as $cols) {
				$col_data = ($editable) ? "<input name='" . $id . "[" . array_shift($titles) . "][" . $y . "][]' onkeypress='keyPressTest(event, this);' value='{$cols}' type='text'>" : $cols; 
				$grid    .= "<td>{$col_data}</td>";
				$grid    .= PHP_EOL;  
			}
			$titles = $temp;
			$y++;
			if ($editable) {
				$grid       .= '<td class="last"><div class="button-del">-</div></td>' . PHP_EOL;
			}
			$grid       .= '</tr>' . PHP_EOL;
		}
	$grid .= '</tbody></table></div>' . PHP_EOL;
	return $grid;
	}
	
	/**
	 * Public Methods 
	 *
	 */

	public function grid_from_array($data, $editable=true, $width='25%') {
		return $this->_generate_grid($data, $editable, $width);
	}
	
	public function grid_from_json($data, $editable=true, $width='25%') {
		return $this->_generate_grid($this->_grid_data_decode($data), $editable, $width);
	}
	
	public function to_json($id, $post) {
		return $this->_grid_data_encode($id, $post, true);
	}
	
	public function to_array($id, $post) {
		return $this->_grid_data_encode($id, $post, false);
	}
	
}
/* End of file Grid.php */
/* Location: ./application/libraries/Grid.php */
	