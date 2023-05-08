<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class User extends Fixture
{
    public function load(ObjectManager $manager)
    {
        // $product = new Product();
        // $manager->persist($product);

        // USer mit Keycloak ID
        $user = new \App\Entity\User();
        $user->setEmail('test@local123.de');
        $user->setCreatedAt(new \DateTime());
        $user->setKeycloakId(123456);
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setRegisterId(123456);
        $user->setSpezialProperties(['ou' => 'Test1', 'departmentNumber' => '1234',]);
        $user->setTimeZone('Europe/Berlin');
        $user->setUuid('lksdhflkjdsljflkjds');
        $user->setUsername('test@local123.de');
        $user->setCreatedAt(new \DateTime());
        $manager->persist($user);

        // USer ohne Keycloak ID, also einfach eingeladen
        $user = new \App\Entity\User();
        $user->setEmail('testNoId@local.de');
        $user->setCreatedAt(new \DateTime());
        $user->setFirstName('Test');
        $user->setLastName('User No ID');
        $user->setRegisterId(123456);
        $manager->persist($user);

        $manager->flush();
    }
}
