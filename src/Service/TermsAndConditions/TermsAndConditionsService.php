<?php

namespace App\Service\TermsAndConditions;

use App\Entity\User;
use App\Service\ThemeService;
use Doctrine\ORM\EntityManagerInterface;

class TermsAndConditionsService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ThemeService           $themeService
    )
    {
    }

    public function hasAcceptedTerms(User $user)
    {
        if ($user->isAcceptTermsAndConditions() || $this->themeService->getApplicationProperties('LAF_TERMS_AND_CONDITIONS') === '') {
            return true;
        }
        return false;
    }

    public function acceptTerms(User $user)
    {
        $user->setAcceptTermsAndConditions(true);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        return true;
    }
}
