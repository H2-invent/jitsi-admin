<?php

namespace App\Tests\Unit\Form\Type;

use App\Form\Type\PublicConferenceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class PublicConferenceTypeTest extends FormTypeTestCase
{
    public function testBuildFormContainsExpectedFields(): void
    {
        $form = $this->formFactory()->create(PublicConferenceType::class, null);

        self::assertTrue($form->has('myName'));
        self::assertTrue($form->has('roomName'));
        self::assertTrue($form->has('submit'));

        self::assertSame(TextType::class, $this->innerTypeOf($form->get('myName')));
        self::assertSame(TextType::class, $this->innerTypeOf($form->get('roomName')));
        self::assertSame(SubmitType::class, $this->innerTypeOf($form->get('submit')));

        self::assertTrue($form->get('myName')->getConfig()->getOption('required'));
        self::assertTrue($form->get('roomName')->getConfig()->getOption('required'));
    }

    public function testSubmitConferenceData(): void
    {
        $form = $this->formFactory()->create(PublicConferenceType::class, null, ['csrf_protection' => false]);
        $form->submit(['myName' => 'Tester', 'roomName' => 'My Conference']);

        self::assertTrue($form->isValid());
        self::assertSame('Tester', $form->getData()['myName']);
        self::assertSame('My Conference', $form->getData()['roomName']);
    }
}
