<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * A text field that is rendered as a masked input (type="password") with an eye
 * toggle. Unlike Symfony's PasswordType it keeps the current value populated when
 * the form is rendered again, so editing an existing server does not silently
 * clear the stored value.
 */
class MaskedTextType extends AbstractType
{
    public function getParent(): ?string
    {
        return TextType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'masked_text';
    }
}
