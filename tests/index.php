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

    $a = 1 + 3; 
    $b = 31 + 11 + 21 - 11 + 25;
    
    if ($a + $b - 11 > 10) {
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
                null
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
        'callback' => function($elements) {
            return new \Dekor\PhpSyntaxTreeBuilder\ASTNode(
                'statements',
                null
            );
        }
    ],
    'g:if' => [
        'sequence' => [
            'IF', '(', 'g:expression', ')', '{', 'g:statements', '}', 'ELSE', '{', 'g:statements', '}',
        ],
        'callback' => function($elements) {
            return new \Dekor\PhpSyntaxTreeBuilder\ASTNode(
                'statements',
                null
            );
        }
    ],
    'g:var_assign' => [
        'sequence' => [
            'VAR', '=', 'g:expression', ';',
        ],
        'callback' => function($elements) {
            return new \Dekor\PhpSyntaxTreeBuilder\ASTNode(
                'var_assign',
                null
            );
        }
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
            'g:_expression',
        ],
        'finishWhenChildParsingUnmatches' => 'g:_expression',
        'callback' => function($elements) {
            return new \Dekor\PhpSyntaxTreeBuilder\ASTNode(
                'statements',
                null
            );
        }
    ],
    'g:_expression' => [
        'sequence' => [
            'g:math_operator',
            'g:scalar_or_var',
            '?g:_expression',
        ],
        'callback' => function($elements) {
            return new \Dekor\PhpSyntaxTreeBuilder\ASTNode(
                'statements',
                null
            );
        }
    ],
    'g:math_operator' => [
        'or' => ['g:math_+', 'g:math_-', 'g:math_*', 'g:math_/'],
    ],
    'g:math_+' => [
        'sequence' => ['+'],
        'callback' => function($elements) {
            return new \Dekor\PhpSyntaxTreeBuilder\ASTNode(
                'statements',
                null
            );
        }
    ],
    'g:math_-' => [
        'sequence' => ['-'],
        'callback' => function($elements) {
            return new \Dekor\PhpSyntaxTreeBuilder\ASTNode(
                'statements',
                null
            );
        }
    ],
    'g:math_*' => [
        'sequence' => ['*'],
        'callback' => function($elements) {
            return new \Dekor\PhpSyntaxTreeBuilder\ASTNode(
                'statements',
                null
            );
        }
    ],
    'g:math_/' => [
        'sequence' => ['/'],
        'callback' => function($elements) {
            return new \Dekor\PhpSyntaxTreeBuilder\ASTNode(
                'statements',
                null
            );
        }
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
                null
            );
        },
    ],
    'g:expression_scalar_num' => [
        'sequence' => ['NUMBER'],
        'callback' => function($elements) {
            return new \Dekor\PhpSyntaxTreeBuilder\ASTNode(
                'scalar_number',
                null
            );
        },
    ],
    'g:expression_scalar_string' => [
        'sequence' => ['STRING'],
        'callback' => function($elements) {
            return new \Dekor\PhpSyntaxTreeBuilder\ASTNode(
                'scalar_string',
                null
            );
        },
    ],
]);

var_dump($parser->parse($lexems));