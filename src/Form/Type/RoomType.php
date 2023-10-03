<?php

/**
 * Created by PhpStorm.
 * User: Emanuel
 * Date: 17.09.2019
 * Time: 20:29
 */

namespace App\Form\Type;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\Tag;
use App\Entity\User;
use App\Repository\TagRepository;
use App\Service\ThemeService;
use App\Util\InputSettings;
use Doctrine\ORM\EntityManagerInterface;
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
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class RoomType extends AbstractType
{
    private $parameterBag;
    private $logger;
    private $theme;
    private $translator;
    private EntityManagerInterface $entityManager;


    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $parameterBag, LoggerInterface $logger, ThemeService $themeService, TranslatorInterface $translator)
    {
        $this->parameterBag = $parameterBag;
        $this->logger = $logger;
        $this->theme = $themeService;
        $this->translator = $translator;
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $time = (new \DateTime())->getTimestamp();
        $room = $options['data'];
        $during = false;
        if ($room->getStartTimestamp() && $room->getStartTimestamp() < $time && !$room->getRepeaterProtoype()) {
            $during = true;
        }

            $builder
                ->add(
                    'server',
                    EntityType::class,
                    [
                        'choice_label' => 'serverName',
                        'class' => Server::class,
                        'choices' => $options['server'],
                        'label' => 'label.serverKonferenz',
                        'translation_domain' => 'form',
                        'multiple' => false,
                        'required' => true,
                        'attr' => ['class' => 'moreFeatures fakeserver']
                    ]
                );
        $tags = $this->entityManager->getRepository(Tag::class)->findBy(['disabled' => false], ['priority' => 'ASC']);
        $organisators = [];

        if ($options['user'] instanceof User) {
            $organisators[] = $options['user'];
            $organisators = array_merge($organisators, $options['user']->getManagers()->toArray());
        }


        $builder
            ->add('name', TextType::class, ['disabled' => $during, 'required' => true, 'label' => 'label.konferenzName', 'translation_domain' => 'form'])
            ->add('agenda', TextareaType::class, ['disabled' => $during, 'required' => false, 'label' => 'label.agenda', 'translation_domain' => 'form'])
            ->add('start', DateTimeType::class, ['required' => true, 'attr' => ['data-minDate' => $options['minDate'], 'class' => 'flatpickr', 'placeholder' => 'placeholder.chooseTime'], 'label' => 'label.start', 'translation_domain' => 'form', 'widget' => 'single_text'])
            ->add(
                'duration',
                ChoiceType::class,
                [
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
                ]
            )
            ->add('scheduleMeeting', CheckboxType::class, ['required' => false, 'label' => 'label.scheduleMeeting', 'translation_domain' => 'form']);

        if ($this->theme->getApplicationProperties(InputSettings::PERSISTENT_ROOMS) == 1) {
            $this->logger->debug('Add Persistant Rooms to the Form');
            $builder->add('persistantRoom', CheckboxType::class, ['required' => false, 'label' => 'label.persistantRoom', 'translation_domain' => 'form']);
        };
        if ($this->theme->getApplicationProperties(InputSettings::ONLY_REGISTERED) == 1) {
            $this->logger->debug('Add Only Registered Users to the Form');
            $builder->add('onlyRegisteredUsers', CheckboxType::class, ['required' => false, 'label' => 'label.nurRegistriertenutzer', 'translation_domain' => 'form']);
        };
        if ($this->theme->getApplicationProperties(InputSettings::SHARE_LINK) == 1) {
            $this->logger->debug('Add Share Links to the Form');
            $builder->add('public', CheckboxType::class, ['required' => false, 'label' => 'label.puplicRoom', 'translation_domain' => 'form', 'attr' => ['class' => 'public_checkbox']]);
        };

        if ($this->theme->getApplicationProperties(InputSettings::MAX_PARTICIPANTS) == 1) {
            $this->logger->debug('Add A maximal allowed number of participants to the Form');
            $builder->add('maxParticipants', NumberType::class, ['required' => false, 'label' => 'label.maxParticipants', 'translation_domain' => 'form', 'attr' => ['placeholder' => 'placeholder.maxParticipants']]);
        };
        if ($this->theme->getApplicationProperties(InputSettings::WAITING_LIST) == 1) {
            $this->logger->debug('Add a waitinglist to the Form');
            $builder->add('waitinglist', CheckboxType::class, ['required' => false, 'label' => 'label.waitinglist', 'translation_domain' => 'form']);
        };
        if ($this->theme->getApplicationProperties(InputSettings::CONFERENCE_JOIN_PAGE) == 1) {
            $this->logger->debug('Add Show Room on Joinpage to the Form');
            $builder->add('showRoomOnJoinpage', CheckboxType::class, ['required' => false, 'label' => 'label.showRoomOnJoinpage', 'translation_domain' => 'form']);
        };
        if ($this->theme->getApplicationProperties(InputSettings::DEACTIVATE_PARTICIPANTS_LIST) == 1) {
            $this->logger->debug('Add the possibility the users must not be on the participants list  to the Form');
            $builder->add('totalOpenRooms', CheckboxType::class, ['required' => false, 'label' => 'label.totalOpenRooms', 'translation_domain' => 'form']);
        };
        if ($this->theme->getApplicationProperties(InputSettings::DISALLOW_SCREENSHARE) == 1) {
            $this->logger->debug('Add the possibility to dissallow screenshare');
            $builder->add('dissallowScreenshareGlobal', CheckboxType::class, ['required' => false, 'label' => 'label.dissallowScreenshareGlobal', 'translation_domain' => 'form']);
        }
        if ($this->theme->getApplicationProperties('allowTimeZoneSwitch') == 1) {
            $this->logger->debug('Add the possibility to select a Timezone');
            $builder->add('timeZone', TimezoneType::class, ['required' => false, 'label' => 'label.timezone', 'translation_domain' => 'form']);
        }
        if ($this->theme->getApplicationProperties(InputSettings::ALLOW_LOBBY) == 1) {
            $this->logger->debug('Add the possibility to select the lobby');
            $builder->add('lobby', CheckboxType::class, ['required' => false, 'label' => 'label.lobby', 'translation_domain' => 'form']);
        }

        if ($this->theme->getApplicationProperties(InputSettings::ALLOW_SET_MAX_USERS) == 1) {
            $this->logger->debug('Add the possibility to set the max participants');
            $builder->add('maxUser', NumberType::class, ['required' => false, 'label' => 'label.maxUser', 'translation_domain' => 'form', 'attr' => ['placeholder' => 'placeholder.maxParticipants']]);
        }


        $formModifier = function (FormInterface $form, Server $server = null): void {
            $tags = null === $server ? [] : $server->getTag();
            if (count($tags) > 1) {
                $form->add('tag', EntityType::class, [
                    'class' => Tag::class,
                    'choice_label' => 'title',
                    'choices' => $tags,
                    'required' => true,
                    'label' => 'label.tag',
                    'translation_domain' => 'form'
                ]);
            }

        };
        if ($options['showTag']) {


                $builder->addEventListener(
                    FormEvents::PRE_SET_DATA,
                    function (FormEvent $event) use ($formModifier): void {
                        // this would be your entity, i.e. SportMeetup
                        $data = $event->getData();
                        $formModifier($event->getForm(), $data->getServer());
                    }
                );

                $builder->get('server')->addEventListener(
                    FormEvents::POST_SUBMIT,
                    function (FormEvent $event) use ($formModifier): void {
                        // It's important here to fetch $event->getForm()->getData(), as
                        // $event->getData() will get you the client data (that is, the ID)
                        $sport = $event->getForm()->getData();

                        // since we've added the listener to the child, we'll have to pass on
                        // the parent to the callback function!
                        $formModifier($event->getForm()->getParent(), $sport);
                    }
                );



        }


        if (sizeof($organisators) > 1) {
            $this->logger->debug('Add the possibility to select a supervisor');
            $builder->add(
                'moderator',
                EntityType::class,
                [
                    'class' => User::class,
                    'choice_label' => function (User $user) {
                        return $user->getFormatedName($this->theme->getApplicationProperties('laf_showNameFrontend'));
                    },
                    'choices' => $organisators,
                    'required' => true,
                    'label' => 'label.moderator',
                    'translation_domain' => 'form'
                ]
            );
        }
        $builder->add(
            'submit',
            SubmitType::class,
            ['label' => 'label.speichern', 'translation_domain' => 'form', 'attr' => [
                'class' => 'd-none']]
        );
        if (count($options['server']) === 1) {
            $builder->remove('server');
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {

        $resolver->setDefaults(
            [
                'server' => [],
                'data_class' => Rooms::class,
                'minDate' => 'today',
                'isEdit' => false,
                'user' => User::class,
            ]
        );

        $resolver->setDefault('attr', function (Options $options) {
            $attr = array('id' => 'newRoom_form');
            if (!$options['isEdit'] && $this->parameterBag->get(InputSettings::ALLOW_EDIT_TAG) == 0 && $this->parameterBag->get(InputSettings::ALLOW_TAG) == 1) {
                $attr['data-blocktext'] = $this->translator->trans('new.room.blockSave.text');
                return $attr;
            }
            return $attr;
        }
        );
        $resolver->setDefault('showTag', function (Options $options) {
            if ($this->parameterBag->get(InputSettings::ALLOW_EDIT_TAG) == 1 && $this->parameterBag->get(InputSettings::ALLOW_TAG) == 1) {
                return true;
            }
            if (!$options['isEdit'] && $this->parameterBag->get(InputSettings::ALLOW_EDIT_TAG) == 0 && $this->parameterBag->get(InputSettings::ALLOW_TAG) == 1) {
                return true;
            } elseif ($options['isEdit'] && $this->parameterBag->get(InputSettings::ALLOW_EDIT_TAG) == 0 && $this->parameterBag->get(InputSettings::ALLOW_TAG) == 1) {
                return false;
            }
            return false;
        }
        );
    }
}
