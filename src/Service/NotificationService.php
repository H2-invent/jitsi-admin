<?php
/**
 * Created by PhpStorm.
 * User: Emanuel
 * Date: 03.10.2019
 * Time: 19:01
 */

namespace App\Service;

use App\Entity\User;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;


class NotificationService
{
    private $mailer;
    private $parameterBag;

    public function __construct(MailerService $mailerService, ParameterBagInterface $parameterBag)
    {
        $this->mailer = $mailerService;
        $this->parameterBag = $parameterBag;
    }

    function sendNotification($content, $subject, User $user)
    {
        $this->mailer->sendEmail(
            $this->parameterBag->get('registerEmailName'),
            $this->parameterBag->get('defaultEmail'),
            $user->getEmail(),
            $subject,
            $content
        );

        return true;
    }


}
