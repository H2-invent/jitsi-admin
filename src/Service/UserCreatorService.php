<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class UserCreatorService
{
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function createUser($email, $userName, $firstName = null, $lastName = null): User
    {
        $user = $this->em->getRepository(User::class)->findOneBy(array('username' => $userName));
        if (!$user) {
            $user = new User();
            $user->setCreatedAt(new \DateTime())
                ->setUsername($userName)
                ->setLastName($lastName)
                ->setFirstName($firstName)
                ->setEmail($email)
                ->setRegisterId(md5(uniqid('ksdjhfkhsdkjhjksd', true)))
                ->setPassword('123')
                ->setUid(md5(uniqid()));
            $this->em->persist($user);
            $this->em->flush();
        }
        return $user;
    }
}