<?php

namespace App\Tests\Unit\Form\Type;

use App\Form\Type\NewPermissionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class NewPermissionTypeTest extends FormTypeTestCase
{
    public function testBuildFormContainsExpectedFields(): void
    {
        $form = $this->formFactory()->create(NewPermissionType::class, null);

        self::assertTrue($form->has('member'));
        self::assertTrue($form->has('submit'));
        self::assertSame(TextareaType::class, $this->innerTypeOf($form->get('member')));
        self::assertSame(SubmitType::class, $this->innerTypeOf($form->get('submit')));
        self::assertFalse($form->get('member')->getConfig()->getOption('required'));
    }

    public function testSubmitMemberText(): void
    {
        $form = $this->formFactory()->create(NewPermissionType::class, null, ['csrf_protection' => false]);
        $form->submit(['member' => 'organizer@example.com']);

        self::assertTrue($form->isValid());
        self::assertSame('organizer@example.com', $form->getData()['member']);
    }
}
