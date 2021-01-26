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
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('url', TextType::class, ['required' => true, 'label' => 'Jitsi Meet Server URL', 'translation_domain' => 'form', 'help'=>'Ohne "https://" angeben (z.B meet.jit.si)'])
            ->add('appId', TextType::class, ['required' => false,'label' => 'App ID', 'translation_domain' => 'form'])
            ->add('appSecret', PasswordType::class, ['required' => false,'label' => 'App Secret', 'translation_domain' => 'form'])
            ->add('smtpHost', TextType::class, ['required' => false,'label' => 'SMTP Hostname', 'translation_domain' => 'form'])
            ->add('smtpPort', TextType::class, ['required' => false,'label' => 'SMTP Port', 'translation_domain' => 'form'])
            ->add('smtpEncryption', TextType::class, ['required' => false,'label' => 'Encryption', 'translation_domain' => 'form'])
            ->add('smtpUsername', TextType::class, ['required' => false,'label' => 'SMTP Benutzername', 'translation_domain' => 'form'])
            ->add('smtpPassword', PasswordType::class, ['required' => false,'label' => 'SMTP Passwort', 'translation_domain' => 'form'])
            ->add('smtpEmail', TextType::class, ['required' => false,'label' => 'Absender Email', 'translation_domain' => 'form'])
            ->add('smtpSenderName', TextType::class, ['required' => false,'label' => 'Absender Name', 'translation_domain' => 'form'])
            ->add('logoUrl', TextType::class, ['required' => false,'label' => 'Link zu Logo', 'translation_domain' => 'form'])

            ->add('submit', SubmitType::class, ['attr' => array('class' => 'btn btn-outline-primary'), 'label' => 'Speichern', 'translation_domain' => 'form']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        ]);

    }
}
