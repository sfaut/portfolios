<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Lignes initiales de l'éditeur de répartition analytique (<x-analytic-lines>) d'une page, lues dans
 * son x-data, où Js::from() les écrit en JSON.parse('…'), dans une chaîne JSON échappée.
 *
 * @param  string  $html  Page qui contient l'éditeur.
 * @return list<array{analytic_axis_id: string|int, percentage: string}>
 */
function editorLines(string $html): array
{
    expect($html)->toMatch("/analyticLines\\(JSON\\.parse\\('([^']*)'\\)/");

    preg_match("/analyticLines\\(JSON\\.parse\\('([^']*)'\\)/", $html, $matches);

    return json_decode(json_decode("\"{$matches[1]}\""), associative: true)['lines'];
}
