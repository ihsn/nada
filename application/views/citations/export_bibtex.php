<?php
$entry_types=array(
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

$br="\r\n";
$bib_type= array_search($bib['ctype'],$entry_types);
$author_array=array();
foreach($bib['authors'] as $author)
{
	if ($author['lname']!='')
	{
		$tmp[]=$author['lname'];
	}
	if ($author['fname']!='')
	{
		$tmp[]=$author['fname'];
	}
		
	$author_array[]=implode(", ", $tmp);
}

$author_string=implode(" and ", $author_array);
?>

@<?php echo $bib_type;?>{,
	title={{<?php echo $bib['title'];?>}},
	<?php if ($author_string!=''):?>
    author={<?php echo $author_string;?>},<?php echo $br;?>
    <?php endif;?>	  
	<?php if ($bib_type=='article'):?>
    journal={<?php echo $bib['subtitle'];?>},<?php echo $br;?>
    <?php endif;?>	  
	<?php if ($bib['volume']!=''):?>
    volume={<?php echo $bib['volume'];?>},<?php echo $br;?>
    <?php endif;?>	  
	<?php if ($bib['idnumber']!=''):?>
    number={<?php echo $bib['idnumber'];?>},<?php echo $br;?>
    <?php endif;?>	  
	<?php if ($bib['page_from']!='' || $bib['page_to']!=''):?>
    pages={<?php echo $bib['page_from'];?><?php echo ($bib['page_to'])!='' ? '--'.$bib['page_to'] : '';?>},<?php echo $br;?>
    <?php endif;?>
    <?php if ($bib['pub_year']!=''):?>
    year={<?php echo $bib['pub_year'];?>},<?php echo $br;?>
    <?php endif;?>	  
    <?php if ($bib['publisher']!=''):?>
    publisher={<?php echo $bib['publisher'];?>},<?php echo $br;?>
    <?php endif;?>	  

}
<?php
/*
$bib['address']=>'place_publication',		//:	Publisher's address (usually just the city, but can be the full address for lesser-known publishers)
$bib['author'=>'authors',		//: The name(s) of the author(s) (in the case of more than one author, separated by and)
$bib['booktitle'=>'title',		//: The title of the book, if only part of it is being cited
$bib['chapter'=>'subtitle',		//: The chapter number
$bib['edition'=>'edition',		//: The edition of a book, long form (such as "first" or "second")
$bib['editor'=>'editors',		//: The name(s) of the editor(s)
$bib['institution'=>'organization',	//: The institution that was involved in the publishing, but not necessarily the publisher
$bib['journal'=>'subtitle',			//: The journal or magazine the work was published in
$bib['month'=>'pub_month',		//: The month of publication (or, if unpublished, the month of creation)
$bib['number'=>'issue',					//: The "number" of a journal, magazine, or tech-report, if applicable. (Most publications have a "volume", but no "number" field.)
$bib['organization'=>'organization',	//: The conference sponsor
$bib['pages'=>'page_from',		//: Page numbers, separated either by commas or double-hyphens.
$bib['publisher'=>'publisher',	//: The publisher's name
$bib['school'=>'organization',		//: The school where the thesis was written
$bib['series'=>'title',			//: The series of books the book was published in (e.g. "The Hardy Boys" or "Lecture Notes in Computer Science")
$bib['title'=>'title',			//: The title of the work
$bib['url'=>'url',				//: The WWW address
$bib['volume'=>'volume',			//: The volume of a journal or multi-volume book
$bib['year'=>'pub_year',			//: The year of publication (or, if unpublished, the year of creation)
$bib['bibtexEntryType'=>'ctype',
$bib['isbn'=>'idnumber',
$bib['issn'=>'idnumber',
$bib['abstract'=>'abstract'*/
?>			