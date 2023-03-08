<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppTwigExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            // If your filter generates SAFE HTML, you should add a third
            // parameter: ['is_safe' => ['html']]
            // Reference: https://twig.symfony.com/doc/2.x/advanced.html#automatic-escaping
            new TwigFilter('substr', [$this, 'substr']),
            new TwigFilter('transformeJour', [$this, 'transformeJour']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            //new TwigFunction('function_name', [$this, 'doSomething']),
        ];
    }

    public function substr($input)
    {
        return substr($input, 0, 80);
    }

    public function transformeJour($input){
        $j = [
            "1"=>"Lundi","2"=>"Mardi","3"=>"Mercredi","4"=>"Jeudi","5"=>"Vendredi","6"=>"Samedi"
        ];

        foreach ($j as $d=>$n){
            $input = str_replace($d,$n, $input);
        }
        $input = str_replace(",",", ",$input);

        return $input;
    }
}
