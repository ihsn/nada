<?php
/*
*
*
*
* Iterator for DDI variables
*
*
*/

class DdiVariableIterator implements Iterator
{
    private $file;
	private $xml_reader = null;
	private $position   = 0;
	private $is_valid   = false;

	public function __construct($xml_file)
    {
        require_once dirname(__FILE__).'/DdiVariable.php';

        $this->file = $xml_file;
        $this->openAndScan();
    }

    public function rewind(): void
    {
        $this->xml_reader->close();
        $this->openAndScan();
    }

    private function openAndScan(): void
    {
        $this->xml_reader = new XMLReader();

        if (!$this->xml_reader->open($this->file, null, LIBXML_NOERROR | LIBXML_NOWARNING)) {
            throw new Exception("DDIVARIABLEITERATOR::FAILED TO OPEN FILE:" . $this->file);
        }

        $found = false;
        while ($this->xml_reader->read()) {
            if ($this->xml_reader->nodeType == XMLReader::ELEMENT && $this->xml_reader->localName == "var") {
                $this->position = 0;
                $found = true;
                break;
            }
        }
        $this->is_valid = $found;
    }

    public function current(): ?DdiVariable
    {
        if ($this->xml_reader->nodeType == XMLReader::ELEMENT && $this->xml_reader->localName == "var")
        {
            $xml = $this->xml_reader->readOuterXML();

            if (!$xml_obj = simplexml_load_string($xml))
            {
                throw new Exception("VARIABLE OUTPUT NOT VALID: " . $xml);
            }

            return new DdiVariable($xml_obj);
        }

        return null;
    }

    public function key(): int
    {
        return $this->position;
    }

    public function next(): void
    {
        while ($this->xml_reader->next()) {
            if ($this->xml_reader->nodeType == XMLReader::ELEMENT && $this->xml_reader->localName == "var") {
                $this->position++;
                $this->is_valid = true;
                return;
            }
        }
        $this->is_valid = false;
    }

    public function valid(): bool
    {
        return $this->is_valid;
    }


}
