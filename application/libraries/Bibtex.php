<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


use RenanBr\BibTexParser\Listener;
use RenanBr\BibTexParser\Parser;
use RenanBr\BibTexParser\Processor;
use RenanBr\BibTexParser\Processor\NamesProcessor;



/**
 * BibText Import/export class
 * 
 *
 *
 *
 * @subpackage	Libraries
 * @category	BibText import/export
 *
 */ 
class BibTeX{
    
	
		var $bibtex_field_mapping=array(
			'address'=>'place_publication',		//:	Publisher's address (usually just the city, but can be the full address for lesser-known publishers)
			'author'=>'authors',		//: The name(s) of the author(s) (in the case of more than one author, separated by and)
			'booktitle'=>'title',		//: The title of the book, if only part of it is being cited
			'chapter'=>'subtitle',		//: The chapter number
			//'crossref',				//: The key of the cross-referenced entry
			'edition'=>'edition',		//: The edition of a book, long form (such as "first" or "second")
			'editor'=>'editors',		//: The name(s) of the editor(s)
			//'eprint'=>'',				//: A specification of an electronic publication, often a preprint or a technical report
			//'howpublished',			//: How it was published, if the publishing method is nonstandard
			'institution'=>'organization',	//: The institution that was involved in the publishing, but not necessarily the publisher
			'journal'=>'subtitle',			//: The journal or magazine the work was published in
			//'key',					//: A hidden field used for specifying or overriding the alphabetical order of entries (when the "author" and "editor" fields are missing). Note that this is very different from the key (mentioned just after this list) that is used to cite or cross-reference the entry.
			'month'=>'pub_month',		//: The month of publication (or, if unpublished, the month of creation)
			//'note',					//: Miscellaneous extra information
			'number'=>'issue',					//: The "number" of a journal, magazine, or tech-report, if applicable. (Most publications have a "volume", but no "number" field.)
			'organization'=>'organization',	//: The conference sponsor
			'pages'=>'pages',
			//'pages'=>'page_from',		//: Page numbers, separated either by commas or double-hyphens.
			'publisher'=>'publisher',	//: The publisher's name
			'school'=>'organization',		//: The school where the thesis was written
			'series'=>'title',			//: The series of books the book was published in (e.g. "The Hardy Boys" or "Lecture Notes in Computer Science")
			'title'=>'title',			//: The title of the work
			'type'=>'ctype',			//: The type of tech-report, for example, "Research Note"
			'url'=>'url',				//: The WWW address
			'volume'=>'volume',			//: The volume of a journal or multi-volume book
			'year'=>'pub_year',			//: The year of publication (or, if unpublished, the year of creation)
			'bibtexEntryType'=>'ctype',
			'isbn'=>'idnumber',
			'issn'=>'idnumber',
			'abstract'=>'abstract'
		);
				
	var $entry_types=array(
			'article'=>'journal',
			'book'=>'book',
			'booklet'=>'book',
			'conference'=>'conference-paper',
			'inbook'=>'book-section',
			'incollection'=>'book',
			'inproceedings'=>'conference-paper',
			'manual'=>'book',
			'mastersthesis'=>'thesis',
			'misc'=>'book',
			'phdthesis'=>'thesis',
			'proceedings'=>'conference-paper',
			'techreport'=>'report',
			'unpublished'=>'book'
		);
	


	function __construct($params=NULL)
	{
	}


	function parse_string($bibtex)
	{
		$listener = new Listener();
		$listener->addProcessor(new Processor\TagNameCaseProcessor(CASE_LOWER));
		$listener->addProcessor(new NamesProcessor());//process authors
		// $listener->addProcessor(new Processor\KeywordsProcessor());
		// $listener->addProcessor(new Processor\DateProcessor());
		// $listener->addProcessor(new Processor\FillMissingProcessor([/* ... */]));
		// $listener->addProcessor(new Processor\TrimProcessor());
		// $listener->addProcessor(new Processor\UrlFromDoiProcessor());
		// $listener->addProcessor(new Processor\LatexToUnicodeProcessor());

		$parser = new Parser();
		$parser->addListener($listener);

		$parser->parseString($bibtex);
		$entries = $listener->export();


		if (!is_array($entries)){
			return FALSE;
		}
		
		$result=$this->bibtex_to_nada_mapping($entries);
		return $result;
	}
	
	
	/**
	*
	* Convert an Array to BibTeX string
	**/
	function export($entry)
	{
		$addarray = array();
		
		//set entry type
		if (in_array($entry['ctype'],$this->entry_types))
		{
			$addarray['entryType']= array_search($entry['ctype'], $this->entry_types);
		}
		else
		{
			$addarray['entryType']='Article';
		}
		
		foreach($entry as $key=>$value)
		{
			if (in_array($key,$this->bibtex_field_mapping))
			{
				//echo $key;
				//get key by searching values
				$skey=array_search($key, $this->bibtex_field_mapping);
				//echo $skey;
				$addarray[$skey]=$value; 
			}
		}

		echo '<pre>';
		//var_dump($entry);
		//var_dump($addarray['author']);

		
		//remove from output array
		$addarray['author']=FALSE;
		
		//authors
		$authors=$entry['authors'];
		
		//add authors		
		foreach($authors as $author)
		{
			$tmp['first']=$author['fname'];
			$tmp['last']=$author['lname'];
			$tmp['von']=$author['initial'];
			
			$addarray['author'][]=$tmp;
		}
		
				
		/*
		//$addarray['cite']               = 'art2';
		$addarray['title']              = 'Titel2';
		$addarray['author'][0]['first'] = 'John';
		$addarray['author'][0]['last']  = 'Doe';
		$addarray['author'][0]['von']  = 'Van-dam';
		$addarray['author'][1]['first'] = 'Jane';
		$addarray['author'][1]['last']  = 'Doe';
		*/
		
		$exportbib= new exportbib;
		$exportbib->data[]=$addarray;
		echo $exportbib->bibTex();		
	}
	
	/**
	*
	* Converts bib entry array to nada format
	**/
	function bibtex_to_nada_mapping($bib_entries)
	{
		$output=array();
		
		foreach($bib_entries as $entry)
		{
			$single_row=array();
			
			//do 1 to 1 mappings of fields
			foreach($entry as $key=>$value)
			{
			 	if (array_key_exists($key,$this->bibtex_field_mapping))
				{
					$field_name=$this->bibtex_field_mapping[$key];//get the nada field name
					$single_row[$field_name]=$value;
				}	
			} 
			
			//remove curly braces by google
			$single_row['title']=str_replace('{','',$single_row['title']);
			$single_row['title']=str_replace('}','',$single_row['title']);
			
			//convert authors ino an array
			if (isset($single_row['authors']))
			{
				$authors=$single_row['authors'];				
				$nada_authors=array();

				//iterate and convert to nada author
				foreach($single_row['authors'] as $auth)
				{
					$nada_authors[]=array(
						"lname"=> (isset($auth['first']) ? $auth['first'] : ''),
						"fname"=> (isset($auth['last']) ? $auth['last'] : ''),
						"initial"=> (isset($auth['von']) ? $auth['von'] : '')
					);
				}
				
				$single_row['authors']=$nada_authors;
			}

			//map pages from/to
			if (isset($single_row['pages']))
			{
				$pages=explode("--",$single_row['pages']);
				if (count($pages)==2)
				{
					$single_row['page_from']=$pages[0];
					$single_row['page_to']=$pages[1];
				}				
			}

			//map citation type. e.g. article, book
			if (array_key_exists($single_row['ctype'],$this->entry_types))
			{
				$single_row['ctype']=$this->entry_types[$single_row['ctype']];
			}
			else
			{
				$single_row['ctype']='book';//if typed can't be matched with nada types
			}
			
			$output[]=$single_row;
		}
	
		return $output;
	}
	
}
// END Bibtex Class

/* End of file Bibtex.php */
/* Location: ./application/libraries/Bibtex.php */