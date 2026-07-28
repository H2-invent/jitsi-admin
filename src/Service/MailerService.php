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
use App\Message\CustomMailerMessage;
use App\Service\Theme\ThemeService;
use App\UtilsHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailerService
{
    private ?CustomMailerMessage $customMailer = null;

    public function __construct(
        private MessageBusInterface   $bus,
        private LicenseService        $licenseService,
        private LoggerInterface       $logger,
        private ParameterBagInterface $parameter,
        private KernelInterface       $kernel,
        private MailerInterface       $mailer,
        private ThemeService          $themeService
    ) {}

    public function sendEmail(
        User $user,
        string $betreff,
        string $content,
        Server $server,
        ?string $replyTo = null,
        ?Rooms $rooms = null,
        array $attachment = []
    ): bool {
        $to = $user->getEmail();
        $cc = $this->extractValidEmails($user->getSecondEmail());

        if ($user->getLdapUserProperties() && !$this->isValidEmail($to)) {
            $this->logger->debug('Invalid LDAP user email. Skipping.');
            return true;
        }

        if ($this->parameter->get('DISALLOW_ALL_EMAILS') === 1) {
            $this->logger->debug('Global email sending disabled.');
            return true;
        }

        try {
            $this->logger->info("Mail To: $to");
            return $this->sendViaMailer($to, $betreff, $content, $server, $replyTo, $rooms, $attachment, $cc);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
    }

    public function sendPlainMail(string $to, string $subject, string $message): void
    {
        $server = new Server();
        foreach (explode(',', $to) as $email) {
            $this->sendViaMailer(trim($email), $subject, $message, $server);
        }
    }

    private function sendViaMailer(
        string $to,
        string $betreff,
        string $content,
        Server $server,
        ?string $replyTo = null,
        ?Rooms $rooms = null,
        array $attachment = [],
        array $cc = []
    ): bool {
        $this->buildTransport($server);

        [$fromAddress, $fromName] = $this->resolveSender($server, $rooms);

        $email = $this->createEmailMessage($to, $betreff, $content, $fromAddress, $fromName, $replyTo, $attachment, $cc);
        $this->applyRoomThemeSender($email, $rooms);
        $this->applyReturnPath($email, $rooms);

        try {
            if ($server->getSmtpHost()) {
                if ($this->kernel->getEnvironment() === 'dev') {
                    foreach ($this->parameter->get('delivery_addresses') as $devRecipient) {
                        $email->to($devRecipient);
                    }
                }
                if ($rooms?->getModerator() && $this->isValidEmail($rooms->getModerator()->getEmail())) {
                    $this->customMailer?->setAbsender($rooms->getModerator()->getEmail());
                }
                if ($rooms) {
                    $this->customMailer?->setRoomId($rooms->getId());
                }
                $this->customMailer?->setTo($to);

                $this->logger->info('Sending via Custom Mailer');
                $this->bus->dispatch(
                    $this->customMailer->send($email),
                    [new DelayStamp(rand(1000, 10000))]
                );
            } else {
                $this->mailer->send($email);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $fallbackEmail = new Email();
            $fallbackEmail->from(new Address(
                $this->parameter->get('registerEmailAdress'),
                $this->parameter->get('registerEmailName')
            ));
            $this->mailer->send($fallbackEmail);
            throw $e;
        }

        return true;
    }

    public function buildTransport(Server $server): void
    {
        if (!$server->getSmtpHost()) return;

        $this->logger->info('Building new Transport: ' . $server->getSmtpHost());
        $dsn = $server->getSmtpUsername()
            ? sprintf(
                'smtp://%s:%s@%s:%s?verify_peer=false&auto_tls=false',
                urlencode($server->getSmtpUsername()),
                urlencode($server->getSmtpPassword()),
                $server->getSmtpHost(),
                $server->getSmtpPort()
            )
            : sprintf('smtp://%s:%s?verify_peer=false&auto_tls=false', $server->getSmtpHost(), $server->getSmtpPort());

        $this->customMailer = new CustomMailerMessage($dsn);
    }

    private function createEmailMessage(
        string $to,
        string $subject,
        string $htmlContent,
        string $fromEmail,
        string $fromName,
        ?string $replyTo,
        array $attachments,
        array $cc
    ): Email {
        $email = (new Email())
            ->subject($subject)
            ->from(new Address($fromEmail, $fromName))
            ->to($to)
            ->html($htmlContent);

        if ($replyTo && $this->isValidEmail($replyTo)) {
            $email->replyTo($replyTo);
        }

        foreach ($attachments as $file) {
            $email->attach($file['body'], UtilsHelper::slugifywithDot($file['filename']), $file['type']);
        }

        if ($this->kernel->getEnvironment() !== 'dev') {
            foreach ($cc as $emailCc) {
                $email->addCc($emailCc);
            }
        }

        return $email;
    }

    private function applyRoomThemeSender(Email $email, ?Rooms $rooms): void
    {
        $theme = $rooms ? $this->themeService->getTheme($rooms) : null;
        if (!$theme) return;

        $name = $theme['EMAIL_SENDER_NAME'] ?? '';
        $address = $theme['EMAIL_SENDER_ADDRESS'] ?? '';

        if ($address) {
            $full = $name ? "$name<$address>" : $address;
            $email->from(Address::create($full));
        }
    }

    private function applyReturnPath(Email $email, ?Rooms $rooms): void
    {
        if ($this->parameter->get('STRICT_EMAIL_SET_ENVELOP_FROM') === 1 && $rooms?->getModerator()) {
            $moderatorEmail = $rooms->getModerator()->getEmail();
            if ($this->isValidEmail($moderatorEmail)) {
                $email->returnPath($moderatorEmail);
            }
        }
    }

    private function resolveSender(Server $server, ?Rooms $rooms): array
    {
        if ($server->getSmtpHost() && $this->licenseService->verify($server)) {
            return [$server->getSmtpEmail(), $server->getSmtpSenderName()];
        }

        if ($rooms?->getModerator() && $this->parameter->get('emailSenderIsModerator')) {
            $moderator = $rooms->getModerator();
            return [
                $this->parameter->get('registerEmailAdress'),
                $moderator->getFirstName() . ' ' . $moderator->getLastName()
            ];
        }

        return [
            $this->parameter->get('registerEmailAdress'),
            $this->parameter->get('registerEmailName')
        ];
    }

    private function extractValidEmails(?string $emails): array
    {
        $list = [];
        foreach (explode(',', $emails ?? '') as $email) {
            $email = trim($email);
            if ($this->isValidEmail($email)) {
                $list[] = $email;
            }
        }
        return $list;
    }

    private function isValidEmail(?string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function getCustomMailer(): ?CustomMailerMessage
    {
        return $this->customMailer;
    }

    public function setCustomMailer(?CustomMailerMessage $customMailer): void
    {
        $this->customMailer = $customMailer;
    }

}
