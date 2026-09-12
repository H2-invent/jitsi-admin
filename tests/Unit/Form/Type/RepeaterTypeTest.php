<?php

namespace App\Tests\Unit\Form\Type;

use App\Entity\Repeat;
use App\Form\Type\RepeaterType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class RepeaterTypeTest extends FormTypeTestCase
{
    public function testBuildFormContainsExpectedFields(): void
    {
        $form = $this->formFactory()->create(RepeaterType::class, new Repeat());

        foreach ([
            'repeatType', 'repeaterDays', 'repeaterWeeks', 'repeatMontly', 'repeatYearly',
            'repatMonthRelativNumber', 'repatMonthRelativWeekday', 'repeatMonthlyRelativeHowOften',
            'repeatYearlyRelativeNumber', 'repeatYearlyRelativeWeekday', 'repeatYearlyRelativeMonth',
            'repeatYearlyRelativeHowOften', 'repetation', 'submit',
        ] as $field) {
            self::assertTrue($form->has($field), sprintf('Form is missing field "%s"', $field));
        }

        self::assertSame(ChoiceType::class, $this->innerTypeOf($form->get('repeatType')));
        self::assertSame(NumberType::class, $this->innerTypeOf($form->get('repeaterDays')));
        self::assertSame(SubmitType::class, $this->innerTypeOf($form->get('submit')));
        self::assertSame(Repeat::class, $form->getConfig()->getOption('data_class'));
    }

    public function testRepeatTypeChoices(): void
    {
        $form = $this->formFactory()->create(RepeaterType::class, new Repeat());
        $choices = $form->get('repeatType')->getConfig()->getOption('choices');

        self::assertSame([
            'option.daily' => 0,
            'option.weekly' => 1,
            'option.montly' => 2,
            'option.montlyRelative' => 3,
            'option.yearly' => 4,
            'option.yearlyRelative' => 5,
        ], $choices);
    }

    public function testSubmitMapsRepeatSettings(): void
    {
        $repeat = new Repeat();
        $form = $this->formFactory()->create(RepeaterType::class, $repeat, ['csrf_protection' => false]);
        $form->submit(['repeatType' => 1, 'repetation' => 4, 'repeaterWeeks' => 2]);

        self::assertTrue($form->isValid());
        self::assertSame(1, $repeat->getRepeatType());
        self::assertSame(4, $repeat->getRepetation());
        self::assertSame(2, $repeat->getRepeaterWeeks());
    }
}
