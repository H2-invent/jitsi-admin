<?php

namespace App\Tests\Unit\Form\Type;

use App\Form\Type\JoinViewType;
use App\Service\Theme\ThemeService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class JoinViewTypeTest extends FormTypeTestCase
{
    private function type(bool $allowBrowser): JoinViewType
    {
        $theme = $this->createMock(ThemeService::class);
        $theme->method('getApplicationProperties')->willReturn($allowBrowser ? 1 : null);

        return new JoinViewType(self::getContainer()->get(ParameterBagInterface::class), $theme);
    }

    public function testBuildFormWithoutBrowserJoin(): void
    {
        $form = $this->factoryWithType($this->type(false))->create(JoinViewType::class, null);

        self::assertTrue($form->has('uid'));
        self::assertTrue($form->has('email'));
        self::assertTrue($form->has('name'));
        self::assertFalse($form->has('joinBrowser'));

        self::assertSame(TextType::class, $this->innerTypeOf($form->get('uid')));
        self::assertSame(TextType::class, $this->innerTypeOf($form->get('email')));
        self::assertSame(TextType::class, $this->innerTypeOf($form->get('name')));
    }

    public function testBuildFormWithBrowserJoin(): void
    {
        $form = $this->factoryWithType($this->type(true))->create(JoinViewType::class, null);

        self::assertTrue($form->has('joinBrowser'));
        self::assertSame(SubmitType::class, $this->innerTypeOf($form->get('joinBrowser')));
    }

    public function testSubmitConferenceData(): void
    {
        $form = $this->factoryWithType($this->type(false))->create(JoinViewType::class, null, ['csrf_protection' => false]);
        $form->submit(['uid' => 'abc123', 'email' => 'test@local.de', 'name' => 'Tester']);

        self::assertTrue($form->isValid());
        self::assertSame('abc123', $form->getData()['uid']);
        self::assertSame('test@local.de', $form->getData()['email']);
        self::assertSame('Tester', $form->getData()['name']);
    }
}
