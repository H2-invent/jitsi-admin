<?php
/**
 * Created by PhpStorm.
 * User: Emanuel
 * Date: 17.09.2019
 * Time: 20:29
 */

namespace App\Form\Type;


use App\Entity\AuditTomAbteilung;
use App\Entity\Server;
use League\CommonMark\Inline\Element\Text;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EnterpriseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('smtpHost', TextType::class, ['required' => false, 'label' => 'label.serverSmtpHostName', 'translation_domain' => 'form'])
            ->add('smtpPort', TextType::class, ['required' => false, 'label' => 'label.serverSmtpHostPort', 'translation_domain' => 'form'])
            ->add('smtpEncryption', ChoiceType::class, ['required' => false, 'label' => 'label.serverSmtpEncryption', 'translation_domain' => 'form', 'choices'=>
            array('choice.tls'=>'tls','choice.ssl'=>'ssl','choice.none'=>null)])
            ->add('smtpUsername', TextType::class, ['required' => false, 'label' => 'label.serverSmtpUsername', 'translation_domain' => 'form'])
            ->add('smtpPassword', TextType::class, ['required' => false, 'label' => 'label.serverSmtpPassword', 'translation_domain' => 'form'])
            ->add('smtpEmail', TextType::class, ['required' => false, 'label' => 'label.serverSmtpSenderEmail', 'translation_domain' => 'form'])
            ->add('smtpSenderName', TextType::class, ['required' => false, 'label' => 'label.serverSmtpSenderName', 'translation_domain' => 'form'])
            ->add('logoUrl', TextType::class, ['required' => false, 'label' => 'label.serverLintLogo', 'translation_domain' => 'form'])
            ->add('privacyPolicy', TextType::class, ['required' => false, 'label' => 'label.serverPrivacyPolicy', 'translation_domain' => 'form'])
            ->add('serverEmailHeader', TextType::class, ['required' => false, 'label' => 'label.serverEmailHeader', 'translation_domain' => 'form'])
            ->add('serverEmailBody', TextareaType::class, ['required' => false, 'label' => 'label.serverEmailBody', 'translation_domain' => 'form'])
            ->add('apiKey', TextType::class, ['required' => false, 'attr' => array('readonly' => 'readonly',), 'label' => 'label.apiKey', 'translation_domain' => 'form'])
            ->add('showStaticBackgroundColor',CheckboxType::class,['required'=>false,'label'=>'label.schowStaticBackgroundColor', 'translation_domain' => 'form'])
            ->add('staticBackgroundColor',ColorType::class,['html5'=>true, 'required'=>false,'label'=>'label.staticBackgroundColor', 'translation_domain' => 'form'])
            ->add('serverBackgroundImage', ImageType::class,['label' => 'label.serverBackgroundImage', 'translation_domain' => 'form'])

            ->add('submit', SubmitType::class, ['attr' => array('class' => 'btn btn-outline-primary'), 'label' => 'label.speichern', 'translation_domain' => 'form']);

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        ]);

    }
}
