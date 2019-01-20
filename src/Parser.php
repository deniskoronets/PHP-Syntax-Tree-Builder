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
     * Debug stack for the latest parsing
     * @var array
     */
    public $stack = [];

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
     * @return array
     */
    public function getLatestParseStack() : array
    {
        return $this->stack;
    }

    /**
     * @param $groupName
     * @param LexemsRewindMachine $rewind
     * @param int $nesting
     * @return ASTNode
     * @throws GrammaryException
     * @throws LoopedNestingException
     * @throws ParserSequenceUnmatchedException
     * @throws ParserSequenceUnmatchedOnFirstElementException
     */
    public function group($groupName, LexemsRewindMachine $rewind, $nesting = 0)
    {
        if (!isset($this->groups[$groupName])) {
            throw new GrammaryException('Group ' . $groupName . ' doesn\'t exist. Check your parser config');
        }

        $this->stack[] = [
            'group' => $groupName,
            'rewindPosition' => [$rewind->current()->name, $rewind->current()->value],
        ];

        if ($rewind->ended()) {
            throw new ParserSequenceUnmatchedException('Lexemes has ended', null);
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
                    return $this->group($allowedGroup, $rewind, $nesting + 1);

                } catch (ParserSequenceUnmatchedException $exception) {
                    // @todo: check the case when some of group (non-last) started parsing, but thrown an exception
                    $lastException = $exception;
                }
            }

            throw $lastException;
        }

        if (isset($group['sequence'])) {

            $rewind->transaction();

            $accumulated = [
                'grouped' => [],
                'series' => [],
            ];

            $lexems = [];

            foreach ($group['sequence'] as $index => $element) {

                $optional = false;

                if (mb_substr($element, 0, 1, 'UTF-8') == '?') {
                    $optional = true;
                    $element = mb_substr($element, 1, null, 'UTF-8');
                }

                /**
                 * In case of element is group, recursively parse group
                 */
                if (isset($this->groups[$element])) {

                    try {
                        $accumulated['grouped'][$element] =
                        $accumulated['series'][] = $this->group($element, $rewind, $nesting + 1);

                    } catch (ParserSequenceUnmatchedOnFirstElementException $e) {

                        /**
                         * When subgroup is optional and parsing failed on the first lexem
                         */
                        if ($optional) {
                            $accumulated['grouped'][$element] =
                            $accumulated['series'][] = null;
                            continue;
                        }

                        throw $e;
                    }

                    continue;
                }

                $lexem = $lexems[] = $rewind->current();

                if ($element != $lexem->name) {

                    $message = 'Unexpected ' . $lexem->name . ', expected ' . $element . ' at line: ' . $lexem->line;

                    if ($index == 0) {
                        throw new ParserSequenceUnmatchedOnFirstElementException($message, $lexem);
                    } else {
                        throw new ParserSequenceUnmatchedException($message, $lexem);
                    }
                }

                $rewind->next();
            }

            $rewind->commit();

            if (!empty($group['callback'])) {

                if (!is_callable($group['callback'])) {
                    throw new GrammaryException('Sequence callback for group ' . $groupName . ' is not callable');
                }

                return $group['callback']($lexems, $accumulated['series']);
            }

            return new ASTNode(
                $groupName,
                $accumulated['grouped'],
                $lexems
            );
        }

        throw new GrammaryException('Corrupted group: no OR, SEQUENCE sections were represented: ' . $groupName);
    }

    /**
     * @param Lexem[] $lexems
     * @return ASTNode|ASTNode[]
     * @throws \Exception
     */
    public function parse(array $lexems)
    {
        return $this->group(
            $this->config['startFrom'],
            new LexemsRewindMachine($lexems)
        );
    }
}