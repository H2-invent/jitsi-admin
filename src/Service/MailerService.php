<?php
/**
 * Created by PhpStorm.
 * User: Emanuel
 * Date: 03.10.2019
 * Time: 19:01
 */

namespace App\Service;


use App\Entity\Server;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;

class MailerService
{

    private $smtp;
    private $swift;
    private $parameter;
    private $kernel;
    public function __construct(ParameterBagInterface $parameterBag, TransportInterface $smtp, \Swift_Mailer $swift_Mailer, KernelInterface $kernel)
    {
        $this->smtp = $smtp;
        $this->swift = $swift_Mailer;
        $this->parameter = $parameterBag;
        $this->kernel = $kernel;
    }

    public function sendViaCustomSmtp(Server $server) {
        $transport = (new \Swift_SmtpTransport(
            $server->getSmtpHost(),
            $server->getSmtpPort(),
            $server->getSmtpEncryption()))
            ->setUsername($server->getSmtpUsername())
            ->setPassword($server->getSmtpPassword());

        $customMailer = new \Swift_Mailer($transport);

        return $customMailer;
    }

    public function sendEmail($to, $betreff, $content, $server, $attachment = array())
    {
        $this->sendViaSwiftMailer($to, $betreff, $content, $server, $attachment);
    }

    private function sendViaSwiftMailer($to, $betreff, $content, Server $server, $attachment = array())
    {
        if ($server->getSmtpHost()){
            $sender = $server->getSmtpEmail();
            $senderName = $server->getSmtpSenderName();
        }else {
            $sender = $this->parameter->get('registerEmailName');
            $senderName = $server->get('defaultEmail');
        }
        $message = (new \Swift_Message($betreff))
            ->setFrom(array($sender => $senderName))
            ->setTo($to)
            ->setBody(

                $content
                , 'text/html'
            );
        foreach ($attachment as $data) {
            $message->attach(new \Swift_Attachment($data['body'], $data['filename'], $data['type']));
        };
        if ($server->getSmtpHost()) {
            if ($this->kernel->getEnvironment() === 'dev'){
               $message->setTo($this->parameter->get('delivery_addresses'));
            }
            $this->sendViaCustomSmtp($server)->send($message);
        }else {
            $this->swift->send($message);
        }
    }
}
