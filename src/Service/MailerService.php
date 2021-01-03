<?php
/**
 * Created by PhpStorm.
 * User: Emanuel
 * Date: 03.10.2019
 * Time: 19:01
 */

namespace App\Service;


use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;

class MailerService
{

    private $smtp;
    private $swift;
    private $parameter;

    public function __construct(ParameterBagInterface $parameterBag, TransportInterface $smtp, \Swift_Mailer $swift_Mailer)
    {
        $this->smtp = $smtp;
        $this->swift = $swift_Mailer;
        $this->parameter = $parameterBag;
    }

    public function sendEmail($sender, $from, $to, $betreff, $content, $attachment = array())
    {
        $this->sendViaSwiftMailer($sender, $from, $to, $betreff, $content, $attachment);
    }

    private function sendViaSwiftMailer($sender, $from, $to, $betreff, $content, $attachment = array())
    {
        $message = (new \Swift_Message($betreff))
            ->setFrom(array($from => $sender))
            ->setTo($to)
            ->setBody(

                $content
                , 'text/html'
            );
        foreach ($attachment as $data) {
            $message->attach(new \Swift_Attachment($data['body'], $data['filename'], $data['type']));
        };
        $this->swift->send($message);
    }
}
