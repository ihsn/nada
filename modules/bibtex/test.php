<pre>
<?php

class exportbib
{
	var $data=array();
	var $_options=array();
	var $authorstring = 'VON LAST, JR, FIRST';	
	function __construct()
	{
	 	$this->_options        = array(
            'stripDelimiter'    => true,
            'validate'          => true,
            'unwrap'            => false,
            'wordWrapWidth'     => false,
            'wordWrapBreak'     => "\n",
            'wordWrapCut'       => 0,
            'removeCurlyBraces' => false,
            'extractAuthors'    => true,
        );
	}
	
	/**
     * Converts the stored BibTex entries to a BibTex String
     *
     * In the field list, the author is the last field.
     *
     * @access public
     * @return string The BibTex string
     */
    function bibTex()
    {
        $bibtex = '';
        foreach ($this->data as $entry) {
            //Intro
            $bibtex .= '@'.strtolower($entry['entryType']).' { '.$entry['cite'].",\n";
            //Other fields except author
            foreach ($entry as $key=>$val) {
                if ($this->_options['wordWrapWidth']>0) {
                    $val = $this->_wordWrap($val);
                }
                if (!in_array($key, array('cite','entryType','author'))) {
                    $bibtex .= "\t".$key.' = {'.$val."},\n";
                }
            }
            //Author
            if (array_key_exists('author', $entry)) {
                if ($this->_options['extractAuthors']) {
                    $tmparray = array(); //In this array the authors are saved and the joind with an and
                    foreach ($entry['author'] as $authorentry) {
                        $tmparray[] = $this->_formatAuthor($authorentry);
                    }
                    $author = join(' and ', $tmparray);
                } else {
                    $author = $entry['author'];
                }
            } else {
                $author = '';
            }
            $bibtex .= "\tauthor = {".$author."}\n";
            $bibtex.="}\n\n";
        }
        return $bibtex;
    }
    /**
     * Returns the author formatted
     *
     * The Author is formatted as setted in the authorstring
     *
     * @access private
     * @param array $array Author array
     * @return string the formatted author string
     */
    function _formatAuthor($array)
    {
        if (!array_key_exists('von', $array)) {
            $array['von'] = '';
        } else {
            $array['von'] = trim($array['von']);
        }
        if (!array_key_exists('last', $array)) {
            $array['last'] = '';
        } else {
            $array['last'] = trim($array['last']);
        }
        if (!array_key_exists('jr', $array)) {
            $array['jr'] = '';
        } else {
            $array['jr'] = trim($array['jr']);
        }
        if (!array_key_exists('first', $array)) {
            $array['first'] = '';
        } else {
            $array['first'] = trim($array['first']);
        }
        $ret = $this->authorstring;
        $ret = str_replace("VON", $array['von'], $ret);
        $ret = str_replace("LAST", $array['last'], $ret);
        $ret = str_replace("JR", $array['jr'], $ret);
        $ret = str_replace("FIRST", $array['first'], $ret);
        return trim($ret);
    }

}

?>

<?php

include "PARSECREATORS.php";
include "PARSEENTRIES.php";
include "PARSEMONTH.php";
include "PARSEPAGE.php";


// Parse a file
	$parse = NEW PARSEENTRIES();
	$parse->expandMacro = TRUE;
//	$array = array("RMP" =>"Rev., Mod. Phys.");
//	$parse->loadStringMacro($array);
	$parse->removeDelimit = TRUE;
//	$parse->fieldExtract = FALSE;
	$parse->openBib("bib.bib");
	$parse->extractEntries();
	$parse->closeBib();
	list($preamble, $strings, $entries, $undefinedStrings) = $parse->returnArrays();
	print_r($preamble);
	print "\n";
	print_r($strings);
	print "\n";
	echo "entries..............";
	print_r($entries);
	print "\n\n";

	$authors=$entries[0]['author'];
	$creator = new PARSECREATORS();
	$creatorArray = $creator->parse($authors);
	print_r($creatorArray);

echo '--------------------------------------------';


$addarray = array();
$addarray['entryType']          = 'Article';
$addarray['cite']               = 'art2';
$addarray['title']              = 'Titel2';
$addarray['author'][0]['first'] = 'John';
$addarray['author'][0]['last']  = 'Doe';
$addarray['author'][0]['von']  = 'Van-dam';
$addarray['author'][1]['first'] = 'Jane';
$addarray['author'][1]['last']  = 'Doe';

$exportbib= new exportbib;
$exportbib->data[]=$addarray;
echo $exportbib->bibTex();
?>