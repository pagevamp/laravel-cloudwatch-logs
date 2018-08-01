#!/usr/bin/env php
<?php

return PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules(array(
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'combine_consecutive_unsets' => true,
        'no_extra_consecutive_blank_lines' => ['break', 'continue', 'extra', 'return', 'throw', 'use', 'parenthesis_brace_block', 'square_brace_block', 'curly_brace_block'],
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_class_elements' => true,
        'phpdoc_add_missing_param_annotation' => true,
        '@PSR2' => true,
        'array_syntax' => array('syntax' => 'short'),
        'no_closing_tag' => true,
        'phpdoc_summary' => true
    ))
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->exclude('vendor')
            ->name('*.php')
            ->in(__DIR__)
    );

