<?php

namespace Dekor\PhpSyntaxTreeBuilder\Exceptions;

use Dekor\PhpSyntaxTreeBuilder\Lexem;
use Throwable;

class ParserSequenceUnmatchedException extends BuilderException
{
    /**
     * @var Lexem
     */
    private $lexem;

    /**
     * ParserSequenceUnmatchedException constructor.
     * @param string $message
     * @param Lexem $lexem
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = "", $lexem, int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->lexem = $lexem;
    }

    /**
     * @return Lexem
     */
    public function getLexem()
    {
        return $this->lexem;
    }
}