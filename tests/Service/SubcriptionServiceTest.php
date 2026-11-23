<?php

namespace App\Tests\Service;

use App\Entity\Rooms;
use App\Entity\Subscriber;
use App\Entity\User;
use App\Repository\RoomsRepository;
use App\Repository\RoomsUserRepository;
use App\Repository\SubscriberRepository;
use App\Repository\UserRepository;
use App\Repository\WaitinglistRepository;
use App\Service\SubcriptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class SubcriptionServiceTest extends KernelTestCase
{
    private function service(): SubcriptionService
    {
        return self::getContainer()->get(SubcriptionService::class);
    }

    private function translator(): TranslatorInterface
    {
        return self::getContainer()->get(TranslatorInterface::class);
    }

    private function room(string $name = 'TestMeeting: 0'): Rooms
    {
        return self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => $name]);
    }

    private function user(string $email): User
    {
        return self::getContainer()->get(UserRepository::class)->findOneBy(['email' => $email]);
    }

    private function userData(string $email): array
    {
        return ['email' => $email, 'firstName' => 'Sub', 'lastName' => 'Scriber'];
    }

    public function testSubscripeCreatesNewSubscriber(): void
    {
        self::bootKernel();
        $room = $this->room();
        $email = 'subscribe-' . uniqid() . '@local.de';
        $repo = self::getContainer()->get(SubscriberRepository::class);
        $before = $repo->count([]);

        $res = $this->service()->subscripe($this->userData($email), $room);

        self::assertFalse($res['error']);
        self::assertSame('success', $res['color']);
        self::assertInstanceOf(Subscriber::class, $res['sub']);
        self::assertNotNull($res['sub']->getUid());
        self::assertSame($email, $res['sub']->getUser()->getEmail());
        self::assertSame($room, $res['sub']->getRoom());
        self::assertSame($before + 1, $repo->count([]));
    }

    public function testSubscripeRejectsInvalidEmail(): void
    {
        self::bootKernel();
        $room = $this->room();
        $repo = self::getContainer()->get(SubscriberRepository::class);
        $before = $repo->count([]);

        $res = $this->service()->subscripe($this->userData('not-a-valid-email'), $room);

        self::assertTrue($res['error']);
        self::assertSame('danger', $res['color']);
        self::assertSame($this->translator()->trans('Ungültige Email. Bitte überprüfen Sie ihre Emailadresse.'), $res['text']);
        self::assertSame($before, $repo->count([]));
    }

    public function testSubscripeRejectsWhenRoomIsFullWithoutWaitinglist(): void
    {
        self::bootKernel();
        $room = $this->room();
        $room->setMaxParticipants(count($room->getUser()))->setWaitinglist(false);
        $repo = self::getContainer()->get(SubscriberRepository::class);
        $before = $repo->count([]);

        $res = $this->service()->subscripe($this->userData('test@local2.de'), $room);

        self::assertTrue($res['error']);
        self::assertSame('danger', $res['color']);
        self::assertSame($this->translator()->trans('Die maximale Teilnehmeranzahl ist bereits erreicht.'), $res['text']);
        self::assertSame($before, $repo->count([]));
    }

    public function testSubscripeStillCreatesSubscriberWhenWaitinglistEnabled(): void
    {
        self::bootKernel();
        $room = $this->room();
        $room->setMaxParticipants(count($room->getUser()))->setWaitinglist(true);
        $email = 'waiting-' . uniqid() . '@local.de';

        $res = $this->service()->subscripe($this->userData($email), $room);

        self::assertFalse($res['error']);
        self::assertInstanceOf(Subscriber::class, $res['sub']);
        self::assertSame($email, $res['sub']->getUser()->getEmail());
    }

    public function testSubscripeRejectsUserAlreadyInRoom(): void
    {
        self::bootKernel();
        $room = $this->room();

        $res = $this->service()->subscripe($this->userData('test@local2.de'), $room);

        self::assertTrue($res['error']);
        self::assertSame('danger', $res['color']);
        self::assertSame($this->translator()->trans('Sie haben sich bereits angemeldet.'), $res['text']);
    }

    public function testSubscripeRejectsExistingSubscriberAndKeepsSingleEntry(): void
    {
        self::bootKernel();
        $room = $this->room();
        $user = $this->user('test@local4.de');
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $subscriber = (new Subscriber())->setUser($user)->setRoom($room)->setUid(md5(uniqid()));
        $em->persist($subscriber);
        $em->flush();
        $repo = self::getContainer()->get(SubscriberRepository::class);
        $before = $repo->count(['room' => $room, 'user' => $user]);

        $res = $this->service()->subscripe($this->userData($user->getEmail()), $room);

        self::assertTrue($res['error']);
        self::assertSame('danger', $res['color']);
        self::assertSame(
            $this->translator()->trans('Sie haben sich bereits angemeldet. Bite bestätigen sie noch ihre Anmeldung durch klick auf den Link in der Email.'),
            $res['text']
        );
        self::assertSame($before, $repo->count(['room' => $room, 'user' => $user]));
    }

    public function testSubscripeAsModeratorCreatesRoomsUser(): void
    {
        self::bootKernel();
        $room = $this->room();
        $email = 'moderator-sub-' . uniqid() . '@local.de';
        $roomsUserRepo = self::getContainer()->get(RoomsUserRepository::class);

        $res = $this->service()->subscripe($this->userData($email), $room, true);

        self::assertFalse($res['error']);
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => $email]);
        self::assertNotNull($user);
        $roomsUser = $roomsUserRepo->findOneBy(['room' => $room, 'user' => $user]);
        self::assertNotNull($roomsUser);
        self::assertTrue($roomsUser->getModerator());
        self::assertTrue($roomsUser->getPrivateMessage());
        self::assertTrue($roomsUser->getShareDisplay());
    }

    public function testAcceptSubWithoutSubscriberReturnsError(): void
    {
        self::bootKernel();

        $res = $this->service()->acceptSub(null);

        self::assertSame('Fehler', $res['title']);
        self::assertSame($this->translator()->trans('Dieser Link ist ungültig. Wahrscheinlich wurde er bereits bestätigt.'), $res['message']);
    }

    public function testAcceptSubCreatesUserRoomAndRemovesSubscriber(): void
    {
        self::bootKernel();
        $room = $this->room();
        $user = $this->user('test@local4.de');
        $room->setModerator($user);
        self::assertFalse($room->getUser()->contains($user));
        $subscriber = $this->persistSubscriber($user, $room);
        $subscriberId = $subscriber->getId();

        $res = $this->service()->acceptSub($subscriber);

        self::assertSame($this->translator()->trans('Erfolgreich bestätigt'), $res['title']);
        self::assertTrue($room->getUser()->contains($user));
        self::assertTrue($user->getRooms()->contains($room));
        self::assertNull(self::getContainer()->get(SubscriberRepository::class)->find($subscriberId));
    }

    public function testAcceptSubAddsToWaitinglistWhenRoomIsFull(): void
    {
        self::bootKernel();
        $room = $this->room();
        $user = $this->user('test@local4.de');
        $room->setModerator($user)->setMaxParticipants(count($room->getUser()))->setWaitinglist(true);
        $subscriber = $this->persistSubscriber($user, $room);
        $subscriberId = $subscriber->getId();

        $res = $this->service()->acceptSub($subscriber);

        self::assertSame($this->translator()->trans('Erfolgreich bestätigt'), $res['title']);
        $waiting = self::getContainer()->get(WaitinglistRepository::class)->findOneBy(['room' => $room, 'user' => $user]);
        self::assertNotNull($waiting);
        self::assertFalse($room->getUser()->contains($user));
        self::assertNull(self::getContainer()->get(SubscriberRepository::class)->find($subscriberId));
    }

    public function testAcceptSubRejectsWhenRoomIsFullAndWaitinglistDisabled(): void
    {
        self::bootKernel();
        $room = $this->room();
        $user = $this->user('test@local4.de');
        $room->setMaxParticipants(count($room->getUser()))->setWaitinglist(false);
        $subscriber = $this->persistSubscriber($user, $room);

        $res = $this->service()->acceptSub($subscriber);

        self::assertSame('Fehler', $res['title']);
        self::assertSame($this->translator()->trans('Die maximale Teilnehmeranzahl ist bereits erreicht.'), $res['message']);
        self::assertNotNull(self::getContainer()->get(SubscriberRepository::class)->find($subscriber->getId()));
        self::assertFalse($room->getUser()->contains($user));
    }

    public function testCreateNewSubscriberReturnsPersistedSubscriber(): void
    {
        self::bootKernel();
        $room = $this->room();
        $user = $this->user('test@local4.de');

        $res = $this->service()->createNewSubscriber($user, $room);

        self::assertFalse($res['error']);
        self::assertSame('success', $res['color']);
        self::assertInstanceOf(Subscriber::class, $res['sub']);
        self::assertSame(
            self::getContainer()->get(SubscriberRepository::class)->find($res['sub']->getId()),
            $res['sub']
        );
    }

    public function testCreateNewWaitinglistPersistsEntryAndAddsUser(): void
    {
        self::bootKernel();
        $room = $this->room();
        $user = $this->user('test@local4.de');
        $room->setModerator($user);

        $res = $this->service()->createNewWaitinglist($user, $room);

        self::assertFalse($res['error']);
        $waiting = self::getContainer()->get(WaitinglistRepository::class)->findOneBy(['room' => $room, 'user' => $user]);
        self::assertNotNull($waiting);
        self::assertInstanceOf(\DateTimeInterface::class, $waiting->getCreatedAt());
    }

    public function testCreateUserRoomAddsUserToRoom(): void
    {
        self::bootKernel();
        $room = $this->room();
        $user = $this->user('test@local4.de');
        $room->setModerator($user);
        self::assertFalse($room->getUser()->contains($user));

        $this->service()->createUserRoom($user, $room);

        self::assertTrue($room->getUser()->contains($user));
        self::assertTrue($user->getRooms()->contains($room));
    }

    private function persistSubscriber(User $user, Rooms $room): Subscriber
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $subscriber = (new Subscriber())->setUser($user)->setRoom($room)->setUid(md5(uniqid()));
        $em->persist($subscriber);
        $em->flush();
        return $subscriber;
    }
}
