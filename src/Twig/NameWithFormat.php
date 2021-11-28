<?php
// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Checklist;
use App\Entity\MyUser;
use App\Entity\User;
use App\Service\MessageService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use function GuzzleHttp\Psr7\str;

class NameWithFormat extends AbstractExtension
{

    public function getFunctions()
    {
        return [
            new TwigFunction('nameOfUserwithFormat', [$this, 'nameOfUserwithFormat']),
        ];
    }

    public function nameOfUserwithFormat(User $user,$string)
    {
        return $user->getFormatedName($string);
    }
}