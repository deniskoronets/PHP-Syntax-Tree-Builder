<?php

namespace Dekor\PhpSyntaxTreeBuilder;

use Dekor\PhpSyntaxTreeBuilder\Exceptions\ParserSequenceUnmatchedException;

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
     * @return ASTNode[]
     * @throws ParserSequenceUnmatchedException
     */
    public function group($groupName, LexemsRewindMachine $rewind, $nesting = 0)
    {
        $astSequence = [];

        if (!isset($this->groups[$groupName])) {
            throw new \Exception('Group ' . $groupName . ' doesnt exist. Check your parser config');
        }

        if ($groupName == 'g:print') {
            $c = 1;
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
                if ($nesting > 30) {
                    $nesting = 0;
                    return;
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

            foreach ($group['sequence'] as $element) {

                /**
                 * In case of element is group, recursively parse group
                 */
                if (isset($this->groups[$element])) {
                    $accumulated = $this->group($element, $rewind, $nesting + 1);
                    continue;
                }

                $lexem = $accumulated[] = $rewind->current();

                if ($element != $lexem->name) {
                    throw new ParserSequenceUnmatchedException('Unexpected ' . $lexem->name . ', expected ' . $element);
                }

                $rewind->next();
            }

            $rewind->commit();

            if (!isset($group['callback'])) {
                throw new \Exception('Undefined callback in some group where sequence is represented');
            }

            return $astSequence[] = $group['callback']($accumulated);
        }

        throw new \Exception('Corrupted group: no OR, SEQUENCE sections were represented: ' . $groupName);
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