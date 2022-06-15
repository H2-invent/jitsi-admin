<?php
/**
 * Created by PhpStorm.
 * User: Emanuel
 * Date: 17.09.2019
 * Time: 20:29
 */

namespace App\Form\Type;

use App\Entity\Addressgroup;
use App\Entity\User;
use App\Service\ParticipantSearchService;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressGroupType extends AbstractType
{
    private $parameterBag;
    private ParticipantSearchService $participantSearchService;
    public function __construct(ParameterBagInterface $parameterBag, ParticipantSearchService $participantSearchService)
    {
        $this->parameterBag = $parameterBag;
        $this->participantSearchService = $participantSearchService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $user = $options['user'];
        $builder
            ->add('name', TextType::class, ['attr' => ['placeholder' => 'label.addressgroupName'], 'label' => false, 'required' => true, 'translation_domain' => 'form'])
            ->add('member', EntityType::class, array(
                'label'=>'label.addressgroupMember',
                'class' => User::class,
                'multiple' => true,
                'expanded' => true,
                'label_html'=>true,
                'choice_label' => function(User $user){
                    return $this->participantSearchService->buildShowInFrontendString($user);

                },
                'choices' => $user->getAddressbook(),
                'translation_domain' => 'form'
            ))
            ->add('submit', SubmitType::class, ['attr' => array('class' => 'btn btn-outline-primary btn-sm'), 'label' => 'label.speichern', 'translation_domain' => 'form']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'=>Addressgroup::class,
            'user'=>new User(),
        ]);
    }
}
