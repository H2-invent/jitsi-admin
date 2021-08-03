<?php
/**
 * Created by PhpStorm.
 * User: Emanuel
 * Date: 17.09.2019
 * Time: 20:29
 */

namespace App\Form\Type;


use App\Entity\AuditTomAbteilung;
use App\Entity\Rooms;
use App\Entity\Server;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
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
    private $paramterBag;
    private $logger;
    public function __construct(ParameterBagInterface $parameterBag, LoggerInterface $logger)
    {
        $this->paramterBag = $parameterBag;
        $this->logger = $logger;
    }

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
                'attr' => array('class' => 'moreFeatures')
            ])
            ->add('name', TextType::class, ['required' => true, 'label' => 'label.konferenzName', 'translation_domain' => 'form'])
            ->add('agenda', TextareaType::class, ['required' => false, 'label' => 'label.agenda', 'translation_domain' => 'form'])
            ->add('start', DateTimeType::class, ['required' => true, 'attr' => ['class' => 'flatpickr'], 'label' => 'label.start', 'translation_domain' => 'form', 'widget' => 'single_text'])
            ->add('duration', ChoiceType::class, [
                'label' => 'label.dauerKonferenz',
                'translation_domain' => 'form',
                'choices' => [
                    'option.15min' => 15,
                    'option.30min' => 30,
                    'option.45min' => 45,
                    'option.60min' => 60,
                    'option.75min' => 75,
                    'option.90min' => 90,
                    'option.105min' => 105,
                    'option.120min' => 120,
                    'option.150min' => 150,
                    'option.180min' => 180,
                    'option.210min' => 210,
                    'option.240min' => 240,
                    'option.270min' => 270,
                    'option.300min' => 300,
                    'option.330min' => 330,
                    'option.360min' => 360,
                    'option.390min' => 390,
                    'option.420min' => 420,
                    'option.450min' => 450,
                    'option.480min' => 480,

                ]
            ])
            ->add('scheduleMeeting', CheckboxType::class, array('required' => false, 'label' => 'label.scheduleMeeting', 'translation_domain' => 'form'));
            if ($this->paramterBag->get('input_settings_persistant_rooms') == 1) {
                $this->logger->debug('Add Persistant Rooms to the Form');
                $builder->add('persistantRoom', CheckboxType::class, array('required' => false, 'label' => 'label.persistantRoom', 'translation_domain' => 'form'));
            };
            if ($this->paramterBag->get('input_settings_only_registered') == 1) {
                $this->logger->debug('Add Only Registered Users to the Form');
                $builder->add('onlyRegisteredUsers', CheckboxType::class, array('required' => false, 'label' => 'label.nurRegistriertenutzer', 'translation_domain' => 'form'));
            };
            if ($this->paramterBag->get('input_settings_share_link') == 1) {
                $this->logger->debug('Add Share Links to the Form');
                $builder->add('public', CheckboxType::class, array('required' => false, 'label' => 'label.puplicRoom', 'translation_domain' => 'form'));
            };

            if ($this->paramterBag->get('input_settings_max_participants') == 1) {
                $this->logger->debug('Add A maximal allowed number of participants to the Form');
                $builder->add('maxParticipants', NumberType::class, array('required' => false, 'label' => 'label.maxParticipants', 'translation_domain' => 'form', 'attr' => array('placeholder' => 'placeholder.maxParticipants')));
            };
           if ($this->paramterBag->get('input_settings_waitinglist') == 1) {
               $this->logger->debug('Add a waitinglist to the Form');
               $builder->add('waitinglist', CheckboxType::class, array('required' => false, 'label' => 'label.waitinglist', 'translation_domain' => 'form'));
           };
          if ($this->paramterBag->get('input_settings_conference_join_page') == 1) {
              $this->logger->debug('Add Show Room on Joinpage to the Form');
              $builder->add('showRoomOnJoinpage', CheckboxType::class, array('required' => false, 'label' => 'label.showRoomOnJoinpage', 'translation_domain' => 'form'));
          };
         if ($this->paramterBag->get('input_settings_deactivate_participantsList') == 1) {
             $this->logger->debug('Add the possibility the users must not be on the participants list  to the Form');
             $builder->add('totalOpenRooms', CheckboxType::class, array('required' => false, 'label' => 'label.totalOpenRooms', 'translation_domain' => 'form'))
                 ->add('totalOpenRoomsOpenTime', NumberType::class, array('required' => false, 'label' => 'label.totalOpenRoomsOpenTime', 'translation_domain' => 'form', 'attr' => array('placeholder' => 'placeholder.maxParticipants')));
         };
          if ($this->paramterBag->get('input_settings_dissallow_screenshare') == 1) {
              $this->logger->debug('Add the possibility to dissallow screenshare');
              $builder->add('dissallowScreenshareGlobal', CheckboxType::class, array('required' => false, 'label' => 'label.dissallowScreenshareGlobal', 'translation_domain' => 'form'));
          }
          $builder->add('submit', SubmitType::class, ['attr' => array('class' => 'btn btn-outline-primary'), 'label' => 'label.speichern', 'translation_domain' => 'form']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'server' => array(),
            'data_class' => Rooms::class
        ]);

    }
}
