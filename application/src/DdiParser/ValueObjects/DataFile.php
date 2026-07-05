<?php

namespace Nada\DdiParser\ValueObjects;

class DataFile
{
    private $id;
    private $uri;
    private $filename;
    private $fileId;
    private $caseQnty;
    private $varQnty;
    private $fileType;
    private $fileCont;
    private $filePlac;

    public function __construct($id, $uri, $filename, $fileId, $caseQnty, $varQnty, $fileType, $fileCont, $filePlac)
    {
        $this->id       = $id;
        $this->uri      = $uri;
        $this->filename = $filename;
        $this->fileId   = $fileId;
        $this->caseQnty = $caseQnty;
        $this->varQnty  = $varQnty;
        $this->fileType = $fileType;
        $this->fileCont = $fileCont;
        $this->filePlac = $filePlac;
    }

    public static function fromArray(array $data)
    {
        return new self(
            isset($data['id'])       ? $data['id']       : null,
            isset($data['uri'])      ? $data['uri']      : null,
            isset($data['filename']) ? $data['filename'] : null,
            isset($data['file_id'])  ? $data['file_id']  : null,
            isset($data['caseQnty']) ? $data['caseQnty'] : null,
            isset($data['varQnty'])  ? $data['varQnty']  : null,
            isset($data['filetype']) ? $data['filetype'] : null,
            isset($data['fileCont']) ? $data['fileCont'] : null,
            isset($data['filePlac']) ? $data['filePlac'] : null
        );
    }

    public function getId()       { return $this->id; }
    public function getUri()      { return $this->uri; }
    public function getFilename() { return $this->filename; }
    public function getFileId()   { return $this->fileId; }
    public function getCaseQnty() { return $this->caseQnty; }
    public function getVarQnty()  { return $this->varQnty; }
    public function getFileType() { return $this->fileType; }
    public function getFileCont() { return $this->fileCont; }
    public function getFilePlac() { return $this->filePlac; }

    public function toArray()
    {
        return [
            'id'       => $this->id,
            'uri'      => $this->uri,
            'filename' => $this->filename,
            'file_id'  => $this->fileId,
            'caseQnty' => $this->caseQnty,
            'varQnty'  => $this->varQnty,
            'filetype' => $this->fileType,
            'fileCont' => $this->fileCont,
            'filePlac' => $this->filePlac,
        ];
    }
}
