<?php

namespace App\Tests\Protocol;

use App\Entity\ProtocolTemplate;
use App\Entity\Rooms;
use App\Entity\RoomStatus;
use App\Entity\RoomStatusParticipant;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\Protocol\CreateProtocolService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProtocolTemplateAndDownloadTest extends WebTestCase
{
    /** 1x1 transparent PNG */
    private const string PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    /** @var list<string> */
    private array $createdFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        parent::tearDown();
    }

    private function login(KernelBrowser $client, string $username = 'test@local.de'): void
    {
        $user = static::getContainer()->get(UserRepository::class)->findOneByUsername($username);
        $client->loginUser($user);
    }

    private function getRunningRoom(): Rooms
    {
        return static::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'Running Room']);
    }

    private function logoDirectory(): string
    {
        return static::getContainer()->getParameter('kernel.project_dir') . '/public/uploads/images/logo';
    }

    public function testTemplateFormStoresLogoAndShowsItWhenReopened(): void
    {
        $client = static::createClient();
        $this->login($client);
        $server = $this->getRunningRoom()->getServer();
        $url    = '/admin/server/' . $server->getId() . '/protocol/template';

        $logoPath = sys_get_temp_dir() . '/protocol-logo-test.png';
        file_put_contents($logoPath, base64_decode(self::PNG));
        $filesBefore = glob($this->logoDirectory() . '/*') ?: [];

        $crawler = $client->request('GET', $url);
        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('img[alt="Logo"]');

        $form = $crawler->selectButton('Speichern')->form();
        $form['protocol_template[header]'] = '<h1>ACME GmbH</h1><script>alert(1)</script>';
        $form['protocol_template[footer]'] = '<p>Musterstraße 1</p>';
        $form['protocol_template[logoFile][file]']->upload($logoPath);
        $client->submit($form);
        self::assertResponseRedirects('/room/dashboard');

        $newFiles           = array_values(array_diff(glob($this->logoDirectory() . '/*') ?: [], $filesBefore));
        $this->createdFiles = $newFiles;
        self::assertCount(1, $newFiles, 'Logo must be stored in the logo directory');

        /** @var ProtocolTemplate $template */
        $template = static::getContainer()->get(EntityManagerInterface::class)
            ->getRepository(ProtocolTemplate::class)->findOneBy(['server' => $server]);
        self::assertNotNull($template);
        self::assertSame(basename($newFiles[0]), $template->getLogo()->getName());
        self::assertSame('protocol-logo-test.png', $template->getLogo()->getOriginalName());
        self::assertSame('image/png', $template->getLogo()->getMimeType());
        self::assertStringNotContainsString('<script', $template->getHeader());
        self::assertStringContainsString('<h1>ACME GmbH</h1>', $template->getHeader());

        // Reopen the modal: the saved logo is shown as preview.
        $client->request('GET', $url);
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('img[alt="Logo"][src$="/uploads/images/logo/' . basename($newFiles[0]) . '"]');
        self::assertSelectorTextContains('.modal-body', 'protocol-logo-test.png');

        // The protocol PDF HTML embeds the logo and the sanitized header/footer.
        $html = static::getContainer()->get(CreateProtocolService::class)
            ->createProtocolHtml($this->getRunningRoom(), null);
        self::assertStringContainsString('src="data:image/png;base64,', $html);
        self::assertStringContainsString('ACME GmbH', $html);
        self::assertStringContainsString('Musterstraße 1', $html);
        self::assertStringNotContainsString('alert(1)', $html);
    }

    public function testTemplateModalIsOnlyForServerAdministrator(): void
    {
        $client = static::createClient();
        $this->login($client, 'test@local3.de');
        $server = $this->getRunningRoom()->getServer();

        $client->request('GET', '/admin/server/' . $server->getId() . '/protocol/template');
        self::assertResponseRedirects('/room/dashboard');
    }

    public function testProtocolListsEveryEntryAndLeave(): void
    {
        $client = static::createClient();
        $this->login($client);
        $room = $this->getRunningRoom();
        $em   = static::getContainer()->get(EntityManagerInterface::class);

        $status = new RoomStatus()
            ->setRoom($room)
            ->setCreated(true)
            ->setJitsiRoomId('protocol-test')
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable())
            ->setRoomCreatedAt(new \DateTimeImmutable('2026-01-10 09:00:00'))
            ->setDestroyed(true)
            ->setDestroyedAt(new \DateTimeImmutable('2026-01-10 11:00:00'));
        $em->persist($status);

        $visits = [
            ['Rejoin Person', '09:05:00', '09:20:00', 'a'],
            ['Rejoin Person', '09:30:00', '10:00:00', 'b'],
            ['Other Person', '09:10:00', '10:50:00', 'c'],
        ];
        foreach ($visits as [$name, $in, $out, $id]) {
            $participant = new RoomStatusParticipant()
                ->setParticipantName($name)
                ->setParticipantId('protocol-test-' . $id)
                ->setEnteredRoomAt(new \DateTimeImmutable('2026-01-10 ' . $in))
                ->setLeftRoomAt(new \DateTimeImmutable('2026-01-10 ' . $out))
                ->setInRoom(false)
                ->setRoomStatus($status);
            $status->addRoomStatusParticipant($participant);
            $em->persist($participant);
        }
        $em->flush();

        $session = static::getContainer()->get(CreateProtocolService::class)->buildSession($status, null);
        self::assertSame('02:00:00', $session['duration']);
        self::assertCount(2, $session['participants']);
        self::assertSame('Rejoin Person', $session['participants'][0]['name']);
        self::assertCount(2, $session['participants'][0]['visits']);
        self::assertSame('00:15:00', $session['participants'][0]['visits'][0]['duration']);
        self::assertSame('00:30:00', $session['participants'][0]['visits'][1]['duration']);
        self::assertSame('00:45:00', $session['participants'][0]['totalDuration']);
        self::assertSame('Other Person', $session['participants'][1]['name']);
        self::assertCount(1, $session['participants'][1]['visits']);

        $client->request('GET', '/room/report/' . $room->getId() . '/download');
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/pdf');
        self::assertStringStartsWith('%PDF', (string)$client->getInternalResponse()->getContent());
        self::assertStringContainsString('.pdf', (string)$client->getResponse()->headers->get('Content-Disposition'));
    }

    public function testDownloadIsNotAllowedForOtherUsers(): void
    {
        $client = static::createClient();
        $this->login($client, 'test@local3.de');
        $room = $this->getRunningRoom();

        $client->request('GET', '/room/report/' . $room->getId() . '/download');
        self::assertResponseStatusCodeSame(404);
    }

    public function testReportModalShowsDownloadButton(): void
    {
        $client = static::createClient();
        $this->login($client);
        $room = $this->getRunningRoom();

        $client->request('GET', '/room/report/' . $room->getId());
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('a[href="/room/report/' . $room->getId() . '/download"]');
    }
}
