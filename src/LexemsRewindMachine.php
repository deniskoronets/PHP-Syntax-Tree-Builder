<?php

namespace Dekor\PhpSyntaxTreeBuilder;

class LexemsRewindMachine
{
    /**
     * @var array|Lexem[]
     */
    private $lexems;

    /**
     * @var int
     */
    private $count;

    /**
     * @var int
     */
    private $pointer;

    /**
     * @var array
     */
    private $transactions;

    /**
     * LexemsRewindMachine constructor.
     * @param Lexem[] $lexems
     */
    public function __construct(array $lexems)
    {
        $this->lexems = $lexems;
        $this->count = count($lexems);
        $this->pointer = 0;
    }

    /**
     * Indicates if sequence has finished
     * @return bool
     */
    public function ended() : bool
    {
        return $this->count <= ($this->pointer + 1);
    }

    /**
     * Starts a new nested transaction
     */
    public function transaction()
    {
        $this->transactions[] = $this->pointer;
    }

    /**
     * Commits last transaction, erase transactions list
     */
    public function commit()
    {
        $this->transactions = [];
    }

    /**
     * Allows to get current lexem
     * @return Lexem
     */
    public function current()
    {
        return $this->lexems[$this->pointer];
    }

    /**
     * Allows to get next lexem
     * @return Lexem
     */
    public function next()
    {
        return $this->lexems[++$this->pointer];
    }

    /**
     * @throws \Exception
     */
    public function rollback()
    {
        if (empty($this->transactions)) {
            throw new \Exception('Unable to rollback transaction, transactions are empty');
        }

        $this->pointer = array_pop($this->transactions);
    }
}