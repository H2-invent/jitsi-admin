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

class Name extends AbstractExtension
{
    private $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('nameOfUser', [$this, 'nameOfUser']),
        ];
    }
    public function nameOfUser(User $user)
    {
        return $user->getFormatedName($this->parameterBag->get('laf_showName'));
    }
}