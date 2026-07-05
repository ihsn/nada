<?php

namespace Nada\DdiParser\Mapping;

use Nada\DdiParser\ValueObjects\StudyMeta;

class NadaSurveyMapper
{
    private $ddi_mappings;

    public function __construct(array $ddi_mappings)
    {
        $this->ddi_mappings = $ddi_mappings;
    }

    public function map(StudyMeta $studyMeta): array
    {
        $metadata = $studyMeta->toArray();
        $mappings = [];

        foreach ($this->ddi_mappings as $key => $value) {
            $mappings[$value['xpath']][] = $key;
        }

        $output = [];

        foreach ($mappings as $xpath => $keys) {
            if (!isset($metadata[$xpath])) {
                continue;
            }

            $element_value = $metadata[$xpath];

            foreach ($keys as $key) {
                if (isset($this->ddi_mappings[$key]['type']) && $this->ddi_mappings[$key]['type'] === 'array') {
                    $this->array_nested_path($output, $key, $element_value);
                } else {
                    $value = is_array($element_value) ? implode(' ', $element_value) : $element_value;
                    $this->array_nested_path($output, $key, $value);
                }
            }
        }

        return $output;
    }

    private function array_nested_path(array &$array, $parents, $value, $glue = '/'): array
    {
        $parts     = explode($glue, (string) $parents);
        $reference = &$array;

        foreach ($parts as $key) {
            if (!array_key_exists($key, $reference)) {
                $reference[$key] = [];
            }
            $reference = &$reference[$key];
        }

        $reference = $value;
        unset($reference);

        return $array;
    }

    public function get_nested_value(array $data, $path, $glue = '/')
    {
        $parts     = explode($glue, (string) $path);
        $reference = $data;

        foreach ($parts as $key) {
            if (!array_key_exists($key, $reference)) {
                return false;
            }
            $reference = $reference[$key];
        }

        return $reference;
    }
}
