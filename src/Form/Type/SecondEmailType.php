<?php

/**
 * Created by PhpStorm.
 * User: Emanuel
 * Date: 17.09.2019
 * Time: 20:29
 */

namespace App\Form\Type;

use App\Entity\User;
use App\Service\ThemeService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class SecondEmailType extends AbstractType
{
    public function __construct(private ThemeService $themeService)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add('profilePicture', ImageType::class, ['label' => 'label.profilImage', 'translation_domain' => 'form'])
            ->add('submit', SubmitType::class, ['attr' => ['class' => 'btn btn-primary'], 'label' => 'label.speichern', 'translation_domain' => 'form']);
        if ($this->themeService->getApplicationProperties('allowTimeZoneSwitch')) {
            $builder->add('timeZone', \Symfony\Component\Form\Extension\Core\Type\TimezoneType::class, ['required' => false, 'label' => 'label.timezone', 'translation_domain' => 'form']);
        }

        if (!$this->themeService->getTheme() || $this->themeService->getApplicationProperties('profileAllowSecondEmail')) {
            $builder->add('secondEmail', TextType::class, ['required' => false, 'label' => 'label.secondEmail', 'translation_domain' => 'form', 'help' => 'help.secondEmail']);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => User::class
            ]
        );
    }
}
