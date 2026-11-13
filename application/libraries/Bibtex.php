<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


use RenanBr\BibTexParser\Listener;
use RenanBr\BibTexParser\Parser;
use RenanBr\BibTexParser\Processor;


/**
 * BibTeX import.
 *
 * parse_string() returns legacy NADA citation rows (ctype, title, subtitle, authors[] with fname/lname/initial,
 * ...); Citation_csl::from_legacy_row() turns them into CSL documents. BibTeX export is in
 * Citation_import_export.
 */
class BibTeX{

	/** BibTeX field => NADA field, copied as is (text is cleaned of LaTeX braces) */
	var $bibtex_field_mapping=array(
		'address'=>'place_publication',		//: Publisher's address (usually just the city)
		'edition'=>'edition',
		'institution'=>'organization',		//: The institution that was involved in the publishing
		'organization'=>'organization',		//: The conference sponsor
		'school'=>'organization',			//: The school where the thesis was written
		'journal'=>'subtitle',				//: The journal or magazine the work was published in
		'booktitle'=>'subtitle',			//: The book or proceedings the work is part of
		'month'=>'pub_month',
		'number'=>'issue',
		'publisher'=>'publisher',
		'title'=>'title',
		'url'=>'url',
		'volume'=>'volume',
		'year'=>'pub_year',
		'isbn'=>'idnumber',
		'issn'=>'idnumber',
		'abstract'=>'abstract',
		'keywords'=>'keywords',
		'doi'=>'doi',
	);

	/** BibTeX entry type => NADA citation type */
	var $entry_types=array(
		'article'=>'journal',
		'book'=>'book',
		'booklet'=>'book',
		'conference'=>'conference-paper',
		'inbook'=>'book-section',
		'incollection'=>'book-section',
		'inproceedings'=>'conference-paper',
		'manual'=>'book',
		'mastersthesis'=>'thesis',
		'misc'=>'book',
		'phdthesis'=>'thesis',
		'proceedings'=>'conference-paper',
		'techreport'=>'report',
		'unpublished'=>'book'
	);

	/** BibTeX fields that hold names, with the NADA field they go to */
	var $name_fields=array(
		'author'=>'authors',
		'editor'=>'editors',
		'translator'=>'translators',
	);

	var $errors=array();

	function __construct($params=NULL)
	{
	}


	/**
	 * @return array|FALSE legacy NADA rows; FALSE when the text can't be parsed (see $errors)
	 */
	function parse_string($bibtex)
	{
		$this->errors=array();

		$listener = new Listener();
		$listener->addProcessor(new Processor\TagNameCaseProcessor(CASE_LOWER));
		// names are split here (names()): the parser's own splitting loses {Organization Names}

		$parser = new Parser();
		$parser->addListener($listener);

		try{
			$parser->parseString($bibtex);
			$entries = $listener->export();
		}
		catch(Exception $e){
			$this->errors[]=$e->getMessage();
			return FALSE;
		}

		if (!is_array($entries)){
			return FALSE;
		}

		return $this->bibtex_to_nada_mapping($entries);
	}


	/**
	 * Converts bib entry array to nada format
	 */
	function bibtex_to_nada_mapping($bib_entries)
	{
		$output=array();

		foreach($bib_entries as $entry)
		{
			$row=array();

			//1 to 1 mappings of fields
			foreach($this->bibtex_field_mapping as $bib_field=>$nada_field)
			{
				if (isset($entry[$bib_field]) && is_string($entry[$bib_field]) && trim($entry[$bib_field])!=='')
				{
					//the first field to give a value wins (journal before booktitle)
					if (!isset($row[$nada_field]) || $row[$nada_field]==='')
					{
						$row[$nada_field]=$this->clean_text($entry[$bib_field]);
					}
				}
			}

			//names
			foreach($this->name_fields as $bib_field=>$nada_field)
			{
				if (isset($entry[$bib_field]) && is_string($entry[$bib_field]))
				{
					$row[$nada_field]=$this->names($entry[$bib_field]);
				}
			}

			//pages: 10--20, 10-20, 10
			if (isset($entry['pages']) && is_string($entry['pages']) && trim($entry['pages'])!=='')
			{
				$pages=preg_split('/\s*[-\x{2013}\x{2014}]+\s*/u', trim($entry['pages']), 2);
				$row['page_from']=$pages[0];
				$row['page_to']=isset($pages[1]) ? $pages[1] : '';
			}

			//citation type. e.g. article, book
			$entry_type=strtolower((string)(isset($entry['_type']) ? $entry['_type'] : (isset($entry['type']) ? $entry['type'] : '')));
			$row['ctype']=array_key_exists($entry_type,$this->entry_types) ? $this->entry_types[$entry_type] : 'book';

			//theses: the thesis type goes in the subtitle field
			if ($row['ctype']=='thesis' && empty($row['subtitle']))
			{
				$row['subtitle']=($entry_type=='mastersthesis') ? "Master's thesis" : 'PhD thesis';
			}

			if (!isset($row['title']))
			{
				$row['title']='';
			}

			$output[]=$row;
		}

		return $output;
	}


	/**
	 * "Doe, Jane and {World Bank} and Jane Roe" => [{fname, lname, initial}]
	 *
	 * {Braced Name} is an organization (no first name). "Last, First" and "First von Last" are both read.
	 */
	private function names($raw)
	{
		$output=array();
		foreach($this->split_top_level($raw, ' and ') as $name)
		{
			$name=trim($name);
			if ($name==='')
			{
				continue;
			}

			//{World Bank} or {{UN}}: one braced group is an organization
			if ($name[0]=='{' && $this->closing_brace($name, 0)===strlen($name)-1)
			{
				$output[]=array('fname'=>'','lname'=>$this->clean_text($name),'initial'=>'');
				continue;
			}

			$parts=array_map('trim', $this->split_top_level($name, ','));
			if (count($parts)>=2)
			{
				//Last, First  |  Last, Jr, First
				$last=$parts[0];
				$first=(count($parts)>=3) ? $parts[2] : $parts[1];
				if (count($parts)>=3 && $parts[1]!=='')
				{
					$last.=' '.$parts[1];
				}
			}
			else
			{
				//First von Last: the last word, with the lower-case particles before it, is the family name
				$words=array_values(array_filter($this->split_top_level($name, ' '), 'strlen'));
				if (count($words)==1)
				{
					$output[]=array('fname'=>'','lname'=>$this->clean_text($words[0]),'initial'=>'');
					continue;
				}
				$last_words=array(array_pop($words));
				while (!empty($words) && count($words)>1 && preg_match('/^[a-z]/u', end($words)))
				{
					array_unshift($last_words, array_pop($words));
				}
				$first=implode(' ', $words);
				$last=implode(' ', $last_words);
			}

			$first=$this->clean_text($first);
			$last=$this->clean_text($last);
			if ($first.$last!=='')
			{
				$output[]=array('fname'=>$first,'lname'=>$last,'initial'=>'');
			}
		}
		return $output;
	}


	/**
	 * Split on a separator that is not inside braces
	 */
	private function split_top_level($text, $separator)
	{
		$parts=array();
		$depth=0;
		$current='';
		$len=strlen($text);
		$sep_len=strlen($separator);

		for ($i=0; $i<$len; $i++)
		{
			$c=$text[$i];
			if ($c=='\\' && $i+1<$len)
			{
				$current.=$c.$text[++$i];
				continue;
			}
			if ($c=='{')
			{
				$depth++;
			}
			elseif ($c=='}' && $depth>0)
			{
				$depth--;
			}
			if ($depth==0 && substr($text, $i, $sep_len)===$separator)
			{
				$parts[]=$current;
				$current='';
				$i+=$sep_len-1;
				continue;
			}
			$current.=$c;
		}
		$parts[]=$current;
		return $parts;
	}


	/**
	 * Index of the brace that closes the one at $open, or FALSE
	 */
	private function closing_brace($text, $open)
	{
		$depth=0;
		$len=strlen($text);
		for ($i=$open; $i<$len; $i++)
		{
			if ($text[$i]=='\\')
			{
				$i++;
				continue;
			}
			if ($text[$i]=='{')
			{
				$depth++;
			}
			elseif ($text[$i]=='}' && --$depth==0)
			{
				return $i;
			}
		}
		return FALSE;
	}


	/**
	 * Remove BibTeX braces and the common escapes
	 */
	private function clean_text($text)
	{
		//escaped braces survive the brace removal
		$text=str_replace(array('\\{','\\}'),array("\x01","\x02"),(string)$text);
		$text=str_replace(array('\\&','\\%','\\_','\\$','\\#'),array('&','%','_','$','#'),$text);
		$text=str_replace(array('{','}'),'',$text);
		$text=str_replace(array("\x01","\x02"),array('{','}'),$text);
		return trim(preg_replace('/\s+/',' ',$text));
	}

}
// END Bibtex Class

/* End of file Bibtex.php */
/* Location: ./application/libraries/Bibtex.php */
