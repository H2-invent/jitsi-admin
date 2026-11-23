<?php

namespace App\Tests\Unit\Form\Type;

use App\Entity\User;
use App\Form\Type\ImageType;
use App\Form\Type\ProfileImageType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class ProfileImageTypeTest extends FormTypeTestCase
{
    public function testBuildFormContainsProfilePicture(): void
    {
        $form = $this->formFactory()->create(ProfileImageType::class, new User());

        self::assertTrue($form->has('profilePicture'));
        self::assertTrue($form->has('submit'));
        self::assertSame(ImageType::class, $this->innerTypeOf($form->get('profilePicture')));
        self::assertSame(SubmitType::class, $this->innerTypeOf($form->get('submit')));
        self::assertTrue($form->get('profilePicture')->has('documentFile'));
        self::assertSame(User::class, $form->getConfig()->getOption('data_class'));
    }

    public function testSubmitEmptyIsValid(): void
    {
        $form = $this->formFactory()->create(ProfileImageType::class, new User(), ['csrf_protection' => false]);
        $form->submit(['profilePicture' => ['documentFile' => null]]);

        self::assertTrue($form->isValid());
    }
}
