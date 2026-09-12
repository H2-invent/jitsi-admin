<?php

namespace App\Tests\Unit\Form\Type;

use App\Entity\Rooms;
use App\Entity\User;
use App\Form\Type\SecondEmailType;
use App\Service\Theme\ThemeService;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;

class SecondEmailTypeTest extends FormTypeTestCase
{
    private function type(array $properties, bool $hasTheme): SecondEmailType
    {
        $theme = $this->createMock(ThemeService::class);
        $theme->method('getApplicationProperties')->willReturnCallback(
            static fn (string $key) => $properties[$key] ?? null
        );
        $theme->method('getTheme')->willReturn($hasTheme);

        return new SecondEmailType($theme);
    }

    public function testBuildFormWithAllOptionsEnabled(): void
    {
        $type = $this->type(['allowTimeZoneSwitch' => 1, 'profileAllowSecondEmail' => 1], true);
        $form = $this->factoryWithType($type)->create(SecondEmailType::class, new User());

        self::assertTrue($form->has('profilePicture'));
        self::assertTrue($form->has('submit'));
        self::assertTrue($form->has('timeZone'));
        self::assertTrue($form->has('secondEmail'));

        self::assertSame(TimezoneType::class, $this->innerTypeOf($form->get('timeZone')));
        self::assertSame(TextType::class, $this->innerTypeOf($form->get('secondEmail')));
        self::assertSame(SubmitType::class, $this->innerTypeOf($form->get('submit')));
        self::assertSame(User::class, $form->getConfig()->getOption('data_class'));
    }

    public function testBuildFormWithoutOptionalOptions(): void
    {
        $type = $this->type([], true);
        $form = $this->factoryWithType($type)->create(SecondEmailType::class, new User());

        self::assertFalse($form->has('timeZone'));
        self::assertFalse($form->has('secondEmail'));
    }

    public function testSecondEmailIsShownWithoutTheme(): void
    {
        $type = $this->type([], false);
        $form = $this->factoryWithType($type)->create(SecondEmailType::class, new User());

        self::assertTrue($form->has('secondEmail'));
        self::assertFalse($form->has('timeZone'));
    }

    public function testSubmitMapsSecondEmail(): void
    {
        $user = new User();
        $type = $this->type(['allowTimeZoneSwitch' => 1, 'profileAllowSecondEmail' => 1], true);
        $form = $this->factoryWithType($type)->create(SecondEmailType::class, $user, ['csrf_protection' => false]);

        $form->submit([
            'profilePicture' => ['documentFile' => null],
            'timeZone' => 'Europe/Berlin',
            'secondEmail' => 'second@example.com',
        ]);

        self::assertTrue($form->isValid());
        self::assertSame('second@example.com', $user->getSecondEmail());
        self::assertSame('Europe/Berlin', $user->getTimeZone());
    }
}
