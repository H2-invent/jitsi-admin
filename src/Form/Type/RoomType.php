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
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RoomType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('server', EntityType::class, [
                'choice_label' => 'url',
                'class' => Server::class,
                'choices' => $options['server'],
                'label' => 'Jitsi Server',
                'translation_domain' => 'form',
                'multiple' => false,
                'required' => true,
            ])
            ->add('name', TextType::class, ['required' => false, 'label' => 'Name der Konferenz', 'translation_domain' => 'form'])
            ->add('start', DateTimeType::class, ['label' => 'Start', 'translation_domain' => 'form'])
            ->add('enddate', DateTimeType::class, ['label' => 'Ende', 'translation_domain' => 'form'])
            ->add('submit', SubmitType::class, ['attr' => array('class' => 'btn btn-outline-primary'), 'label' => 'Erstellen', 'translation_domain' => 'form']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'server'=>array(),
        ]);

    }
}
