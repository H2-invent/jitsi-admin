<?php

namespace App\Form;

use App\Entity\AddressGroup;
use App\Entity\Documents;
use App\Entity\LdapUserProperties;
use App\Entity\Repeat;
use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CalendlyTokenType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'calendlyServer',
                EntityType::class,
                [
                    'choice_label' => 'serverName',
                    'class' => Server::class,
                    'choices' => $options['server'],
                    'label' => 'label.calendlyServer',
                    'translation_domain' => 'form',
                    'multiple' => false,
                    'required' => true,
                ]
            )
            ->add('calendly_token', TextareaType::class, ['attr' => ['placeholder' => 'label.calendlyToken'], 'label' => false, 'required' => true, 'translation_domain' => 'form'])
            ->add('submit', SubmitType::class, ['attr' => ['class' => 'btn btn-primary'], 'label' => 'label.speichern', 'translation_domain' => 'form']);;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'server'=>[]
        ]);
    }
}
