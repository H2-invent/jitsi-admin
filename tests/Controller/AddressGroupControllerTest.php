<?php

namespace App\Tests\Controller;

use App\Entity\AddressGroup;
use App\Repository\AddressGroupRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AddressGroupControllerTest extends WebTestCase
{
    public function testNewAjaxCreatesGroup(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $member = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/room/address/group/new');
        $form = $crawler->selectButton('Speichern')->form();
        $values = $form->getPhpValues();
        $values['address_group']['name'] = 'Ajax Gruppe';
        $values['address_group']['member'] = [(string)$member->getId()];

        $client->request('POST', '/room/address/group/new-ajax', $values);

        $this->assertResponseIsSuccessful();
        $this->assertNotNull(self::getContainer()->get(AddressGroupRepository::class)->findOneBy(['name' => 'Ajax Gruppe']));
    }

    public function testNewAjaxRejectsDuplicateName(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/room/address/group/new');
        $form = $crawler->selectButton('Speichern')->form();
        $values = $form->getPhpValues();
        $values['address_group']['name'] = 'Testgruppe';

        $client->request('POST', '/room/address/group/new-ajax', $values);

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }

    public function testRemoveDeletesGroup(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $group = self::getContainer()->get(AddressGroupRepository::class)->findOneBy(['name' => 'Testgruppe']);
        $groupId = $group->getId();
        $client->loginUser($user);

        $client->request('GET', '/room/address/group/remove?id=' . $groupId);

        $this->assertResponseRedirects('/room/dashboard');
        $this->assertNull(self::getContainer()->get(AddressGroupRepository::class)->find($groupId));
    }

    public function testRemoveAjaxDeletesGroup(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $group = self::getContainer()->get(AddressGroupRepository::class)->findOneBy(['name' => 'Testgruppe']);
        $groupId = $group->getId();
        $client->loginUser($user);

        $client->request('POST', '/room/address/group/remove-ajax?id=' . $groupId);

        $this->assertResponseIsSuccessful();
        $this->assertNull(self::getContainer()->get(AddressGroupRepository::class)->find($groupId));
    }

    public function testPersistAddressGroupStoresIndexer(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $member = $userRepo->findOneBy(['email' => 'test@local3.de']);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/room/address/group/new');
        $form = $crawler->selectButton('Speichern')->form();
        $values = $form->getPhpValues();
        $values['address_group']['name'] = 'Index Gruppe';
        $values['address_group']['member'] = [(string)$member->getId()];

        $client->request('POST', '/room/address/group/new-ajax', $values);

        $this->assertResponseIsSuccessful();
        /** @var AddressGroup $group */
        $group = self::getContainer()->get(AddressGroupRepository::class)->findOneBy(['name' => 'Index Gruppe']);
        $this->assertNotNull($group);
        $this->assertNotEmpty($group->getIndexer());
    }
}
