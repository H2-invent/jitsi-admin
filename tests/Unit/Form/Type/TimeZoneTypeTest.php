<?php

namespace App\Tests\Unit\Form\Type;

use App\Entity\User;
use App\Form\Type\TimeZoneType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType as SymfonyTimezoneType;

class TimeZoneTypeTest extends FormTypeTestCase
{
    public function testBuildFormContainsExpectedFields(): void
    {
        $form = $this->formFactory()->create(TimeZoneType::class, new User());

        self::assertTrue($form->has('timeZone'));
        self::assertTrue($form->has('submit'));
        self::assertSame(SymfonyTimezoneType::class, $this->innerTypeOf($form->get('timeZone')));
        self::assertSame(SubmitType::class, $this->innerTypeOf($form->get('submit')));
        self::assertFalse($form->get('timeZone')->getConfig()->getOption('required'));
        self::assertSame(User::class, $form->getConfig()->getOption('data_class'));
    }

    public function testSubmitMapsTimeZone(): void
    {
        $user = new User();
        $form = $this->formFactory()->create(TimeZoneType::class, $user, ['csrf_protection' => false]);
        $form->submit(['timeZone' => 'Europe/Berlin']);

        self::assertTrue($form->isValid());
        self::assertSame('Europe/Berlin', $user->getTimeZone());
    }
}
