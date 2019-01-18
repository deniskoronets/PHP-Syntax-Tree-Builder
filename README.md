# PHP-Syntax-Tree-Builder
This library allows you to build your custom grammar left-right, RD (recursive descent) parser by describing lexing/parsing rules.
It builds on <a href="https://en.wikipedia.org/wiki/Finite-state_machine">Finite-state machine</a> and gives you an instance of <a href="https://en.wikipedia.org/wiki/Abstract_syntax_tree">Abstract syntax tree</a>.

Inspired by lex/yacc for c/c++ *(but not the same!)*.

## Installation
```
composer install dekor/php-syntax-tree-builder 
```

## How it works?
Building grammar takes 2 main steps:
1) lexing - splitting origin file into language lexems.
2) parser - using finite-state machine, parsing lexems sequence by some grammary rules.

### Lexing
This part is very simple. All you need is to describe pairs regex => lexem name.
Here is a simple list if lexer:
```php
$lexer = new \Dekor\PhpSyntaxTreeBuilder\Lexer([
    '[\s\t\n]+' => '',    
    '\;' => ';',
    '=' => '=',
    '\$([a-z]+)' => 'VAR',
    'if' => 'IF',    
    '([0-9]+)' => 'NUMBER',
    '\"(.*)\"' => 'STRING',
    'print' => 'PRINT',
    '(\+|\-|\*\/)' => 'MATH_OPERATOR',    
]);

$lexems = $lexer->parse($pathToFile);
```

The result of `parse` method is an array of lexems. In case when lexer can't determine 
current symbol sequence as any of described lexems, it throws `LexerAnalyseException`.
This could be catched and processed. 

### Parsing
This part is more complex. When you describing construction, you need to have some boot 
construction which will be the point, at which finite-state machine starts:

```php
$parser = new \Dekor\PhpSyntaxTreeBuilder\Parser([
    'startFrom' => 'g:php',
], [
    'g:php' => [
        'sequence' => [
            'OPENING_PHP_TAG', 'g:statements',
        ],
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
    ...
]);    
```

As you can see in this sample, in the config we say that it will start parsing from g:php.
Why do we add `g:` prefix? Actually, this have only visual purposes: it can be easily 
understanded, when you mention group and when - token.

Lets go forward. Here we can see `sequence` and `or`. In each group you can have 
only one of that. What does it mean? In case, when we 
have `sequence`, the parser will foreach through each element of sequence and try to 
parse lexems in this order. In case when we have group mentioned within sequence,
it will be recursively parsed. This means that parser will check lexems for opening php
lexem, then it will parse statements. Statements consists of another sequence: `statement` and
`?statements`. Statement here is a single statement which you can see below, `?statements` means
that after single statement, there will be another statement. But, the `?` symbol tells parser
that it is optional, which means that parser will try to parse group, in case of unseccess it will
finish continue with the following element of the sequence.
Here below, we can see `or`. This allows parser to try parsing of each group from the list.
Once parser matches construction, it continues with the group that works in current statement.
This construction allows you to split your alghoritm in multiple branches. FYI, it trying
parsing from left to right.

#### What can go wrong?
In some cases you may have recursive grammary which may be looped infinitely. 
As this parser is left-right one, here takes place <a href="https://en.wikipedia.org/wiki/Left_recursion">Left recursion</a>.
You may check the article in order to resolve this. This particular situation is resolved for
formula parsing in the `/demo/php/demo.php`. Please, check the grammar section.

## Usage
Here is a simple structure of usage:

```php
<?php

require __DIR__ . '/../../vendor/autoload.php';

$lexer = new \Dekor\PhpSyntaxTreeBuilder\Lexer([
    '[\s\t\n]+' => '',    
    '([0-9]+)' => 'NUMBER',
    '\"(.*)\"' => 'STRING',
    // lexing rules
]);

$lexems = $lexer->parse($inFile);

$parser = new \Dekor\PhpSyntaxTreeBuilder\Parser([
    'startFrom' => 'g:statements',
], [    
    'g:statements' => [
        'sequence' => [
            'g:statement',
            '?g:statements',
        ],
    ],
    'g:statement' => [
        'or' => [
            'g:string',
            'g:number',
        ],
    ],
    'g:string' => [
        'sequence' => [
            'STRING',        
        ],
    ],
    'g:number' => [
        'sequence' => [
            'NUMBER',        
        ],
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
```
    
Try our all the demo in order to get some good understanding on how this works and how it can be applied.

## ASTNode

You can see class `Dekor\PhpSyntaxTreeBuilder\ASTNode`. It is our implementation of Abstract Syntax Tree node.
`Parser::parse(Lexems[] $lexems)` usually returns `ASTNode` instance (may return array of nodes).
You may try and out.json in our demos in order to get some understanding on how it look like.

In case you have your own ASTNode implementation or classes composition (different classes for each node type),
you may override node making by adding such construction:
```php
'g:php' => [
    'sequence' => [
        'OPENING_PHP_TAG', 'g:statements',
    ],
    'callback' => function($lexems, $subGroupsSequence) {
        return new \Dekor\PhpSyntaxTreeBuilder\ASTNode('PHP', $subGroupsSequence, $lexems);
    }
],
```  
How it works? Right after the sequence parsed, the callback (if it exists) will be executed.
It should always return node object. `$subGroupsSequence` is an array with parsed child groups. In this example,
it's `g:statements`. `$lexems` here is a simple list of lexems which were parsed in this sequence (excluding nested groups lexems).

## Have any ideas/need assistance?
You can contact me by email: CONCAT("denis", "koronets", "@", "woo.zp.ua") // sorry for obfuscation, some kind of spam protection :)