<?php

// tests/Functional/Fixtures/HubStub.php
namespace App\Tests\Functional\Fixtures;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Jwt\FactoryTokenProvider;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Mercure\Jwt\TokenProviderInterface;
use Symfony\Component\Mercure\Update;

class HubStub implements HubInterface
{
    public function __construct(
        private HubInterface $inner,
    ) {
    }

    public function publish(Update $update): string
    {
        return 'id';
    }

    // implement rest of HubInterface methods here
    public function getUrl(): string
    {
        // TODO: Implement proper getUrl() method.
        return 'test';
    }

    public function getPublicUrl(): string
    {
        // TODO: Implement proper getPublicUrl() method.
        return 'test';
    }

    public function getProvider(): TokenProviderInterface
    {
        // TODO: Implement proper getProvider() method.
        return $this->inner->getProvider();
    }

    public function getFactory(): ?TokenFactoryInterface
    {
        // TODO: Implement proper getFactory() method.
        return $this->inner->getFactory();
    }
}
