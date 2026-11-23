<?php

namespace App\Tests\Unit\Form\Type;

use App\Entity\Server;
use App\Entity\User;
use App\Form\CalendlyTokenType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class CalendlyTokenTypeTest extends FormTypeTestCase
{
    public function testBuildFormContainsExpectedFields(): void
    {
        $form = $this->formFactory()->create(CalendlyTokenType::class, new User(), ['server' => []]);

        self::assertTrue($form->has('calendlyServer'));
        self::assertTrue($form->has('calendly_token'));
        self::assertTrue($form->has('submit'));

        self::assertSame(EntityType::class, $this->innerTypeOf($form->get('calendlyServer')));
        self::assertSame(TextareaType::class, $this->innerTypeOf($form->get('calendly_token')));
        self::assertSame(SubmitType::class, $this->innerTypeOf($form->get('submit')));

        self::assertSame(User::class, $form->getConfig()->getOption('data_class'));
        self::assertTrue($form->get('calendlyServer')->getConfig()->getOption('required'));
        self::assertTrue($form->get('calendly_token')->getConfig()->getOption('required'));
    }

    public function testSubmitMapsServerAndTokenToUser(): void
    {
        $server = $this->entityManager()->getRepository(Server::class)->findOneBy([]);
        $user = new User();

        $form = $this->formFactory()->create(
            CalendlyTokenType::class,
            $user,
            ['server' => [$server], 'csrf_protection' => false]
        );
        $form->submit(['calendlyServer' => $server->getId(), 'calendly_token' => 'the-token']);

        self::assertTrue($form->isValid());
        self::assertSame('the-token', $user->getCalendlyToken());
        self::assertSame($server->getId(), $user->getCalendlyServer()->getId());
    }

    public function testSubmitUnknownServerIsInvalid(): void
    {
        $server = $this->entityManager()->getRepository(Server::class)->findOneBy([]);

        $form = $this->formFactory()->create(
            CalendlyTokenType::class,
            new User(),
            ['server' => [$server], 'csrf_protection' => false]
        );
        $form->submit(['calendlyServer' => 999999, 'calendly_token' => 'the-token']);

        self::assertFalse($form->isValid());
    }
}
