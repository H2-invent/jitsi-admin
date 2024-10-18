<?php
/**
 * Created by PhpStorm.
 * User: Emanuel
 * Date: 17.09.2019
 * Time: 20:29
 */

namespace App\Form\Type;

use App\Service\ThemeService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PublicConferenceType extends AbstractType
{


    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('myName', TextType::class, ['attr' => ['placeholder' => 'label.myName'], 'label' => false, 'required' => true, 'translation_domain' => 'form'])
            ->add('roomName', TextType::class, ['attr' => ['placeholder' => 'label.konferenzName'], 'label' => false, 'required' => true, 'translation_domain' => 'form'])
            ->add('submit', SubmitType::class, array('attr' => array('class' => 'btn btn-primary'), 'label' => 'label.go', 'translation_domain' => 'form'),);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
    }
}
