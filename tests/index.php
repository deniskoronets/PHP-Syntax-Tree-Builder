<?php

require __DIR__ . '/../vendor/autoload.php';

$lexer = new \Dekor\PhpSyntaxTreeBuilder\Lexer([
    '[\s\t\n]+' => '',
    '\;' => ';',
    '=' => '=',
    '\$([a-z]+)' => 'VAR',
    'if' => 'IF',
    'else' => 'ELSE',
    '([0-9]+)' => 'NUMBER',
    '\"(.*)\"' => 'STRING',
    'print' => 'PRINT',
    '\+' => '+',
    '\-' => '-',
    '\*' => '*',
    '\/' => '/',
    '\(' => '(',
    '\)' => ')',
    '\{' => '{',
    '\}' => '}',
    '\>' => '>',
    '\<' => '<',
]);

$lexems = $lexer->parse('

    $a = 1; 
    $b = (31 + 11 + 21 - (11 + 25));
    
    if (($a + $b - 11) > 10) {
        $c = 1;
    } else {
        $c = 2;
    }
    
    print $c;

');

// var_dump($lexems);

$parser = new \Dekor\PhpSyntaxTreeBuilder\Parser([
    'startFrom' => 'g:statements',
], [
    'g:statements' => [
        'sequence' => [
            'g:statement',
            'g:statements',
        ],
        'callback' => function($elements) {
            return new \Dekor\PhpSyntaxTreeBuilder\ASTNode(
                'statements',
                $elements[0]->name
            );
        }
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
        'callback' => function($elements) {
            return new \Dekor\PhpSyntaxTreeBuilder\ASTNode(
                'var_assign',
                $elements[1]->value
            );
        }
    ],
    'g:expression' => [
        'or' => [
            'g:expression_var_usage',
            'g:expression_scalar_num',
            'g:expression_scalar_string',

            'g:expression_sum',
            'g:expression_diff',
            'g:expression_mul',
            'g:expression_div',
            'g:expression_brackets',
        ],
    ],
    'g:expression_sum' => [
        'sequence' => ['g:expression', '+', 'g:expression'],
        'callback' => function($elements) {
            return new \Dekor\PhpSyntaxTreeBuilder\ASTNode(
                'sum',
                null //$elements[1]->value
            );
        },
    ],
    'g:expression_diff' => [
        'sequence' => ['g:expression', '-', 'g:expression'],
        'callback' => function($elements) {
            return new \Dekor\PhpSyntaxTreeBuilder\ASTNode(
                'diff',
                null //$elements[1]->value
            );
        },
    ],
    'g:expression_mul' => [
        'sequence' => ['g:expression', '*', 'g:expression'],
        'callback' => function($elements) {
            return new \Dekor\PhpSyntaxTreeBuilder\ASTNode(
                'mul',
                null //$elements[1]->value
            );
        },
    ],
    'g:expression_div' => [
        'sequence' => ['g:expression', '/', 'g:expression'],
        'callback' => function($elements) {
            return new \Dekor\PhpSyntaxTreeBuilder\ASTNode(
                'div',
                null //$elements[1]->value
            );
        },
    ],
    'g:expression_brackets' => [
        'sequence' => ['(', 'g:expression', ')'],
        'callback' => function($elements) {
            return new \Dekor\PhpSyntaxTreeBuilder\ASTNode(
                'brackets',
                null //$elements[1]->value
            );
        },
    ],
    'g:expression_var_usage' => [
        'sequence' => ['VAR'],
        'callback' => function($elements) {
            return new \Dekor\PhpSyntaxTreeBuilder\ASTNode(
                'var_usage',
                $elements[1]->value
            );
        },
    ],
    'g:expression_scalar_num' => [
        'sequence' => ['NUMBER'],
        'callback' => function($elements) {
            return new \Dekor\PhpSyntaxTreeBuilder\ASTNode(
                'scalar_number',
                $elements[0]->value
            );
        },
    ],
    'g:expression_scalar_string' => [
        'sequence' => ['STRING'],
        'callback' => function($elements) {
            return new \Dekor\PhpSyntaxTreeBuilder\ASTNode(
                'scalar_string',
                $elements[0]->value
            );
        },
    ],
]);

var_dump($parser->parse($lexems));