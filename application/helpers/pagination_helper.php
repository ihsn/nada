<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Create custom pager
 *
 * @access	public
 * @param	total_records	number of total records
 * @param	limit			how many pages to show
 * @param	current_page	current page - gets highlighted as active
 * @param	adjacents		how many page numbers to show before and after current page
 *
 * @return	string	httml formatted pager
 */	
if ( ! function_exists('pager'))
{
	function pager($total_records, $limit = null, $current_page = null, $adjacents = null)
	{
		$total_pages=ceil($total_records / $limit);
		
		if ($total_pages<2)
		{
			return false;
		}
		
		$result = range(1, ceil($total_records / $limit));	
		$search_qs='?'.get_querystring( array('ps','sk', 'vk', 'vf','view','topic','country','from','to'));
		
		if (($adjacents = floor($adjacents / 2) * 2 + 1) >= 1)
		{
			$result = array_slice($result, max(0, min(count($result) - $adjacents, intval($current_page) - ceil($adjacents / 2))), $adjacents);
		}
		
		$output=array();
		if ($current_page>1 && $total_pages>1)
		{
			$output[]=sprintf('<a href="%s" class="page first" data-page="%s"> &laquo; </a>',
						site_url('catalog/').$search_qs.'&page=1',1
						);

			$output[]=sprintf('<a href="%s" class="page prev" data-page="%s"> %s </a>',
						site_url('catalog/').$search_qs.'&page='.($current_page-1),
						$current_page-1,
						t('prev')
						);
		}	
		
		foreach($result as $page)
		{
			$css='';
			if($page==$current_page)
			{
				$css='active';
			}
			
			$output[]=sprintf('<a href="%s" class="page %s" data-page="%s">%s</a>',
						site_url('catalog/').$search_qs.'&page='.$page,
						$css,
						$page,
						$page);
		}
		
		if ($current_page<$total_pages)
		{
			$output[]=sprintf('<a href="%s" class="page next" data-page="%s"> %s </a>',
					site_url('catalog/').$search_qs.'&page='.($current_page+1),
					$current_page+1,
					t('next')
					);
			
			$output[]=sprintf('<a href="%s" class="page last" data-page="%s" title="%s">&raquo;</a>',
					site_url('catalog/').$search_qs.'&page='.($total_pages),
					$total_pages,t('Last')
					);

		}
	
		$pager='<div class="pager">';
		$pager.=implode('',$output);
		$pager.='</div>';
		
		return $pager;
	}
}



/* End of file pagination_helper.php */
/* Location: ./application/helpers/pagination_helper.php */