<?php

namespace Dekor\PhpSyntaxTreeBuilder\Exceptions;

class LoopedNestingException extends BuilderException
{
    public function __construct()
    {
        parent::__construct(
            'Your grammar has too big nesting. This could be caused when you have recursive loop. Possibly, it is a thing which called Left Recursion.'
        );
    }
}