<?php

namespace App\Tests\Unit\Form\Type;

use App\Entity\Server;
use App\Form\Type\KeycloakGroupsToServersType;
use App\Form\Type\ServerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ServerTypeTest extends FormTypeTestCase
{
    public function testBuildFormContainsExpectedFields(): void
    {
        $form = $this->formFactory()->create(ServerType::class, new Server());

        foreach ([
            'liveKitServer', 'url', 'serverName', 'appId', 'appSecret', 'corsHeader',
            'keycloakGroups', 'featureEnableByJWT', 'enforceE2e', 'disallowFirefox',
            'disableFilmstripe', 'disableEtherpad', 'disableWhiteboard', 'disableChat',
            'prefixRoomUidWithHash', 'livekitBackgroundImages', 'allowIp',
            'jwtModeratorPosition', 'submit',
        ] as $field) {
            self::assertTrue($form->has($field), sprintf('Form is missing field "%s"', $field));
        }

        self::assertSame(CheckboxType::class, $this->innerTypeOf($form->get('liveKitServer')));
        self::assertSame(TextType::class, $this->innerTypeOf($form->get('url')));
        self::assertSame(TextType::class, $this->innerTypeOf($form->get('serverName')));
        self::assertSame(TextareaType::class, $this->innerTypeOf($form->get('livekitBackgroundImages')));
        self::assertSame(ChoiceType::class, $this->innerTypeOf($form->get('jwtModeratorPosition')));
        self::assertSame(CollectionType::class, $this->innerTypeOf($form->get('keycloakGroups')));
        self::assertSame(SubmitType::class, $this->innerTypeOf($form->get('submit')));
        self::assertSame(Server::class, $form->getConfig()->getOption('data_class'));
    }

    public function testKeycloakGroupsEntryType(): void
    {
        $form = $this->formFactory()->create(ServerType::class, new Server());
        $keycloakGroups = $form->get('keycloakGroups');

        self::assertSame(KeycloakGroupsToServersType::class, $keycloakGroups->getConfig()->getOption('entry_type'));
        self::assertTrue($keycloakGroups->getConfig()->getOption('allow_add'));
        self::assertTrue($keycloakGroups->getConfig()->getOption('allow_delete'));
        self::assertFalse($keycloakGroups->getConfig()->getOption('by_reference'));
    }

    public function testSubmitMapsServerData(): void
    {
        $server = new Server();
        $form = $this->formFactory()->create(ServerType::class, $server, ['csrf_protection' => false]);

        $form->submit([
            'url' => 'https://meet.example.com',
            'serverName' => 'My Server',
            'jwtModeratorPosition' => 0,
            'keycloakGroups' => [],
        ]);

        self::assertTrue($form->isValid());
        self::assertSame('https://meet.example.com', $server->getUrl());
        self::assertSame('My Server', $server->getServerName());
        self::assertSame(0, $server->getJwtModeratorPosition());
    }

    public function testSubmitKeycloakGroupCollection(): void
    {
        $server = new Server();
        $form = $this->formFactory()->create(ServerType::class, $server, ['csrf_protection' => false]);

        $form->submit([
            'url' => 'https://meet.example.com',
            'serverName' => 'My Server',
            'jwtModeratorPosition' => 1,
            'keycloakGroups' => [
                ['keycloakGroup' => 'group-a'],
                ['keycloakGroup' => 'group-b'],
            ],
        ]);

        self::assertTrue($form->isValid());
        self::assertCount(2, $server->getKeycloakGroups());
        self::assertSame('group-a', $server->getKeycloakGroups()->first()->getKeycloakGroup());
        self::assertSame(1, $server->getJwtModeratorPosition());
    }
}
