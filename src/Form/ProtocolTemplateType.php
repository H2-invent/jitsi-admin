<?php

namespace App\Form;

use App\Entity\ProtocolTemplate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

/**
 * @extends AbstractType<ProtocolTemplate>
 */
class ProtocolTemplateType extends AbstractType
{
    public const string SANITIZER = 'app.protocol_sanitizer';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('header', TextareaType::class, [
                'required'      => false,
                'label'         => 'label.protocolTemplate.header',
                'help'          => 'help.protocolTemplate.html',
                'sanitize_html' => true,
                'sanitizer'     => self::SANITIZER,
                'attr'          => ['rows' => 4],
            ])
            ->add('footer', TextareaType::class, [
                'required'      => false,
                'label'         => 'label.protocolTemplate.footer',
                'help'          => 'help.protocolTemplate.html',
                'sanitize_html' => true,
                'sanitizer'     => self::SANITIZER,
                'attr'          => ['rows' => 3],
            ])
            ->add('logoFile', VichImageType::class, [
                'required'     => false,
                'label'        => 'label.protocolTemplate.logo',
                'help'         => 'help.protocolTemplate.logo',
                'allow_delete' => true,
                'download_uri' => false,
                'image_uri'    => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'         => ProtocolTemplate::class,
            'translation_domain' => 'form',
        ]);
    }
}
