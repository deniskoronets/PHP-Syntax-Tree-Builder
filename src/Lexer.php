<?php

namespace Dekor\PhpSyntaxTreeBuilder;

use Dekor\PhpSyntaxTreeBuilder\Exceptions\LexerAnalyseException;

class Lexer
{
    /**
     * Associative list of regex => condition to do
     * @var array
     */
    public $rules = [];

    /**
     * @var int
     */
    private $line = 1;

    /**
     * Lexer constructor.
     * @param array $rules
     */
    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }

    /**
     * @param string $in
     * @return array
     * @throws LexerAnalyseException
     */
    private function match(string $in) : array
    {
        foreach ($this->rules as $pattern => $lexemType) {
            $matches = [];
            if (preg_match('/^' . $pattern . '/', $in, $matches)) {

                $this->line += substr_count($matches[0], "\n");

                return [
                    'offset' => mb_strlen($matches[0], 'UTF-8'),
                    'lexem' => ($lexemType != '') ? new Lexem($lexemType, $matches[1] ?? '', $this->line) : null,
                ];
            }
        }

        throw new LexerAnalyseException('Unable to parse at: ' . mb_substr($in, 0, 50) . '...');
    }

    /**
     * Parse income string and returns a list of resulting lexems
     * @param string $in
     * @return array
     * @throws LexerAnalyseException
     */
    public function parse(string $in) : array
    {
        $offset = 0;
        $length = mb_strlen($in, 'UTF-8');

        $lexems = [];

        while (($offset + 1) <= $length) {
            $result = $this->match(mb_substr($in, $offset));
            $offset += $result['offset'];

            if (!empty($result['lexem'])) {
                $lexems[] = $result['lexem'];
            }
        }

        return $lexems;
    }
}