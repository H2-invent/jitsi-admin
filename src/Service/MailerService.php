<?php
/**
 * Created by PhpStorm.
 * User: Emanuel
 * Date: 03.10.2019
 * Time: 19:01
 */

namespace App\Service;


use App\Entity\Server;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;

class MailerService
{

    private $smtp;
    private $swift;
    private $parameter;
    private $kernel;
    private $logger;
    private $customMailer;
    private $userName;
    private $licenseService;
    private $serverService;

    public function __construct(ServerService $serverService, LicenseService $licenseService,LoggerInterface $logger, ParameterBagInterface $parameterBag, TransportInterface $smtp, \Swift_Mailer $swift_Mailer, KernelInterface $kernel)
    {
        $this->smtp = $smtp;
        $this->swift = $swift_Mailer;
        $this->parameter = $parameterBag;
        $this->kernel = $kernel;
        $this->logger = $logger;
        $this->customMailer = null;
        $this->userName = null;
        $this->licenseService = $licenseService;
        $this->serverService = $serverService;
    }

    public function buildTransport(Server $server)
    {

        if ($server->getSmtpHost()) {
            $this->logger->info('Build new Transport: ' . $server->getSmtpHost());
            $tmpTransport = (new \Swift_SmtpTransport(
                $server->getSmtpHost(),
                $server->getSmtpPort(),
                $server->getSmtpEncryption()))
                ->setUsername($server->getSmtpUsername())
                ->setPassword($server->getSmtpPassword());
            $tmpMailer = new \Swift_Mailer($tmpTransport);
            if ($this->userName != $server->getSmtpUsername()) {
                $this->userName = $server->getSmtpUsername();
                $this->logger->info('The Transport is new and we take him');
                $this->customMailer = $tmpMailer;
            }
        }
    }

    public function sendEmail($to, $betreff, $content, Server $server, $replyTo = null, $attachment = array()):bool
    {
        try {
            $this->logger->info('Mail To: ' . $to);
            $res = $this->sendViaSwiftMailer($to, $betreff, $content, $server,$replyTo, $attachment);

        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $res;
    }

    private function sendViaSwiftMailer($to, $betreff, $content, Server $server, $replyTo = null, $attachment = array()):bool
    {
        $this->buildTransport($server);
        if ($server->getSmtpHost() && $this->licenseService->verify($server)) {
            $this->logger->info($server->getSmtpEmail());
            $sender = $server->getSmtpEmail();
            $senderName = $server->getSmtpSenderName();
        } else {
            $sender = $this->parameter->get('registerEmailAdress');
            $senderName = $this->parameter->get('registerEmailName');
        }
        $message = (new \Swift_Message($betreff))
            ->setFrom(array($sender => $senderName))
            ->setTo($to)
            ->setBody(
                $content
                , 'text/html'
            );
        if($replyTo){
            $message->setReplyTo($replyTo);
        }
        foreach ($attachment as $data) {
            $message->attach(new \Swift_Attachment($data['body'], $this->serverService->slugify($data['filename']), $data['type']));
        };

        try {
            if ($server->getSmtpHost()) {
                if ($this->kernel->getEnvironment() === 'dev') {
                    $message->setTo($this->parameter->get('delivery_addresses'));
                }
                $this->logger->info('Send from Custom Mailer');
                $this->customMailer->send($message);
            } else {
                $this->swift->send($message);
            }
        } catch (\Exception $e) {
            $this->swift->send($message);
            $this->logger->error($e->getMessage());
            return false;
        }
        return true;
    }
}
