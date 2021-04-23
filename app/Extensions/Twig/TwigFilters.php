<?php
namespace App\Extensions\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TwigFilters extends AbstractExtension {
    public function getFilters()
    {
        return [
            new TwigFilter('preserveSpaces', [$this, 'preserveSpaces'], ['pre_escape' => 'html', 'is_safe' => ['html']]),
        ];
    }

    public function preserveSpaces($text)
    {
        return preg_replace_callback(['/^([ ]*)/', '/([ ]*)$/'], function ($matches) {
            return str_repeat('&nbsp;', strlen($matches[1]));
        }, $text);
    }
}