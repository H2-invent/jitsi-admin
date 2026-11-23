<?php

namespace App\Tests\Unit\Form\Type;

use App\Form\Type\NewMemberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class NewMemberTypeTest extends FormTypeTestCase
{
    public function testBuildFormContainsExpectedFields(): void
    {
        $form = $this->formFactory()->create(NewMemberType::class, null);

        self::assertTrue($form->has('member'));
        self::assertTrue($form->has('submit'));
        self::assertSame(TextareaType::class, $this->innerTypeOf($form->get('member')));
        self::assertSame(SubmitType::class, $this->innerTypeOf($form->get('submit')));
        self::assertFalse($form->get('member')->getConfig()->getOption('required'));
    }

    public function testSubmitMemberText(): void
    {
        $form = $this->formFactory()->create(NewMemberType::class, null, ['csrf_protection' => false]);
        $form->submit(['member' => "a@example.com\nb@example.com"]);

        self::assertTrue($form->isValid());
        self::assertSame("a@example.com\nb@example.com", $form->getData()['member']);
    }
}
