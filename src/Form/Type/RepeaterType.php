<?php
/**
 * Created by PhpStorm.
 * User: Emanuel
 * Date: 17.09.2019
 * Time: 20:29
 */

namespace App\Form\Type;


use App\Entity\AuditTomAbteilung;
use App\Entity\Repeat;
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

class RepeaterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('repeatType', ChoiceType::class, ['choices' => [
                    'option.daily' => 0,
                    'option.weekly' => 1,
                    'option.montly' => 2,
                    'option.yearly' => 3,
                ],
                    'label' => 'label.repeatType',
                    'translation_domain' => 'form']
            )
            ->add('weekday', ChoiceType::class, [
                'choices' => [
                    'option.monday' => 0,
                    'option.tuesday' => 1,
                    'option.wednesday' => 2,
                    'option.thursday' => 3,
                    'option.friday' => 4,
                    'option.saturday' => 5,
                    'option.sunday' => 6
                ],
                'required' => true,
                'label' => 'label.weekday',
                'expanded' => true,
                'multiple' => true,
                'translation_domain' => 'form'
            ])
            ->add('repeaterDays', NumberType::class, ['label'=>false, 'required' => false, 'attr' => ['placeholder' => 'label.repeaterDays'], 'translation_domain' => 'form'])
            ->add('repeaterWeeks', NumberType::class, ['label'=>false, 'required' => false, 'attr' => ['placeholder' => 'label.repeaterWeeks'], 'translation_domain' => 'form'])
            ->add('repeatMontly', NumberType::class, ['label'=>false, 'required' => false, 'attr' => ['placeholder' => 'label.repeatMontly'], 'translation_domain' => 'form'])
            ->add('repeatYearly', NumberType::class, ['label'=>false,'required' => false, 'attr' => ['placeholder' => 'label.repeatYearly'], 'translation_domain' => 'form'])
            ->add('repetation', NumberType::class, ['label'=>false, 'required' => true, 'attr' => ['placeholder' => 'label.repetation'], 'translation_domain' => 'form'])
            ->add('submit', SubmitType::class, ['attr' => array('class' => 'btn btn-outline-primary'), 'label' => 'label.speichern', 'translation_domain' => 'form']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Repeat::class
        ]);

    }
}
