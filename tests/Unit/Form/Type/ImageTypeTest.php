<?php

namespace App\Tests\Unit\Form\Type;

use App\Entity\Documents;
use App\Form\Type\ImageType;
use Vich\UploaderBundle\Form\Type\VichImageType;

class ImageTypeTest extends FormTypeTestCase
{
    public function testBuildFormContainsDocumentFile(): void
    {
        $form = $this->formFactory()->create(ImageType::class, new Documents());

        self::assertTrue($form->has('documentFile'));
        self::assertSame(VichImageType::class, $this->innerTypeOf($form->get('documentFile')));
        self::assertSame(Documents::class, $form->getConfig()->getOption('data_class'));
    }

    public function testDocumentFileOptions(): void
    {
        $form = $this->formFactory()->create(ImageType::class, new Documents());

        self::assertFalse($form->get('documentFile')->getConfig()->getOption('required'));
        self::assertTrue($form->get('documentFile')->getConfig()->getOption('allow_delete'));
        self::assertSame('Löschen', $form->get('documentFile')->getConfig()->getOption('delete_label'));
    }

    public function testSubmitWithoutFileIsValid(): void
    {
        $form = $this->formFactory()->create(ImageType::class, new Documents(), ['csrf_protection' => false]);
        $form->submit(['documentFile' => null]);

        self::assertTrue($form->isValid());
    }
}
