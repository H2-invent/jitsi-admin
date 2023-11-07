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
use App\Entity\KeycloakGroupsToServers;
use League\CommonMark\Inline\Element\Text;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('url', TextType::class, ['required' => true, 'label' => 'lable.serverUrl', 'translation_domain' => 'form', 'help' => 'help.serverUrl'])
            ->add('serverName', TextType::class, ['required' => true, 'label' => 'label.serverName', 'translation_domain' => 'form', 'help' => 'help.serverName'])
            ->add('appId', TextType::class, ['required' => false, 'label' => 'label.appId', 'translation_domain' => 'form'])
            ->add('appSecret', TextType::class, ['required' => false, 'label' => 'label.appSecret', 'translation_domain' => 'form'])
            ->add('corsHeader', CheckboxType::class, ['required' => false, 'label' => 'label.corsHeader', 'help' => 'help.corsHeader', 'translation_domain' => 'form'])
            ->add(
                'keycloakGroups',
                CollectionType::class,
                ['entry_type' => KeycloakGroupsToServersType::class,
                    'entry_options' => ['label' => 'false',],
                    'allow_add' => true,
                    'allow_delete' => true,
                    'by_reference' => false,
                    'label' => false,
                    'translation_domain' => 'form',
                ]
            )
            ->add('url', TextType::class, ['required' => true, 'label' => 'lable.serverUrl', 'translation_domain' => 'form', 'help' => 'help.serverUrl'])
            ->add('featureEnableByJWT', CheckboxType::class, ['required' => false, 'label' => 'label.featureEnalbeByJwt', 'help' => 'help.featureEnalbeByJwt', 'translation_domain' => 'form'])
            ->add('enforceE2e', CheckboxType::class, ['required' => false, 'label' => 'label.enforceE2e', 'help' => 'help.enforceE2e', 'translation_domain' => 'form'])
            ->add('disallowFirefox', CheckboxType::class, ['required' => false, 'label' => 'label.disallowFirefox', 'help' => 'help.disallowFirefox', 'translation_domain' => 'form'])
            ->add('disableFilmstripe', CheckboxType::class, ['required' => false, 'label' => 'label.disableFilmstripe', 'help' => 'help.disableFilmstripe', 'translation_domain' => 'form'])
            ->add('disableEtherpad', CheckboxType::class, ['required' => false, 'label' => 'label.disableEtherpad', 'help' => 'help.disableEtherpad', 'translation_domain' => 'form'])
            ->add('disableWhiteboard', CheckboxType::class, ['required' => false, 'label' => 'label.disableWhiteboard', 'help' => 'help.disableWhiteboard', 'translation_domain' => 'form'])
            ->add('disableChat', CheckboxType::class, ['required' => false, 'label' => 'label.disableChat', 'help' => 'help.disableChat', 'translation_domain' => 'form'])
            ->add('prefixRoomUidWithHash', CheckboxType::class, ['required' => false, 'label' => 'label.prefixRoomUidWithHash', 'help' => 'help.prefixRoomUidWithHash', 'translation_domain' => 'form'])

            ->add('allowIp', TextType::class, ['required' => false, 'label' => 'label.allowIp', 'help' => 'help.allowIp', 'translation_domain' => 'form'])

            ->add(
                'jwtModeratorPosition',
                ChoiceType::class,
                [
                    'required' => true,
                    'label' => 'label.jwtModeratorPosition',
                    'translation_domain' => 'form',
                    'choices' => [
                        'option.root' => 0,
                        'option.user' => 1,
                    ]
                ]
            )
            ->add('submit', SubmitType::class, ['attr' => ['class' => 'btn btn-primary'], 'label' => 'label.speichern', 'translation_domain' => 'form']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => Server::class,
            ]
        );
    }
}
