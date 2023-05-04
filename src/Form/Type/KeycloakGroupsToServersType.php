<?php

namespace App\Form\Type;

use App\Entity\KeycloakGroupsToServers;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class KeycloakGroupsToServersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('keycloakGroup', TextType::class, ['required' => true, 'label' => 'Name', 'attr' => ['class' => 'd-inline w-75'],]); // 'label' => 'label.keycloakGroup', 'translation_domain' => 'form', 'help' =>
        // ->add('submit', SubmitType::class, ['attr' => array('class' => 'btn btn-outline-primary'), 'label' => 'label.speichern', 'translation_domain' => 'form']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => KeycloakGroupsToServers::class,
            ]
        );
    }
}
