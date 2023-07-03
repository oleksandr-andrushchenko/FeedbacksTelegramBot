<?php


// Provide at least three ways to extract the column “designed_by"
// Expected result: an array ['James Gosling', 'Guido van Rossum', 'Rasmus Lerdorf']

$languages = [
    'java' => [
        'first_release' => 1995,
        'designed_by' => 'James Gosling',
    ],
    'python' => [
        'first_release' => 1991,
        'designed_by' => 'Guido van Rossum',
    ],
    'php' => [
        'first_release' => 1995,
        'designed_by' => 'Rasmus Lerdorf',
    ],
];


function extract1(array $input, string $key): array
{
    return array_values(array_map(fn (array $inner) => $inner[$key], $input));
}

function extract2(array $input, string $key): array
{
    $output = [];

    foreach ($input as $inner) {
        $output[] = $inner[$key];
    }

    return $output;
}

function extract3(array $input, string $key): array
{
    return array_column($input, $key);
}

var_dump(
    extract1($languages, 'designed_by'),
    extract2($languages, 'designed_by'),
    extract3($languages, 'designed_by'),
);