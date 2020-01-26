<?php
namespace Tests\Twig;

use Twig\Test\IntegrationTestCase;
use Polyglot\Extensions\Twig\TwigFilters;

class IntegrationTest extends IntegrationTestCase
{
    public function getExtensions()
    {
        return [
            new TwigFilters(),
        ];
    }

    public function getFixturesDir()
    {
        return __DIR__.'/Fixtures/';
    }
}