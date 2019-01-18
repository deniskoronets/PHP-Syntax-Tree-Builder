<?php

namespace Dekor\PhpSyntaxTreeBuilder;

class Lexem
{
    public $name;

    public $value;

    public $line;

    public function __construct(string $name, $value, int $line)
    {
        $this->name = $name;
        $this->value = $value;
        $this->line = $line;
    }
}