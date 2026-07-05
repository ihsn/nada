<?php

namespace Nada\DdiParser\Factory;

use Nada\DdiParser\Contracts\ReaderInterface;
use Nada\DdiParser\Parsing\DDIReader;
use Nada\GisParser\Parsing\GisReader;

class ReaderFactory
{
    public static function getReader($readerType, $file, $metadata_key_mappings = null): ReaderInterface
    {
        if ($readerType === 'survey') {
            return new DDIReader($file, $metadata_key_mappings);
        }

        if ($readerType === 'geospatial') {
            return new GisReader($file, $metadata_key_mappings);
        }

        throw new \Exception("READER TYPE NOT SUPPORTED: " . $readerType);
    }
}
