<?php

namespace App\Service\Callout;

use App\Entity\CallerId;
use App\Entity\CallerSession;
use App\Entity\CalloutSession;
use App\Service\ThemeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CalloutSessionAPIService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TranslatorInterface    $translator,
        private ThemeService           $themeService,
        private UrlGeneratorInterface  $urlGenerator,
        private CalloutService         $calloutService
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
        $res = array();
        foreach ($calloutSession as $data) {

            $tmp = $this->buildCallerSessionPoolArray($data);
            if ($tmp) {
                $res[] = $tmp;
            }
        }
        return array('calls' => $res);
    }

    /**
     * This function build the Array which is expected from the API Consumer
     * @param CalloutSession $calloutSession
     * @return array
     */
    public function buildCallerSessionPoolArray(CalloutSession $calloutSession)
    {
        $pin = $this->entityManager->getRepository(CallerId::class)->findOneBy(array('room' => $calloutSession->getRoom(), 'user' => $calloutSession->getUser()));
        $roomId = $calloutSession->getRoom()->getCallerRoom();
        if ($pin && $roomId) {
            return array(
                'call_number' => $this->calloutService->getCallerIdForUser($calloutSession->getUser()),
                'sip_room_number' => $roomId->getCallerId(),
                'sip_pin' => $pin->getCallerId(),
                'display_name' => $this->translator->trans('Sie wurden von {name} eingeladen',
                    array('{name}' => $calloutSession->getInvitedFrom()->getFormatedName($this->themeService->getApplicationProperties('laf_showNameFrontend'))
                    )
                ),
                'tag' => $calloutSession->getRoom()->getTag() ? $calloutSession->getRoom()->getTag()->getTitle() : 'none',
                'organisator' => $calloutSession->getRoom()->getModerator()->getFormatedName($this->themeService->getApplicationProperties('laf_showNameFrontend')),
                'title' => $calloutSession->getRoom()->getName(),
                'links' => array(
                    'dial' => $this->urlGenerator->generate('callout_api_dial',
                        array(
                            'calloutSessionId' => $calloutSession->getUid()
                        )
                    )
                )
            );
        }
        return null;
    }

    /**
     * This Function searches all CalloutSessions in the Specific State
     * The State is defined in the CalloutSession Class in Static Variables
     * @param $state
     * @return CalloutSession[]|array|object[]
     */
    public function findCalloutSessionByState($state)
    {
        $calloutSession = $this->entityManager->getRepository(CalloutSession::class)->findBy(array('state' => $state));
        return $calloutSession;
    }
}