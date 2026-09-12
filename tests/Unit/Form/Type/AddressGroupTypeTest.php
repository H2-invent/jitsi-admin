<?php

namespace App\Tests\Unit\Form\Type;

use App\Entity\AddressGroup;
use App\Entity\User;
use App\Form\Type\AddressGroupType;
use App\Form\Type\UserLineType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class AddressGroupTypeTest extends FormTypeTestCase
{
    private function addressbookOwner(): User
    {
        return $this->entityManager()->getRepository(User::class)->findOneBy(['email' => 'test@local.de']);
    }

    public function testBuildFormContainsExpectedFields(): void
    {
        $form = $this->formFactory()->create(
            AddressGroupType::class,
            new AddressGroup(),
            ['user' => $this->addressbookOwner()]
        );

        self::assertTrue($form->has('name'));
        self::assertTrue($form->has('member'));
        self::assertTrue($form->has('submit'));

        self::assertSame(TextType::class, $this->innerTypeOf($form->get('name')));
        self::assertSame(UserLineType::class, $this->innerTypeOf($form->get('member')));
        self::assertSame(SubmitType::class, $this->innerTypeOf($form->get('submit')));

        self::assertTrue($form->get('name')->getConfig()->getOption('required'));
        self::assertTrue($form->get('member')->getConfig()->getOption('multiple'));
        self::assertTrue($form->get('member')->getConfig()->getOption('expanded'));
        self::assertInstanceOf(AddressGroup::class, $form->getData());
    }

    public function testSubmitMapsNameAndMembers(): void
    {
        $group = new AddressGroup();
        $owner = $this->addressbookOwner();
        $member = $this->entityManager()->getRepository(User::class)->findOneBy(['email' => 'test@local2.de']);

        $form = $this->formFactory()->create(
            AddressGroupType::class,
            $group,
            ['user' => $owner, 'csrf_protection' => false]
        );
        $form->submit(['name' => 'My Address Group', 'member' => [$member->getId()]]);

        self::assertTrue($form->isValid());
        self::assertSame('My Address Group', $group->getName());
        self::assertCount(1, $group->getMember());
        self::assertSame($member->getId(), $group->getMember()->first()->getId());
    }

    public function testSubmitMemberOutsideAddressbookIsInvalid(): void
    {
        $group = new AddressGroup();
        $stranger = $this->entityManager()->getRepository(User::class)->findOneBy(['email' => 'test@australia.de']);

        $form = $this->formFactory()->create(
            AddressGroupType::class,
            $group,
            ['user' => $this->addressbookOwner(), 'csrf_protection' => false]
        );
        $form->submit(['name' => 'My Address Group', 'member' => [$stranger->getId()]]);

        self::assertFalse($form->isValid());
    }
}
