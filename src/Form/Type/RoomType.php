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
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
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
                'label' => 'label.serverKonferenz',
                'translation_domain' => 'form',
                'multiple' => false,
                'required' => true,
                'attr'=>array('class'=>'moreFeatures')
            ])
            ->add('name', TextType::class, ['required' => true, 'label' => 'label.konferenzName', 'translation_domain' => 'form'])
            ->add('agenda', TextareaType::class, ['required' => false, 'label' => 'label.agenda', 'translation_domain' => 'form'])
            ->add('start', DateTimeType::class, ['attr'=>['class'=>'flatpickr'],'label' => 'label.start', 'translation_domain' => 'form', 'widget' => 'single_text'])
            ->add('duration', ChoiceType::class, [
                'label' => 'label.dauerKonferenz',
                'translation_domain' => 'form',
                'choices' => [
                    'option.15min' => 15,
                    'option.30min' => 30,
                    'option.45min' => 45,
                    'option.60min' => 60,
                    'option.90min' => 90,
                    'option.120min' => 120,
                    'option.240min' => 240,
                    'option.480min' => 480,
                ]
            ])
            ->add('scheduleMeeting',CheckboxType::class,array('required'=>false,'label' => 'label.scheduleMeeting', 'translation_domain' => 'form'))
            ->add('onlyRegisteredUsers',CheckboxType::class,array('required'=>false,'label' => 'label.nurRegistriertenutzer', 'translation_domain' => 'form'))
            ->add('dissallowScreenshareGlobal',CheckboxType::class,array('required'=>false,'label' => 'label.dissallowScreenshareGlobal', 'translation_domain' => 'form'))
            ->add('dissallowPrivateMessage',CheckboxType::class,array('required'=>false,'label' => 'label.dissallowPrivateMessage', 'translation_domain' => 'form'))
            ->add('public',CheckboxType::class,array('required'=>false,'label' => 'label.puplicRoom', 'translation_domain' => 'form'))
            ->add('showRoomOnJoinpage',CheckboxType::class,array('required'=>false,'label' => 'label.showRoomOnJoinpage', 'translation_domain' => 'form'))
            ->add('maxParticipants',NumberType::class,array('required'=>false,'label' => 'label.maxParticipants', 'translation_domain' => 'form','attr'=>array('placeholder'=>'placeholder.maxParticipants')))

            ->add('submit', SubmitType::class, ['attr' => array('class' => 'btn btn-outline-primary'), 'label' => 'label.speichern', 'translation_domain' => 'form']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'server'=>array(),
        ]);

    }
}
