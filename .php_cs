<?php

$finder = Symfony\CS\Finder::create()
    ->exclude('vendor')
    ->exclude('app')
    ->in(__DIR__)
;

return Symfony\CS\Config::create()
    ->fixers([
        'newline_after_open_tag',
        'no_empty_comment',
        'no_useless_return',
        'ordered_use',
        'php_unit_construct',
        'phpdoc_order',
        'short_array_syntax',
    ])
    ->finder($finder)
;
