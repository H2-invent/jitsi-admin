<?php
/**
 * Created by PhpStorm.
 * User: Emanuel
 * Date: 17.09.2019
 * Time: 20:29
 */

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JoinViewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('uid', TextType::class, ['attr' => ['placeholder' => 'label.konferenzId'], 'label' => false, 'required' => true, 'translation_domain' => 'form'])
            ->add('email', TextType::class, ['attr' => ['placeholder' => 'label.email'], 'label' => false, 'required' => true, 'translation_domain' => 'form'])
            ->add('name', TextType::class, ['attr' => ['placeholder' => 'label.name'], 'label' => false, 'required' => true, 'translation_domain' => 'form'])
            ->add('joinApp', SubmitType::class, ['attr' => array('class' => 'btn btn-outline-secondary btn-block p-3'), 'label' => 'label.beitretenApp', 'translation_domain' => 'form'])
            ->add('joinBrowser', SubmitType::class, ['attr' => array('class' => 'btn btn-outline-primary btn-block p-3'), 'label' => 'label.beitretenBrowser', 'translation_domain' => 'form']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
    }
}
