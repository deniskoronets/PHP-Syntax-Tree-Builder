<?php

namespace Dekor\PhpSyntaxTreeBuilder;

class ASTNode
{
    /**
     * @var string
     */
    public $group;

    /**
     * @var array|null
     */
    public $subGroups = [];

    /**
     * @var array
     */
    public $lexems = [];

    public function __construct(string $group, array $subGroups, array $lexems)
    {
        $this->group = $group;
        $this->subGroups = $subGroups;
        $this->lexems = $lexems;
    }
}