<?php

require __DIR__ . '/../../vendor/autoload.php';

$lexer = new \Dekor\PhpSyntaxTreeBuilder\Lexer([
    '[\s\t\n]+' => '',
    '\<\?php' => 'OPENING_PHP_TAG',
    '\;' => ';',
    '=' => '=',
    '\$([a-z]+)' => 'VAR',
    'if' => 'IF',
    'else' => 'ELSE',
    '([0-9]+)' => 'NUMBER',
    '\"(.*)\"' => 'STRING',
    'print' => 'PRINT',
    '(\+|\-|\*\/)' => 'MATH_OPERATOR',
    '\(' => '(',
    '\)' => ')',
    '\{' => '{',
    '\}' => '}',
    '\>' => '>',
    '\<' => '<',
]);

$lexems = $lexer->parse(file_get_contents(__DIR__ . '/file_in.php'));

$parser = new \Dekor\PhpSyntaxTreeBuilder\Parser([
    'startFrom' => 'g:php',
], [
    'g:php' => [
        'sequence' => [
            'OPENING_PHP_TAG', 'g:statements',
        ],
        'callback' => function($lexems, $subGroupsSequence) {
            return new \Dekor\PhpSyntaxTreeBuilder\ASTNode('PHP', $subGroupsSequence, $lexems);
        }
    ],
    'g:statements' => [
        'sequence' => [
            'g:statement',
            '?g:statements',
        ],
    ],
    'g:statement' => [
        'or' => [
            'g:var_assign',
            'g:if',
        ],
    ],
    'g:print' => [
        'sequence' => [
            'PRINT', 'g:expression', ';'
        ],
    ],
    'g:if' => [
        'sequence' => [
            'IF', '(', 'g:expression', ')', '{', 'g:statements', '}', 'ELSE', '{', 'g:statements', '}',
        ],
    ],
    'g:var_assign' => [
        'sequence' => [
            'VAR', '=', 'g:expression', ';',
        ],
    ],
    'g:scalar_or_var' => [
        'or' => [
            'g:expression_var_usage',
            'g:expression_scalar_num',
            'g:expression_scalar_string',
        ],
    ],
    'g:expression' => [
        'sequence' => [
            'g:scalar_or_var',
            '?g:_expression',
        ],
    ],
    'g:_expression' => [
        'sequence' => [
            'MATH_OPERATOR',
            'g:scalar_or_var',
            '?g:_expression',
        ],
    ],
    'g:expression_brackets' => [
        'sequence' => ['(', 'g:expression', ')'],
    ],
    'g:expression_var_usage' => [
        'sequence' => ['VAR'],
    ],
    'g:expression_scalar_num' => [
        'sequence' => ['NUMBER'],
    ],
    'g:expression_scalar_string' => [
        'sequence' => ['STRING'],
    ],
]);

try {
    file_put_contents(
        'out.json',
        json_encode($parser->parse($lexems), JSON_PRETTY_PRINT)
    );

} catch (\Throwable $e) {
    echo get_class($e) . ': ' . $e->getMessage();
}