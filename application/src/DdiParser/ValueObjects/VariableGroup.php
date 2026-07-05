<?php

namespace Nada\DdiParser\ValueObjects;

class VariableGroup
{
    private $vgid;
    private $groupType;
    private $variableGroups;
    private $variables;
    private $label;
    private $definition;

    public function __construct($vgid, $groupType, $variableGroups, $variables, $label, $definition)
    {
        $this->vgid           = $vgid;
        $this->groupType      = $groupType;
        $this->variableGroups = $variableGroups;
        $this->variables      = $variables;
        $this->label          = $label;
        $this->definition     = $definition;
    }

    public static function fromArray(array $data)
    {
        return new self(
            isset($data['vgid'])            ? $data['vgid']            : null,
            isset($data['group_type'])      ? $data['group_type']      : null,
            isset($data['variable_groups']) ? $data['variable_groups'] : null,
            isset($data['variables'])       ? $data['variables']       : null,
            isset($data['label'])           ? $data['label']           : null,
            isset($data['definition'])      ? $data['definition']      : null
        );
    }

    public function getVgid()           { return $this->vgid; }
    public function getGroupType()      { return $this->groupType; }
    public function getVariableGroups() { return $this->variableGroups; }
    public function getVariables()      { return $this->variables; }
    public function getLabel()          { return $this->label; }
    public function getDefinition()     { return $this->definition; }

    public function toArray()
    {
        return [
            'vgid'            => $this->vgid,
            'group_type'      => $this->groupType,
            'variable_groups' => $this->variableGroups,
            'variables'       => $this->variables,
            'label'           => $this->label,
            'definition'      => $this->definition,
        ];
    }
}
