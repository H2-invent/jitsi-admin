<?php

namespace App\Tests\Unit\Form\Type;

use App\Form\Type\JoinMyRoomType;
use App\Service\Theme\ThemeService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class JoinMyRoomTypeTest extends FormTypeTestCase
{
    private function type(bool $allowBrowser): JoinMyRoomType
    {
        $theme = $this->createMock(ThemeService::class);
        $theme->method('getApplicationProperties')->willReturn($allowBrowser ? 1 : null);

        return new JoinMyRoomType(self::getContainer()->get(ParameterBagInterface::class), $theme);
    }

    public function testBuildFormWithoutBrowserJoin(): void
    {
        $form = $this->factoryWithType($this->type(false))->create(JoinMyRoomType::class, null);

        self::assertTrue($form->has('name'));
        self::assertFalse($form->has('joinBrowser'));
        self::assertSame(TextType::class, $this->innerTypeOf($form->get('name')));
    }

    public function testBuildFormWithBrowserJoin(): void
    {
        $form = $this->factoryWithType($this->type(true))->create(JoinMyRoomType::class, null);

        self::assertTrue($form->has('name'));
        self::assertTrue($form->has('joinBrowser'));
        self::assertSame(SubmitType::class, $this->innerTypeOf($form->get('joinBrowser')));
    }

    public function testSubmitName(): void
    {
        $form = $this->factoryWithType($this->type(false))->create(JoinMyRoomType::class, null, ['csrf_protection' => false]);
        $form->submit(['name' => 'My Room']);

        self::assertTrue($form->isValid());
        self::assertSame('My Room', $form->getData()['name']);
    }
}
