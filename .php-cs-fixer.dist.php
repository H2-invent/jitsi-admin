<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
    ->notPath([
        'config/bundles.php',
        'config/reference.php',
    ])
;

return (new PhpCsFixer\Config())
    ->setRules([
        // Basic ruleset is PSR 12
        '@PSR12' => true,
        // Braces configuration
        'braces_position' => [
            'classes_opening_brace' => 'next_line_unless_newline_at_signature_end',
            'functions_opening_brace' => 'next_line_unless_newline_at_signature_end',
            'anonymous_functions_opening_brace' => 'same_line',
            'control_structures_opening_brace' => 'next_line_unless_newline_at_signature_end',
        ],
        'control_structure_braces' => true,
        'control_structure_continuation_position' => [
            'position' => 'same_line',
        ],
        // Concat space
        'concat_space' => ['spacing' => 'one'],
        // // Short array syntax
        'array_syntax'                                     => ['syntax' => 'short'],
        // Align elements in multiline array and variable declarations on new lines below each other
        'binary_operator_spaces'                           => ['operators' => ['=>' => 'align_single_space_minimal', '=' => 'align', '??=' => 'align']],
        // Remove unused imports
        'no_unused_imports'                                => true,
        // // Alpha order imports
        'ordered_imports'                                  => ['imports_order' => ['class', 'function', 'const'], 'sort_algorithm' => 'alpha'],
        // // There should not be useless else cases
        'no_useless_else'                                  => true,
        // Using isset($var) && multiple times should be done in one call.
        'combine_consecutive_issets'                       => true,
        // Calling unset on multiple items should be done in one call
        'combine_consecutive_unsets'                       => true,
    ])
    ->setFinder($finder)
;
