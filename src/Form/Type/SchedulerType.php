<?php

namespace App\Form\Type;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\Tag;
use App\Entity\User;
use App\Repository\TagRepository;
use App\Service\ThemeService;
use App\Util\InputSettings;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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

class SchedulerType extends AbstractType
{
    private const DURATION_LABEL_FORMAT = 'option.%dmin';

    public function __construct(
        private TagRepository $tagRepository,
        private LoggerInterface        $logger,
        private ThemeService           $themeService,
        private TranslatorInterface    $translator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $durations = [];

        for ($i = 15; $i <= 120; $i += 15) {
            $durations[sprintf(self::DURATION_LABEL_FORMAT, $i)] = $i;
        }

        for ($i = 150; $i <= 480; $i += 30) {
            $durations[sprintf(self::DURATION_LABEL_FORMAT, $i)] = $i;
        }

        $time = (new DateTime())->getTimestamp();
        $room = $options['data'];
        $during = false;
        if ($room->getStartTimestamp() && $room->getStartTimestamp() < $time && !$room->getRepeaterProtoype()) {
            $during = true;
        }


            $builder
                ->add(
                    'server',
                    EntityType::class,
                    $this->getOptions(
                        true,
                        'label.serverKonferenz',
                        [
                            'choice_label' => 'serverName',
                            'class' => Server::class,
                            'choices' => $options['server'],
                            'multiple' => false,
                            'attr' => ['class' => 'moreFeatures fakeserver']
                        ],
                    ),
                );


        $organisators = [];

        if ($options['user'] instanceof User) {
            $organisators[] = $options['user'];
            $organisators = array_merge($organisators, $options['user']->getManagers()->toArray());
        }

        $builder
            ->add(
                'name',
                TextType::class,
                $this->getOptions(
                    true,
                    'label.konferenzName',
                    ['disabled' => $during],
                ),
            )
            ->add(
                'agenda',
                TextareaType::class,
                $this->getOptions(
                    false,
                    'label.agenda',
                    ['disabled' => $during],
                ),
            )
            ->add(
                'duration',
                ChoiceType::class,
                $this->getOptions(
                    true,
                    'label.dauerKonferenz',
                    ['choices' => $durations],
                ),
            );

        if ($this->checkAppProperty(InputSettings::ONLY_REGISTERED)) {
            $this->logger->debug('Add Only Registered Users to the Form');
            $builder->add(
                'onlyRegisteredUsers',
                CheckboxType::class,
                $this->getOptions(false, 'label.nurRegistriertenutzer')
            );
        };
        if ($this->checkAppProperty(InputSettings::SHARE_LINK)) {
            $this->logger->debug('Add Share Links to the Form');
            $builder->add(
                'public',
                CheckboxType::class,
                $this->getOptions(false, 'label.puplicRoom',['attr'=>['class'=>'public_checkbox']]),
            );
        };

        if ($this->checkAppProperty(InputSettings::MAX_PARTICIPANTS)) {
            $this->logger->debug('Add A maximal allowed number of participants to the Form');
            $builder->add(
                'maxParticipants',
                NumberType::class,
                $this->getOptions(
                    false,
                    'label.maxParticipants',
                    [
                        'attr' => ['placeholder' => 'placeholder.maxParticipants']
                    ],
                ),
            );
        };
        if ($this->checkAppProperty(InputSettings::WAITING_LIST)) {
            $this->logger->debug('Add a waitinglist to the Form');
            $builder->add(
                'waitinglist',
                CheckboxType::class,
                $this->getOptions(false, 'label.waitinglist'),
            );
        }
        if ($this->checkAppProperty(InputSettings::CONFERENCE_JOIN_PAGE)) {
            $this->logger->debug('Add Show Room on Joinpage to the Form');
            $builder->add(
                'showRoomOnJoinpage',
                CheckboxType::class,
                $this->getOptions(false, 'label.showRoomOnJoinpage')
            );
        };
        if ($this->checkAppProperty(InputSettings::DISALLOW_SCREENSHARE)) {
            $this->logger->debug('Add the possibility to disallow screenshare');
            $builder->add(
                'dissallowScreenshareGlobal',
                CheckboxType::class,
                $this->getOptions(false, 'label.dissallowScreenshareGlobal'),
            );
        }
        if ($this->checkAppProperty(InputSettings::ALLOW_TIMEZONE)) {
            $this->logger->debug('Add the possibility to select a Timezone');
            $builder->add(
                'timeZone',
                TimezoneType::class,
                $this->getOptions(false, 'label.timezone'),
            );
        }
        if ($this->checkAppProperty(InputSettings::ALLOW_LOBBY)) {
            $this->logger->debug('Add the possibility to select the lobby');
            $builder->add(
                'lobby',
                CheckboxType::class,
                $this->getOptions(false, 'label.lobby'),
            );
        }

        if ($this->checkAppProperty(InputSettings::ALLOW_MAYBE_OPTION)) {
            $this->logger->debug('Add the possibility to disable the maybe option');
            $builder->add(
                'allowMaybeOption',
                CheckboxType::class,
                $this->getOptions(true, 'label.allowMaybeOption'),
            );
        }

        if ($this->checkAppProperty(InputSettings::ALLOW_SET_MAX_USERS) == 1) {
            $this->logger->debug('Add the possibility to set the max participants');
            $builder->add('maxUser', NumberType::class, ['required' => false, 'label' => 'label.maxUser', 'translation_domain' => 'form', 'attr' => ['placeholder' => 'placeholder.maxParticipants']
            ]);
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

        if (count($organisators) > 1) {
            $this->logger->debug('Add the possibility to select a supervisor');
            $builder->add(
                'moderator',
                EntityType::class,
                $this->getOptions(
                    true,
                    'label.moderator',
                    [
                        'class' => User::class,
                        'choice_label' => function (User $user) {
                            return $user->getFormatedName(
                                $this->themeService->getApplicationProperties('laf_showNameFrontend')
                            );
                        },
                        'choices' => $organisators,
                    ],
                ),
            );
        }
        $builder->add(
            'submit',
            SubmitType::class,
            [
                'label' => 'label.speichern',
                'translation_domain' => 'form',
                'attr' => ['class' => 'd-none'],
            ],
        );
        if (count($options['server']) === 1) {
            $builder->remove('server');
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'server' => [],
                'data_class' => Rooms::class,
                'minDate' => 'today',
                'isEdit' => false,
                'user' => User::class,
                'allowMaybeOption' => $this->checkAppProperty(InputSettings::ALLOW_MAYBE_OPTION_DEFAULT),
            ],
        );

        $resolver->setDefault(
            'attr',
            function (Options $options) {
                $attr = ['id' => 'newRoom_form'];
                if (!$options['isEdit']
                    && !$this->checkAppProperty(InputSettings::ALLOW_EDIT_TAG)
                    && $this->checkAppProperty(InputSettings::ALLOW_TAG)
                ) {
                    $attr['data-blocktext'] = $this->translator->trans('new.room.blockSave.text');

                    return $attr;
                }

                return $attr;
            }
        );
        $resolver->setDefault(
            'showTag',
            function (Options $options) {
                if (!$this->checkAppProperty(InputSettings::ALLOW_TAG)) {
                    return false;
                }

                if ($this->checkAppProperty(InputSettings::ALLOW_EDIT_TAG)) {
                    return true;
                }

                return !$options['isEdit'];
            }
        );
    }

    private function getOptions(
        bool   $required,
        string $label,
        array  $additional = [],
        string $domain = 'form',
    ): array {
        $options = [
            'required' => $required,
            'label' => $label,
            'translation_domain' => $domain,
        ];

        return array_merge($options, $additional);
    }

    private function checkAppProperty(string $parameter): bool
    {
        $propValue = $this->themeService->getApplicationProperties($parameter);

        return ($propValue == 1);
    }
}
