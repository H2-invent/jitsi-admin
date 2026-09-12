<?php

namespace App\Tests\Unit\Form\Type;

use App\Entity\KeycloakGroupsToServers;
use App\Form\Type\KeycloakGroupsToServersType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class KeycloakGroupsToServersTypeTest extends FormTypeTestCase
{
    public function testBuildFormContainsKeycloakGroup(): void
    {
        $form = $this->formFactory()->create(KeycloakGroupsToServersType::class, new KeycloakGroupsToServers());

        self::assertTrue($form->has('keycloakGroup'));
        self::assertSame(TextType::class, $this->innerTypeOf($form->get('keycloakGroup')));
        self::assertTrue($form->get('keycloakGroup')->getConfig()->getOption('required'));
        self::assertSame(KeycloakGroupsToServers::class, $form->getConfig()->getOption('data_class'));
    }

    public function testSubmitMapsKeycloakGroup(): void
    {
        $entity = new KeycloakGroupsToServers();
        $form = $this->formFactory()->create(KeycloakGroupsToServersType::class, $entity, ['csrf_protection' => false]);
        $form->submit(['keycloakGroup' => 'my-group']);

        self::assertTrue($form->isValid());
        self::assertSame('my-group', $entity->getKeycloakGroup());
    }
}
