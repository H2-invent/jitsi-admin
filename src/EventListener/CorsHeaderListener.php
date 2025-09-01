<?php
namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: KernelEvents::RESPONSE)]
class CorsHeaderListener
{
    public function __invoke(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        // Nur auf bestimmte Pfade beschränken, z. B. /theme/
        $path = $request->getPathInfo();

        // Beispiel: Alle .jpg-Dateien im /theme/-Ordner
        if (preg_match('#^/uploads/.*\.*$#', $path)) {
            $response->headers->set('Access-Control-Allow-Origin', '*');
        }
    }
}
