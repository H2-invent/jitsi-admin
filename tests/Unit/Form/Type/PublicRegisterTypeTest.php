<?php

namespace App\Tests\Unit\Form\Type;

use App\Form\Type\PublicRegisterType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class PublicRegisterTypeTest extends FormTypeTestCase
{
    public function testBuildFormContainsExpectedFields(): void
    {
        $form = $this->formFactory()->create(PublicRegisterType::class, null);

        self::assertTrue($form->has('firstName'));
        self::assertTrue($form->has('lastName'));
        self::assertTrue($form->has('email'));
        self::assertTrue($form->has('subscribe'));

        self::assertSame(TextType::class, $this->innerTypeOf($form->get('firstName')));
        self::assertSame(TextType::class, $this->innerTypeOf($form->get('lastName')));
        self::assertSame(TextType::class, $this->innerTypeOf($form->get('email')));
        self::assertSame(SubmitType::class, $this->innerTypeOf($form->get('subscribe')));

        self::assertTrue($form->get('firstName')->getConfig()->getOption('required'));
        self::assertTrue($form->get('lastName')->getConfig()->getOption('required'));
        self::assertTrue($form->get('email')->getConfig()->getOption('required'));
    }

    public function testSubmitRegistrationData(): void
    {
        $form = $this->formFactory()->create(PublicRegisterType::class, null, ['csrf_protection' => false]);
        $form->submit(['firstName' => 'Test', 'lastName' => 'User', 'email' => 'test@local.de']);

        self::assertTrue($form->isValid());
        self::assertSame('Test', $form->getData()['firstName']);
        self::assertSame('User', $form->getData()['lastName']);
        self::assertSame('test@local.de', $form->getData()['email']);
    }
}
