<?php
/**
 * Created by PhpStorm.
 * User: Emanuel
 * Date: 03.10.2019
 * Time: 19:01
 */

namespace App\Service;


use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\User;
use App\UtilsHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailerService
{

    private $parameter;
    private $kernel;
    private $logger;
    private $customMailer;
    private $userName;
    private $licenseService;
    private $mailer;

    public function __construct(LicenseService $licenseService, LoggerInterface $logger, ParameterBagInterface $parameterBag, KernelInterface $kernel, MailerInterface $mailer)
    {

        $this->parameter = $parameterBag;
        $this->kernel = $kernel;
        $this->logger = $logger;
        $this->customMailer = null;
        $this->userName = null;
        $this->licenseService = $licenseService;
        $this->mailer = $mailer;
    }

    public function buildTransport(Server $server)
    {

        if ($server->getSmtpHost()) {
            $this->logger->info('Build new Transport: ' . $server->getSmtpHost());
            if ($this->userName != $server->getSmtpUsername()) {
                $this->userName = $server->getSmtpUsername();
                $this->logger->info('The Transport is new and we take him');
                $dsn = 'smtp://' . $server->getSmtpUsername() . ':' . $server->getSmtpPassword() . '@' . $server->getSmtpHost() . ':' . $server->getSmtpPort() . '?verify_peer=false';
            }else{
                $dsn = 'smtp://' . $server->getSmtpHost() . ':' . $server->getSmtpPort() . '?verify_peer=false';
            }
            $this->customMailer = Transport::fromDsn($dsn);
            return true;
        }
        return false;
    }

    public function sendEmail(User $user, $betreff, $content, Server $server, $replyTo = null, Rooms $rooms = null, $attachment = array()): bool
    {
        $to = $user->getEmail();
        $cc = array();
        if ($user->getSecondEmail()) {
            foreach (explode(',', $user->getSecondEmail()) as $data) {
                $e = trim($data);
                if (filter_var($e, FILTER_VALIDATE_EMAIL)) {
                    $cc[] = $e;
                }
            }
        }
        if ($user->getLdapUserProperties() && filter_var($to, FILTER_VALIDATE_EMAIL) == false) {
            $this->logger->debug('We sent no email, because the User is an LDAP User and the email is not a valid Email');
            return true;
        }
        if ($this->parameter->get('DISALLOW_ALL_EMAILS') === 1) {
            $this->logger->debug('We don`t send emails at all so we  dont send any emails here');
            return true;
        }
        try {
            $this->logger->info('Mail To: ' . $to);
            $res = $this->sendViaMailer($to, $betreff, $content, $server, $replyTo, $rooms, $attachment, $cc);

        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $res = false;
        }
        return $res;
    }


    private function sendViaMailer($to, $betreff, $content, Server $server, $replyTo = null, Rooms $rooms = null, $attachment = array(), $cc = array()): bool
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
        if ($rooms && $rooms->getModerator() && $this->parameter->get('emailSenderIsModerator')) {
            $senderName = $rooms->getModerator()->getFirstName() . ' ' . $rooms->getModerator()->getLastName();
        }
        $message = (new Email())
            ->subject($betreff)
            ->from(new Address($sender, $senderName))
            ->to($to)
            ->html($content);

        if ($replyTo) {
            if (filter_var($replyTo, FILTER_VALIDATE_EMAIL) == true) {
                $message->replyTo($replyTo);
            }
        }
        foreach ($attachment as $data) {
            $message->attach($data['body'], UtilsHelper::slugifywithDot($data['filename']), $data['type']);
        };
        if ($this->kernel->getEnvironment() !== 'dev') {
            foreach ($cc as $data) {
                $message->addCc($data);
            }
        }

        if ($this->parameter->get('STRICT_EMAIL_SET_ENVELOP_FROM') == 1) {
            if ($rooms && $rooms->getModerator()->getEmail() && filter_var($rooms->getModerator()->getEmail(), FILTER_VALIDATE_EMAIL) == true) {
                $message->returnPath($rooms->getModerator()->getEmail());
            }
        }

        try {
            if ($server->getSmtpHost()) {
                if ($this->kernel->getEnvironment() === 'dev') {
                    foreach ($this->parameter->get('delivery_addresses') as $data){
                        $message->to($data);
                    }
                }
                $this->logger->info('Send from Custom Mailer');
                $this->customMailer->send($message);
            } else {
                $this->mailer->send($message);
            }
        } catch (\Exception $e) {
            //we reset the sender name if the individual email is not working
            $sender = $this->parameter->get('registerEmailAdress');
            $senderName = $this->parameter->get('registerEmailName');
            $message->from(new Address($sender, $senderName));
            $this->mailer->send($message);
            $this->logger->error($e->getMessage());
            throw $e;
        }
        return true;
    }
}
