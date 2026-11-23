<?php

namespace App\Tests\Unit\Form\Type;

use App\Entity\User;
use App\Form\Type\ThemeUploadType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\File;

class ThemeUploadTypeTest extends FormTypeTestCase
{
    public function testBuildFormContainsThemeUpload(): void
    {
        $form = $this->formFactory()->create(ThemeUploadType::class, new User());

        self::assertTrue($form->has('theme'));
        self::assertTrue($form->has('submit'));
        self::assertSame(FileType::class, $this->innerTypeOf($form->get('theme')));
        self::assertSame(SubmitType::class, $this->innerTypeOf($form->get('submit')));
        self::assertSame(User::class, $form->getConfig()->getOption('data_class'));
    }

    public function testThemeFieldIsUnmappedRequiredAndConstrained(): void
    {
        $form = $this->formFactory()->create(ThemeUploadType::class, new User());
        $theme = $form->get('theme');

        self::assertFalse($theme->getConfig()->getOption('mapped'));
        self::assertTrue($theme->getConfig()->getRequired());

        $constraints = $theme->getConfig()->getOption('constraints');
        self::assertCount(1, $constraints);
        self::assertInstanceOf(File::class, $constraints[0]);
        self::assertSame(20000000, $constraints[0]->maxSize);
        self::assertSame(['application/zip'], $constraints[0]->mimeTypes);
    }

    public function testSubmitNonZipFileIsInvalid(): void
    {
        $form = $this->formFactory()->create(ThemeUploadType::class, new User(), ['csrf_protection' => false]);

        $path = tempnam(sys_get_temp_dir(), 'theme');
        file_put_contents($path, 'this is not a zip file');

        $form->submit(['theme' => new UploadedFile($path, 'theme.zip', 'application/zip', null, true)]);

        self::assertFalse($form->isValid());
        self::assertGreaterThan(0, count($form->get('theme')->getErrors(true)));
    }

    public function testSubmitZipFileIsValid(): void
    {
        if (!class_exists(\ZipArchive::class)) {
            self::markTestSkipped('ZipArchive is not available');
        }

        $path = tempnam(sys_get_temp_dir(), 'theme') . '.zip';
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('theme.json', '{}');
        $zip->close();

        $form = $this->formFactory()->create(ThemeUploadType::class, new User(), ['csrf_protection' => false]);
        $form->submit(['theme' => new UploadedFile($path, 'theme.zip', 'application/zip', null, true)]);

        self::assertTrue($form->isValid());
    }
}
