<?php

namespace Nada\DdiParser\ValueObjects;

class StudyMeta
{
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function get($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    public function toArray()
    {
        return $this->data;
    }
}
