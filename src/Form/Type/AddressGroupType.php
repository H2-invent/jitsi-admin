<?php

/**
 * Created by PhpStorm.
 * User: Emanuel
 * Date: 17.09.2019
 * Time: 20:29
 */

namespace App\Form\Type;

use App\Entity\AddressGroup;
use App\Entity\User;
use App\Service\ParticipantSearchService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<AddressGroup>
 */
class AddressGroupType extends AbstractType
{
    public function __construct(private readonly ParticipantSearchService $participantSearchService)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];
        $builder
            ->add(
                'name',
                TextType::class,
                ['attr' => ['placeholder' => 'label.addressgroupName'], 'label' => 'label.addressgroupName', 'required' => true, 'translation_domain' => 'form']
            )
            ->add(
                'member',
                UserLineType::class,
                [
                    'choice_indexerName' => fn(User $user) => $user->getIndexer(),
                    'choice_nameNoIcon'  => $this->participantSearchService->buildShowInFrontendStringNoString(...),
                    'label'              => 'label.addressgroupMember',
                    'class'              => User::class,
                    'multiple'           => true,
                    'expanded'           => true,
                    'label_html'         => true,
                    'choice_label'       => $this->participantSearchService->buildShowInFrontendString(...),
                    'choices'            => $user->getAddressbook(),
                    'translation_domain' => 'form',
                    'choice_attr'        => // adds a class like attending_yes, attending_no, etc
                        fn(User $user)
                            => [
                            'data-indexer' => $user->getIndexer(),
                            'data-labelNoIcon' => $this->participantSearchService->buildShowInFrontendStringNoString($user)
                        ],
                ]
            )
            ->add('submit', SubmitType::class, ['attr' => ['class' => 'btn btn-primary'], 'label' => 'label.speichern', 'translation_domain' => 'form']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => AddressGroup::class,
                'user'       => new User(),
            ]
        );
    }
}
