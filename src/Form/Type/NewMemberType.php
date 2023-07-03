<?php

/**
 * Created by PhpStorm.
 * User: Emanuel
 * Date: 17.09.2019
 * Time: 20:29
 */

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NewMemberType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('member', TextareaType::class, ['required' => false, 'label' => 'label.teilnehmerEmailhinzufuegen', 'help' => 'help.emailTextfeld', 'translation_domain' => 'form'])
            ->add('moderator', TextareaType::class, ['required' => false, 'label' => 'label.teilnehmerEmailhinzufuegenModerator', 'help' => 'help.teilnehmerEmailhinzufuegenModerator', 'translation_domain' => 'form'])
            ->add('submit', SubmitType::class, ['attr' => ['class' => 'btn btn-primary'], 'label' => 'label.teilnehmerSpeichern', 'translation_domain' => 'form']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [

            ]
        );
    }
}
