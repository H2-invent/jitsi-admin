<?php

namespace App\Tests\Unit\Form\Type;

use App\Entity\User;
use App\Form\Type\UserLineType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class UserLineTypeTest extends FormTypeTestCase
{
    public function testParentTypeIsEntityType(): void
    {
        self::assertSame(EntityType::class, (new UserLineType())->getParent());
    }

    public function testRequiredOptionsAreEnforced(): void
    {
        $this->expectException(MissingOptionsException::class);

        $this->formFactory()->create(UserLineType::class, null, [
            'class' => User::class,
            'choices' => [],
        ]);
    }

    public function testBuildsEntityFieldWithCustomOptions(): void
    {
        $user = $this->entityManager()->getRepository(User::class)->findOneBy(['email' => 'test@local.de']);
        $indexer = static fn (User $u) => $u->getIndexer();
        $name = static fn (User $u) => 'name';

        $form = $this->formFactory()->create(UserLineType::class, null, [
            'class' => User::class,
            'choices' => [$user],
            'choice_indexerName' => $indexer,
            'choice_nameNoIcon' => $name,
        ]);

        self::assertSame(UserLineType::class, $this->innerTypeOf($form));
        self::assertSame(EntityType::class, $form->getConfig()->getType()->getParent()->getInnerType()::class);
        self::assertSame($indexer, $form->getConfig()->getOption('choice_indexerName'));
        self::assertSame($name, $form->getConfig()->getOption('choice_nameNoIcon'));
        self::assertSame(User::class, $form->getConfig()->getOption('class'));
    }

    public function testSubmitResolvesEntity(): void
    {
        $user = $this->entityManager()->getRepository(User::class)->findOneBy(['email' => 'test@local.de']);

        $form = $this->formFactory()->create(UserLineType::class, null, [
            'class' => User::class,
            'choices' => [$user],
            'choice_indexerName' => static fn (User $u) => $u->getIndexer(),
            'choice_nameNoIcon' => static fn (User $u) => 'name',
            'csrf_protection' => false,
        ]);
        $form->submit($user->getId());

        self::assertTrue($form->isValid());
        self::assertSame($user->getId(), $form->getData()->getId());
    }
}
