<?php

namespace App\Helper;

use App\Entity\Rooms;
use App\Service\ThemeService;
use App\Service\Whiteboard\WhiteboardJwtService;

class ExternalApplication
{
    public function __construct(
        private WhiteboardJwtService $whiteboardJwtService,
        private ThemeService         $themeService,
        private UidHelper            $uidHelper,
    )
    {
    }

    public function etherpadLink(Rooms $rooms, $name = null)
    {
        if (!$name) {
            $name = '%name%';
        } else {
            $name = urlencode($name);
        }
        $ui = $this->uidHelper->getUid($rooms);
        return $this->themeService->getApplicationProperties('ETHERPAD_URL') . '/p/' . $ui . '?showChat=false&userName=' . $name;
    }

    public function whitebophirLink(Rooms $rooms, $moderator = false)
    {
        $ui = $this->uidHelper->getUid($rooms);
        return $this->themeService->getApplicationProperties('WHITEBOARD_URL') . '/boards/' . $ui . '?token=' . $this->whiteboardJwtService->createJwt($rooms, $moderator);
    }
}
