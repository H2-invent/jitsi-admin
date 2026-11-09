<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class User extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);

        // USer mit Keycloak ID
        $user = new \App\Entity\User();
        $user->setEmail('test@local123.de');
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setKeycloakId('d0560008-4331-4463-8adb-6671f9feefa1');
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setRegisterId('d5415165-9fc6-4977-8c2a-4e40ba293c3e');
        $user->setSpezialProperties(['ou' => 'Test1', 'departmentNumber' => '1234',]);
        $user->setTimeZone('Europe/Berlin');
        $user->setUuid('638c1d92-0726-4689-8686-2204052f28d7');
        $user->setUsername('test@local123.de');
        $user->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($user);

        // USer ohne Keycloak ID, also einfach eingeladen
        $user = new \App\Entity\User();
        $user->setEmail('testNoId@local.de');
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setFirstName('Test');
        $user->setLastName('User No ID');
        $user->setRegisterId('1b9e5559-c26a-4f98-b2ab-b25940480ab3');
        $manager->persist($user);

        $manager->flush();
    }
}
