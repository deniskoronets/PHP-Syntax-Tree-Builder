<?php

namespace Dekor\PhpSyntaxTreeBuilder;

class Lexem
{
    public $name;

    public $value;

    public function __construct(string $name, $value = '')
    {
        $this->name = $name;
        $this->value = $value;
    }
}