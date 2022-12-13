<?php
// src/Twig/AppExtension.php
namespace App\Twig;
use App\Entity\Deputy;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DeputyTwig extends AbstractExtension
{



    public function __construct(private EntityManagerInterface $entityManager)
    {

    }

    public function getFunctions(): array
    {

        return [
            new TwigFunction('deputyIsFromLDAP', [$this, 'deputyIsFromLDAP']),
        ];
    }

    public function deputyIsFromLDAP(User $manager, User $deputy)
    {
        $dep = $this->entityManager->getRepository(Deputy::class)->findOneBy(array('manager'=>$manager,'deputy'=>$deputy));
        if ($dep && $dep->isIsFromLdap()){
            return true;
        }
        return false;
    }


}