<?php

namespace Dekor\PhpSyntaxTreeBuilder;

class ASTNode
{
    public $group;

    public $expression;

    public $childs = [];

    public $elseChilds = [];

    public function __construct(string $group, $expression, array $childs = [], array $elseChilds = [])
    {
        $this->group = $group;
        $this->expression = $expression;
        $this->childs = $childs;
        $this->elseChilds = $elseChilds;
    }
}