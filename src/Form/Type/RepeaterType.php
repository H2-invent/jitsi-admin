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
use App\Enums\RepeatMonthEnum;
use App\Enums\RepeatNumberEnum;
use App\Enums\RepeatTypeEnum;
use App\Enums\RepeatWeekdayEnum;
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
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->add(
                'repeatType',
                ChoiceType::class,
                ['choices' => RepeatTypeEnum::cases(),
                    'choice_label' => fn (RepeatTypeEnum $choice) => $choice->translationKey(),
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
            ->add('repeaterDays', NumberType::class, ['label' => 'label.repeaterDays', 'required' => false, 'attr' => ['placeholder' => 'label.repeaterDays'], 'translation_domain' => 'form'])
            ->add('repeaterWeeks', NumberType::class, ['label' => 'label.repeaterWeeks', 'required' => false, 'attr' => ['placeholder' => 'label.repeaterWeeks'], 'translation_domain' => 'form'])
            ->add('repeatMontly', NumberType::class, ['label' => 'label.repeatMontly', 'required' => false, 'attr' => ['placeholder' => 'label.repeatMontly'], 'translation_domain' => 'form'])
            ->add('repeatYearly', NumberType::class, ['label' => 'label.repeatYearly', 'required' => false, 'attr' => ['placeholder' => 'label.repeatYearly'], 'translation_domain' => 'form'])
            ->add(
                'repatMonthRelativNumber',
                ChoiceType::class,
                ['choices' => RepeatNumberEnum::cases(),
                    'choice_label' => fn (RepeatNumberEnum $choice) => $choice->translationKey(),
                    'label' => 'label.montlyRelativeNumber',
                    'translation_domain' => 'form']
            )
            ->add(
                'repatMonthRelativWeekday',
                ChoiceType::class,
                ['choices' => RepeatWeekdayEnum::cases(),
                    'choice_label' => fn (RepeatWeekdayEnum $choice) => $choice->translationKey(),
                    'label' => 'label.montlyRelativeWeekday',
                    'translation_domain' => 'form']
            )
            ->add('repeatMonthlyRelativeHowOften', NumberType::class, ['label' => 'label.repeatMontly', 'required' => false, 'attr' => ['placeholder' => 'label.repeatMontly'], 'translation_domain' => 'form'])
            ->add(
                'repeatYearlyRelativeNumber',
                ChoiceType::class,
                ['choices' => RepeatNumberEnum::cases(),
                    'choice_label' => fn (RepeatNumberEnum $choice) => $choice->translationKey(),
                    'label' => 'label.montlyRelativeNumber',
                    'translation_domain' => 'form']
            )
            ->add(
                'repeatYearlyRelativeWeekday',
                ChoiceType::class,
                ['choices' => RepeatWeekdayEnum::cases(),
                    'choice_label' => fn (RepeatWeekdayEnum $choice) => $choice->translationKey(),
                    'label' => 'label.montlyRelativeWeekday',
                    'translation_domain' => 'form']
            )
            ->add(
                'repeatYearlyRelativeMonth',
                ChoiceType::class,
                ['choices' => RepeatMonthEnum::cases(),
                    'choice_label' => fn (RepeatMonthEnum $choice) => $choice->translationKey(),
                    'label' => 'label.montlyRelativeMonth',
                    'translation_domain' => 'form']
            )
            ->add('repeatYearlyRelativeHowOften', NumberType::class, ['label' => 'label.repeatYearly', 'required' => false, 'attr' => ['placeholder' => 'label.repeatYearly'], 'translation_domain' => 'form'])
            ->add('repetation', NumberType::class, ['label' => 'label.repetation', 'required' => true, 'attr' => ['placeholder' => 'label.repetation'], 'translation_domain' => 'form'])
            ->add('submit', SubmitType::class, ['attr' => ['class' => 'btn btn-outline-primary'], 'label' => 'label.speichern', 'translation_domain' => 'form']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Repeat::class
            ]
        );
    }
}
