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
            ->add(
                'repeatType',
                ChoiceType::class,
                ['choices' => [
                    'option.daily' => 0,
                    'option.weekly' => 1,
                    'option.montly' => 2,
                    'option.montlyRelative' => 3,
                    'option.yearly' => 4,
                    'option.yearlyRelative' => 5,
                ],
                    'label' => 'label.repeatType',
                    'translation_domain' => 'form']
            )
//            ->add('weekday', ChoiceType::class, [
//                'choices' => [
//                    'option.sunday' => 0,
//                    'option.monday' => 1,
//                    'option.tuesday' => 2,
//                    'option.wednesday' => 3,
//                    'option.thursday' => 4,
//                    'option.friday' => 5,
//                    'option.saturday' => 6,
//
//                ],
//                'required' => true,
//                'label' => 'label.weekday',
//                'expanded' => true,
//                'multiple' => true,
//                'translation_domain' => 'form'
//            ])
            ->add('repeaterDays', NumberType::class, ['label' => false, 'required' => false, 'attr' => ['placeholder' => 'label.repeaterDays'], 'translation_domain' => 'form'])
            ->add('repeaterWeeks', NumberType::class, ['label' => false, 'required' => false, 'attr' => ['placeholder' => 'label.repeaterWeeks'], 'translation_domain' => 'form'])
            ->add('repeatMontly', NumberType::class, ['label' => false, 'required' => false, 'attr' => ['placeholder' => 'label.repeatMontly'], 'translation_domain' => 'form'])
            ->add('repeatYearly', NumberType::class, ['label' => false, 'required' => false, 'attr' => ['placeholder' => 'label.repeatYearly'], 'translation_domain' => 'form'])
            ->add(
                'repatMonthRelativNumber',
                ChoiceType::class,
                ['choices' => [
                    'option.first' => 0,
                    'option.second' => 1,
                    'option.third' => 2,
                    'option.fourth' => 3,
                    'option.fifth' => 4,
                    'option.last' => 5,
                ],
                    'label' => 'label.montlyRelativeNumber',
                    'translation_domain' => 'form']
            )
            ->add(
                'repatMonthRelativWeekday',
                ChoiceType::class,
                ['choices' => [
                    'option.sunday' => 0,
                    'option.monday' => 1,
                    'option.tuesday' => 2,
                    'option.wednesday' => 3,
                    'option.thursday' => 4,
                    'option.friday' => 5,
                    'option.saturday' => 6,

                ],
                    'label' => 'label.montlyRelativeWeekday',
                    'translation_domain' => 'form']
            )
            ->add('repeatMonthlyRelativeHowOften', NumberType::class, ['label' => false, 'required' => false, 'attr' => ['placeholder' => 'label.repeatMontly'], 'translation_domain' => 'form'])
            ->add(
                'repeatYearlyRelativeNumber',
                ChoiceType::class,
                ['choices' => [
                    'option.first' => 0,
                    'option.second' => 1,
                    'option.third' => 2,
                    'option.fourth' => 3,
                    'option.fifth' => 4,
                    'option.last' => 5,
                ],
                    'label' => 'label.montlyRelativeNumber',
                    'translation_domain' => 'form']
            )
            ->add(
                'repeatYearlyRelativeWeekday',
                ChoiceType::class,
                ['choices' => [
                    'option.sunday' => 0,
                    'option.monday' => 1,
                    'option.tuesday' => 2,
                    'option.wednesday' => 3,
                    'option.thursday' => 4,
                    'option.friday' => 5,
                    'option.saturday' => 6,

                ],
                    'label' => 'label.montlyRelativeWeekday',
                    'translation_domain' => 'form']
            )
            ->add(
                'repeatYearlyRelativeMonth',
                ChoiceType::class,
                ['choices' => [
                    'option.january' => 0,
                    'option.february' => 1,
                    'option.march' => 2,
                    'option.april' => 3,
                    'option.may' => 4,
                    'option.june' => 5,
                    'option.july' => 6,
                    'option.august' => 7,
                    'option.septembre' => 8,
                    'option.octobre' => 9,
                    'option.novembre' => 10,
                    'option.decembre' => 11,

                ],
                    'label' => 'label.montlyRelativeMonth',
                    'translation_domain' => 'form']
            )
            ->add('repeatYearlyRelativeHowOften', NumberType::class, ['label' => false, 'required' => false, 'attr' => ['placeholder' => 'label.repeatYearly'], 'translation_domain' => 'form'])
            ->add('repetation', NumberType::class, ['label' => false, 'required' => true, 'attr' => ['placeholder' => 'label.repetation'], 'translation_domain' => 'form'])
            ->add('submit', SubmitType::class, ['attr' => ['class' => 'btn btn-outline-primary'], 'label' => 'label.speichern', 'translation_domain' => 'form']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => Repeat::class
            ]
        );
    }
}
