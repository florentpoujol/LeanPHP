<?php declare(strict_types=1);

use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;
use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = (new Finder())
    ->in([
        __DIR__ . '/bin',
        __DIR__ . '/library',
        __DIR__ . '/bootstrap',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->notPath([
        // note Florent: this actually doesn't work no matter what value I set here
        __DIR__ . '/library/src/Validation/Validator.php', // see line 404 ini \LeanPHP\Validation\Validator::passesParameterizedRule why it's excluded
    ])
;

return (new Config())
    ->setRules([
        '@Symfony:risky'  => true,

        'declare_strict_types' => true,
        'strict_comparison' => true,
        'strict_param' => true,
        'final_class' => true,
        'void_return' => true,
        'numeric_literal_separator' => true,

        'trailing_comma_in_multiline' => [
            'after_heredoc' => true,
            'elements' => ['arguments', 'arrays', 'match', 'parameters'],
        ],

        'phpdoc_line_span' => [
            'const' => 'multi',
            'method' => 'multi',
            'property' => 'multi',
        ],

        'fully_qualified_strict_types' => [
            'import_symbols' => true,
            'leading_backslash_in_global_namespace' => true,
        ],

        'blank_line_before_statement' => [
            'statements' => ['declare', 'return'], // Symfony only has 'return'
            // Note that this actually doesn't work for the declare(strict_types=1) lines when they are at the top of the file (which is the only pertinent place for them...)
        ],

        'concat_space' => ['spacing' => 'one'],

        'php_unit_test_case_static_method_calls' => ['call_type' => 'self'],

        // no rules with PHP CS Fixer:
        // - put promoted properties always on their own lines > no rules
        // - put argument list on multiple line when longer than 120 chars or 3 arguments > no rule

        'single_quote' => [
            'strings_containing_single_quote_chars' => false,
        ],
    ])
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setFinder($finder)
    ->setRiskyAllowed(true)
;
