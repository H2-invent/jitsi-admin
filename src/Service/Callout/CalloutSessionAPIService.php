<?php

namespace App\Service\Callout;

use App\Entity\CallerId;
use App\Entity\CalloutSession;
use App\Service\ThemeService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CalloutSessionAPIService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TranslatorInterface    $translator,
        private ThemeService           $themeService,
        private UrlGeneratorInterface  $urlGenerator,
        private CalloutService         $calloutService,
        private ParameterBagInterface  $parameterBag,
        private LoggerInterface        $logger,
    )
    {
    }

    /**
     * This function returns the pending callouts.
     * The Callouts are formated in an array
     * @return array
     */
    public function getCalloutPool()
    {
        $calloutSession = $this->findCalloutSessionByState(CalloutSession::$INITIATED);
        $res = [];
        foreach ($calloutSession as $data) {
            $tmp = $this->buildCallerSessionPoolArray($data);
            if ($tmp) {
                $res[] = $tmp;
            }
        }
        return ['calls' => $res];
    }

    /**
     * This function build the Array which is expected from the API Consumer
     * @param CalloutSession $calloutSession
     * @return array
     */
    public function buildCallerSessionPoolArray(CalloutSession $calloutSession)
    {
        $this->logger->debug('lastdialed',
            [
                $calloutSession->getLastDialed(),
                (new \DateTime())->format('U'),
                (intval((new \DateTime())->format('U')) - $calloutSession->getLastDialed())
            ]);
        if ($calloutSession->getLastDialed() && ((intval((new \DateTime())->format('U')) - $calloutSession->getLastDialed()) < $this->parameterBag->get('CALLOUT_WAITING_TIME'))) {
            return null;
        } else {
            $calloutSession->setLastDialed((new \DateTime())->format('U'));
            $this->entityManager->persist($calloutSession);
            $this->entityManager->flush();
        }
        $pin = $this->entityManager->getRepository(CallerId::class)->findOneBy(['room' => $calloutSession->getRoom(), 'user' => $calloutSession->getUser()]);
        $roomId = $calloutSession->getRoom()->getCallerRoom();
        if ($pin && $roomId) {
            return [
                'state' => CalloutSession::$STATE[$calloutSession->getState()],
                'call_number' => $this->calloutService->getCallerIdForUser($calloutSession->getUser()),
                'sip_room_number' => $roomId->getCallerId(),
                'sip_pin' => $pin->getCallerId(),
                'display_name' => $this->translator->trans(
                    'Sie wurden von {name} eingeladen',
                    ['{name}' => $calloutSession->getInvitedFrom()->getFormatedName($this->themeService->getApplicationProperties('laf_showNameFrontend'))
                    ]
                ),
                'tag' => $calloutSession->getRoom()->getTag()?->getTitle(),
                'organisator' => $calloutSession->getRoom()->getModerator()->getFormatedName($this->themeService->getApplicationProperties('laf_showNameFrontend')),
                'title' => $calloutSession->getRoom()->getName(),
                'is_video' => (bool)$calloutSession->getUser()->getIsSipVideoUser(),
                'links' => [
                    'dial' => $this->urlGenerator->generate(
                        'callout_api_dial',
                        [
                            'calloutSessionId' => $calloutSession->getUid()
                        ]
                    )
                ]
            ];
        }
        return [];
    }

    /**
     * This Function searches all CalloutSessions in the Specific State
     * The State is defined in the CalloutSession Class in Static Variables
     * @param $state
     * @return CalloutSession[]|array|object[]
     */
    public function findCalloutSessionByState($state)
    {
        $calloutSession = $this->entityManager->getRepository(CalloutSession::class)->findBy(['state' => $state]);
        return $calloutSession;
    }

    /**
     * returns a pool of callout sessions which are in dialing state.
     * @return array[]
     */
    public function getDialPool()
    {
        $calloutSession = $this->findCalloutSessionByState(CalloutSession::$DIALED);
        $res = [];
        foreach ($calloutSession as $data) {
            $tmp = $this->buildCallerSessionPoolArray($data);
            if ($tmp) {
                $res[] = $tmp;
            }
        }
        return ['calls' => $res];
    }

    /**
     * Returns the Pool of callout Sessions which are in an on hold state.
     * @return array[]
     */
    public function getOnHoldPool()
    {
        $calloutSession = $this->entityManager->getRepository(CalloutSession::class)->findonHoldCalloutSessions();
        $res = [];
        foreach ($calloutSession as $data) {
            $tmp = $this->buildCallerSessionPoolArray($data);
            if ($tmp) {
                $res[] = $tmp;
            }
        }
        return ['calls' => $res];
    }
}
