<?php

namespace Dekor\PhpSyntaxTreeBuilder;

use Dekor\PhpSyntaxTreeBuilder\Exceptions\GrammaryException;
use Dekor\PhpSyntaxTreeBuilder\Exceptions\LoopedNestingException;
use Dekor\PhpSyntaxTreeBuilder\Exceptions\ParserSequenceUnmatchedException;
use Dekor\PhpSyntaxTreeBuilder\Exceptions\ParserSequenceUnmatchedOnFirstElementException;

class Parser
{
    /**
     * @var array
     */
    public $config = [];

    /**
     * @var array
     */
    public $groups = [];

    /**
     * Parser constructor.
     * @param array $config
     * @param array $groups
     */
    public function __construct(array $config, array $groups)
    {
        $this->config = $config;
        $this->groups = $groups;
    }

    /**
     * @param $groupName
     * @param LexemsRewindMachine $rewind
     * @param int $nesting
     * @return ASTNode[]
     * @throws GrammaryException
     * @throws LoopedNestingException
     * @throws ParserSequenceUnmatchedException
     * @throws ParserSequenceUnmatchedOnFirstElementException
     */
    public function group($groupName, LexemsRewindMachine $rewind, $nesting = 0)
    {
        $astSequence = [];

        if (!isset($this->groups[$groupName])) {
            throw new \Exception('Group ' . $groupName . ' doesnt exist. Check your parser config');
        }

        if ($rewind->ended()) {
            return $astSequence;
        }

        $group = $this->groups[$groupName];

        if (isset($group['or'])) {

            $lastException = null;

            foreach ($group['or'] as $allowedGroup) {

                $lastException = null;

                /**
                 * In case of infinite nesting started
                 */
                if ($nesting > 100) {
                    throw new LoopedNestingException();
                }

                try {
                    $astSequence[] = $this->group($allowedGroup, $rewind, $nesting + 1);

                } catch (ParserSequenceUnmatchedException $exception) {
                    $lastException = $exception;
                }

                if ($lastException === null) {
                    return $astSequence;
                }
            }

            throw new $lastException;
        }

        if (isset($group['sequence'])) {

            $rewind->transaction();

            $accumulated = [];

            foreach ($group['sequence'] as $index => $element) {

                $optional = false;

                if (mb_substr($element, 0, 1, 'UTF-8') == '?') {
                    $optional = true;
                    $element = mb_substr($element, 1);
                }

                /**
                 * In case of element is group, recursively parse group
                 */
                if (isset($this->groups[$element])) {

                    try {
                        $accumulated = $this->group($element, $rewind, $nesting + 1);

                    } catch (ParserSequenceUnmatchedOnFirstElementException $e) {
                        if ($optional) {
                            continue;
                        }

                        throw $e;
                    }
                    continue;
                }

                $lexem = $rewind->current();

                if ($element != $lexem->name) {

                    $message = 'Unexpected ' . $lexem->name . ', expected ' . $element;

                    if ($index == 0) {
                        throw new ParserSequenceUnmatchedOnFirstElementException($message);
                    } else {
                        throw new ParserSequenceUnmatchedException($message);
                    }
                }

                $rewind->next();
            }

            $rewind->commit();

            if (!isset($group['callback'])) {
                throw new GrammaryException('Undefined callback in some group where sequence is represented');
            }

            return $astSequence[] = $group['callback']($accumulated);
        }

        throw new GrammaryException('Corrupted group: no OR, SEQUENCE sections were represented: ' . $groupName);
    }

    /**
     * @param Lexem[] $lexems
     * @return ASTNode[]
     * @throws \Exception
     */
    public function parse(array $lexems) : array
    {
        return $this->group(
            $this->config['startFrom'],
            new LexemsRewindMachine($lexems)
        );
    }
}