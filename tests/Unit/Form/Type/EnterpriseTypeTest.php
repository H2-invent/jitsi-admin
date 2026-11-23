<?php

namespace App\Tests\Unit\Form\Type;

use App\Entity\Server;
use App\Form\Type\EnterpriseType;
use App\Form\Type\ImageType;
use App\Service\Transcription\TranscriptionProvider;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class EnterpriseTypeTest extends FormTypeTestCase
{
    private function server(): Server
    {
        return $this->entityManager()->getRepository(Server::class)->findOneBy([]);
    }

    public function testBuildFormContainsAllExpectedFields(): void
    {
        $form = $this->formFactory()->create(EnterpriseType::class, $this->server());

        foreach ([
            'smtpHost', 'smtpPort', 'smtpEncryption', 'smtpUsername', 'smtpPassword',
            'smtpEmail', 'smtpSenderName', 'logoUrl', 'privacyPolicy', 'serverEmailHeader',
            'serverEmailBody', 'apiKey', 'showStaticBackgroundColor', 'staticBackgroundColor',
            'serverBackgroundImage', 'jigasiApiUrl', 'jigasiNumberUrl', 'jigasiProsodyDomain',
            'jitsiEventSyncUrl', 'livekitMiddlewareUrl', 'enableRecording', 'transcriptionProvider',
            'apiKeyTranscription', 'enableTranscription', 'tag', 'submit',
        ] as $field) {
            self::assertTrue($form->has($field), sprintf('Form is missing field "%s"', $field));
        }
    }

    public function testBuildFormFieldTypes(): void
    {
        $form = $this->formFactory()->create(EnterpriseType::class, $this->server());

        self::assertSame(TextType::class, $this->innerTypeOf($form->get('smtpHost')));
        self::assertSame(TextareaType::class, $this->innerTypeOf($form->get('serverEmailBody')));
        self::assertSame(ChoiceType::class, $this->innerTypeOf($form->get('smtpEncryption')));
        self::assertSame(CheckboxType::class, $this->innerTypeOf($form->get('enableRecording')));
        self::assertSame(ColorType::class, $this->innerTypeOf($form->get('staticBackgroundColor')));
        self::assertSame(ImageType::class, $this->innerTypeOf($form->get('serverBackgroundImage')));
        self::assertSame(EnumType::class, $this->innerTypeOf($form->get('transcriptionProvider')));
        self::assertSame(EntityType::class, $this->innerTypeOf($form->get('tag')));
        self::assertSame(SubmitType::class, $this->innerTypeOf($form->get('submit')));
        self::assertSame(TranscriptionProvider::class, $form->get('transcriptionProvider')->getConfig()->getOption('class'));
    }

    public function testOptionalFieldsAndSubmit(): void
    {
        $server = $this->server();
        $form = $this->formFactory()->create(EnterpriseType::class, $server, ['csrf_protection' => false]);

        self::assertFalse($form->get('smtpHost')->getConfig()->getOption('required'));
        self::assertTrue($form->get('tag')->getConfig()->getOption('multiple'));
        self::assertTrue($form->get('tag')->getConfig()->getOption('expanded'));

        $form->submit([
            'smtpHost' => 'smtp.example.com',
            'smtpPort' => '587',
            'staticBackgroundColor' => '#123456',
        ]);

        self::assertTrue($form->isValid());
        self::assertSame('smtp.example.com', $server->getSmtpHost());
        self::assertSame(587, $server->getSmtpPort());
    }
}
