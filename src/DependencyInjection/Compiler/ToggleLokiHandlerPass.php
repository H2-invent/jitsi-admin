<?php
declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use App\Message\LokiLogMessage;
use App\MessageHandler\LokiLogMessageHandler;
use App\Monolog\Handler\AsyncLokiHandler;
use Monolog\Handler\NullHandler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ToggleLokiHandlerPass implements CompilerPassInterface
{
    private const LOKI_SERVICES = [
        LokiLogMessageHandler::class,
        LokiLogMessage::class,
        AsyncLokiHandler::class,
        'monolog.handler.loki_filtered',
        'monolog.handler.loki_healthcheck_filter',
        'monolog.handler.loki_async',
        'monolog.handler.loki',
    ];

    public function process(ContainerBuilder $container): void
    {
        $enabled = $container->resolveEnvPlaceholders(
            $container->getParameter('app.log.loki.enabled'),
            true,
        );

        if ($enabled) {
            return;
        }

        foreach (self::LOKI_SERVICES as $service) {
            if ($container->hasDefinition($service) || $container->hasAlias($service)) {
                $nullhandler = new Definition(NullHandler::class);
                $nullhandler->setPublic(false);
                $container->setDefinition($service, $nullhandler);
            }
        }
    }
}
