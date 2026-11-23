<?php

namespace App\Tests\DependencyInjection;

use App\DependencyInjection\Compiler\ToggleLokiHandlerPass;
use Monolog\Handler\NullHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ToggleLokiHandlerPassTest extends TestCase
{
    private const LOKI_SERVICE_IDS = [
        'App\MessageHandler\LokiLogMessageHandler',
        'App\Message\LokiLogMessage',
        'App\Monolog\Handler\AsyncLokiHandler',
        'monolog.handler.loki_filtered',
        'monolog.handler.loki_healthcheck_filter',
        'monolog.handler.loki_async',
        'monolog.handler.loki',
    ];

    private function container(bool $enabled): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('app.log.loki.enabled', $enabled);
        foreach (self::LOKI_SERVICE_IDS as $id) {
            $container->setDefinition($id, new Definition(\stdClass::class));
        }
        $container->setDefinition('app.unrelated.service', new Definition(\stdClass::class));

        return $container;
    }

    public function testReplacesAllLokiServicesWithNullHandlerWhenDisabled(): void
    {
        $container = $this->container(false);

        (new ToggleLokiHandlerPass())->process($container);

        foreach (self::LOKI_SERVICE_IDS as $id) {
            self::assertTrue($container->hasDefinition($id));
            self::assertSame(NullHandler::class, $container->getDefinition($id)->getClass(), $id);
        }
        self::assertSame(\stdClass::class, $container->getDefinition('app.unrelated.service')->getClass());
    }

    public function testKeepsLokiServicesWhenEnabled(): void
    {
        $container = $this->container(true);

        (new ToggleLokiHandlerPass())->process($container);

        foreach (self::LOKI_SERVICE_IDS as $id) {
            self::assertSame(\stdClass::class, $container->getDefinition($id)->getClass(), $id);
        }
        self::assertSame(\stdClass::class, $container->getDefinition('app.unrelated.service')->getClass());
    }

    public function testReplacesAliasWithNullHandlerWhenDisabled(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('app.log.loki.enabled', false);
        $container->setAlias('monolog.handler.loki_filtered', 'some.target.service');

        (new ToggleLokiHandlerPass())->process($container);

        self::assertFalse($container->hasAlias('monolog.handler.loki_filtered'));
        self::assertTrue($container->hasDefinition('monolog.handler.loki_filtered'));
        self::assertSame(NullHandler::class, $container->getDefinition('monolog.handler.loki_filtered')->getClass());
    }

    public function testDoesNotCreateMissingLokiServices(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('app.log.loki.enabled', false);

        (new ToggleLokiHandlerPass())->process($container);

        foreach (self::LOKI_SERVICE_IDS as $id) {
            self::assertFalse($container->hasDefinition($id), $id);
        }
    }
}
