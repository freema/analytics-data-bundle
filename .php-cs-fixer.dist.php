<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude(['vendor', 'var', 'old'])
;

$config = new PhpCsFixer\Config();
return $config->setRules([
    '@PSR12' => true,
    'array_syntax' => ['syntax' => 'short'],
    'ordered_imports' => ['sort_algorithm' => 'alpha'],
    'no_unused_imports' => true,
    'declare_strict_types' => true,
    'strict_param' => true,
    'no_superfluous_phpdoc_tags' => true,
    'native_function_invocation' => ['include' => ['@compiler_optimized']],
    'fully_qualified_strict_types' => true,
    'global_namespace_import' => [
        'import_classes' => false,
        'import_constants' => false,
        'import_functions' => false,
    ],
    'phpdoc_align' => true,
    'phpdoc_indent' => true,
    'phpdoc_separation' => true,
    'phpdoc_to_comment' => true,
    'phpdoc_types' => true,
    'single_quote' => true,
])
    ->setFinder($finder)
;