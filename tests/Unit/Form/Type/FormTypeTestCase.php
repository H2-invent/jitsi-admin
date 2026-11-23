<?php

namespace App\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormRegistry;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\ResolvedFormTypeFactory;

abstract class FormTypeTestCase extends KernelTestCase
{
    protected function formFactory(): FormFactoryInterface
    {
        return self::getContainer()->get('form.factory');
    }

    protected function factoryWithType(FormTypeInterface $type): FormFactoryInterface
    {
        $extensions = [new PreloadedExtension([$type], [])];
        $extensions[] = self::getContainer()->get('form.extension');

        return new FormFactory(new FormRegistry($extensions, new ResolvedFormTypeFactory()));
    }

    protected function entityManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManager();
    }

    protected function innerTypeOf(\Symfony\Component\Form\FormInterface $form): string
    {
        return $form->getConfig()->getType()->getInnerType()::class;
    }
}
