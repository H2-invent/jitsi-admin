<?php
/**
 * Created by PhpStorm.
 * User: Emanuel
 * Date: 17.09.2019
 * Time: 20:29
 */

namespace App\Form\Type;


use App\Entity\AuditTomAbteilung;
use App\Entity\Server;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('url', TextType::class, ['required' => false, 'label' => 'URL', 'translation_domain' => 'form'])
            ->add('appId', TextType::class, ['label' => 'App ID', 'translation_domain' => 'form'])
            ->add('appSecret', PasswordType::class, ['label' => 'App Secret', 'translation_domain' => 'form'])
            ->add('submit', SubmitType::class, ['attr' => array('class' => 'btn btn-outline-primary'), 'label' => 'Speichern', 'translation_domain' => 'form']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        ]);

    }
}
