<?php

namespace App\Tests\Unit\Form\Type;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\User;
use App\Form\Type\RoomType;
use App\Service\Theme\ThemeService;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class RoomTypeTest extends FormTypeTestCase
{
    private function servers(): array
    {
        return $this->entityManager()->getRepository(Server::class)->findBy([], ['id' => 'ASC'], 2);
    }

    private function realType(): RoomType
    {
        return self::getContainer()->get(RoomType::class);
    }

    public function testBuildFormContainsCoreFields(): void
    {
        $room = new Rooms();
        $form = $this->formFactory()->create(
            RoomType::class,
            $room,
            ['server' => $this->servers()]
        );

        foreach (['server', 'name', 'agenda', 'start', 'duration', 'scheduleMeeting', 'submit'] as $field) {
            self::assertTrue($form->has($field), sprintf('Form is missing field "%s"', $field));
        }

        self::assertSame(EntityType::class, $this->innerTypeOf($form->get('server')));
        self::assertSame(TextType::class, $this->innerTypeOf($form->get('name')));
        self::assertSame(TextareaType::class, $this->innerTypeOf($form->get('agenda')));
        self::assertSame(DateTimeType::class, $this->innerTypeOf($form->get('start')));
        self::assertSame(ChoiceType::class, $this->innerTypeOf($form->get('duration')));
        self::assertSame(CheckboxType::class, $this->innerTypeOf($form->get('scheduleMeeting')));
        self::assertSame(SubmitType::class, $this->innerTypeOf($form->get('submit')));
        self::assertSame(Rooms::class, $form->getConfig()->getOption('data_class'));
    }

    public function testServerFieldUsesServerDisabledOption(): void
    {
        $form = $this->formFactory()->create(
            RoomType::class,
            new Rooms(),
            ['server' => $this->servers(), 'serverDisabled' => true]
        );

        self::assertTrue($form->get('server')->getConfig()->getOption('disabled'));
    }

    public function testBuildFormAddsThemeDependentFields(): void
    {
        $theme = $this->createMock(ThemeService::class);
        $theme->method('getApplicationProperties')->willReturn(1);

        $type = new RoomType(
            $this->entityManager(),
            self::getContainer()->get(ParameterBagInterface::class),
            self::getContainer()->get(LoggerInterface::class),
            $theme,
            self::getContainer()->get(TranslatorInterface::class),
        );

        $server = $this->entityManager()->getRepository(Server::class)->find(2);
        $room = new Rooms();
        $room->setServer($server);

        $form = $this->factoryWithType($type)->create(
            RoomType::class,
            $room,
            ['server' => $this->servers(), 'user' => new User()]
        );

        foreach ([
            'persistantRoom', 'onlyRegisteredUsers', 'public', 'maxParticipants',
            'disableSelfSubscriptionDoubleOptIn', 'waitinglist', 'showRoomOnJoinpage',
            'totalOpenRooms', 'dissallowScreenshareGlobal', 'timeZone', 'lobby', 'maxUser',
        ] as $field) {
            self::assertTrue($form->has($field), sprintf('Form is missing field "%s"', $field));
        }

        self::assertSame(NumberType::class, $this->innerTypeOf($form->get('maxParticipants')));
        self::assertSame(CheckboxType::class, $this->innerTypeOf($form->get('persistantRoom')));
        self::assertSame(\Symfony\Component\Form\Extension\Core\Type\TimezoneType::class, $this->innerTypeOf($form->get('timeZone')));
    }

    public function testSubmitMapsRoomData(): void
    {
        $servers = $this->servers();
        $room = new Rooms();
        $form = $this->formFactory()->create(
            RoomType::class,
            $room,
            ['server' => $servers, 'csrf_protection' => false]
        );

        $form->submit([
            'name' => 'My Scheduled Room',
            'start' => '2026-01-15T10:00',
            'duration' => 90,
            'server' => $servers[1]->getId(),
        ]);

        self::assertTrue($form->isValid());
        self::assertSame('My Scheduled Room', $room->getName());
        self::assertSame(90.0, $room->getDuration());
        self::assertSame($servers[1]->getId(), $room->getServer()->getId());
    }

    public function testSubmitUnknownServerIsInvalid(): void
    {
        $form = $this->formFactory()->create(
            RoomType::class,
            new Rooms(),
            ['server' => $this->servers(), 'csrf_protection' => false]
        );
        $form->submit([
            'name' => 'My Scheduled Room',
            'start' => '2026-01-15T10:00',
            'duration' => 60,
            'server' => 999999,
        ]);

        self::assertFalse($form->isValid());
    }

    public function testConfigureOptionsDefaults(): void
    {
        $resolver = new OptionsResolver();
        $this->realType()->configureOptions($resolver);

        $options = $resolver->resolve([]);

        self::assertSame([], $options['server']);
        self::assertFalse($options['serverDisabled']);
        self::assertSame(Rooms::class, $options['data_class']);
        self::assertSame('today', $options['minDate']);
        self::assertFalse($options['isEdit']);
        self::assertSame(User::class, $options['user']);
        self::assertSame('newRoom_form', $options['attr']['id']);
    }
}
