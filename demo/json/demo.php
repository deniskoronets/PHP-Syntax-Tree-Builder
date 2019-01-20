<?php

require __DIR__ . '/../../vendor/autoload.php';

$lexer = new \Dekor\PhpSyntaxTreeBuilder\Lexer([
    '[\s\t\n]+' => '',
    '([0-9]+|\"[^\"]+\")' => 'SCALAR',
    '\{' => '{',
    '\}' => '}',
    '\[' => '[',
    '\]' => ']',
    '\:' => ':',
    '\,' => ',',
]);

$lexems = $lexer->parse(file_get_contents(__DIR__ . '/file_in.json'));

$parser = new \Dekor\PhpSyntaxTreeBuilder\Parser([
    'startFrom' => 'g:object_or_array',
], [
    'g:object_or_array' => [
        'or' => [
            'g:object',
            'g:array',
        ]
    ],
        'g:array' => [
            'sequence' => [
                '[',
                    '?g:array_element',
                ']',
            ]
        ],
            'g:array_element' => [
                'sequence' => [
                    'g:scalar_or_object', '?g:_array_element'
                ]
            ],
                'g:_array_element' => [
                    'sequence' => [
                        ',', 'g:array_element',
                    ]
                ],

        'g:object' => [
            'sequence' => [
                '{',
                    '?g:properties',
                '}',
            ],
        ],
            'g:properties' => [
                'sequence' => [
                    'SCALAR', ':', 'g:scalar_or_object', '?g:_properties'
                ]
            ],
                'g:scalar_or_object' => [
                    'or' => [
                        'g:scalar',
                        'g:object',
                        'g:object_or_array',
                    ],
                ],
                    'g:scalar' => [
                        'sequence' => [
                            'SCALAR',
                        ],
                    ],
                'g:_properties' => [
                    'sequence' => [
                        ',', 'g:properties',
                    ]
                ],
]);

try {
    file_put_contents(
        'out.json',
        json_encode($parser->parse($lexems), JSON_PRETTY_PRINT)
    );

} catch (\Throwable $e) {

    var_dump($parser->getLatestParseStack());

    echo get_class($e) . ': ' . $e->getMessage();
}