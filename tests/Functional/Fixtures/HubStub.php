<?php

// tests/Functional/Fixtures/HubStub.php
namespace App\Tests\Functional\Fixtures;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Mercure\Jwt\TokenProviderInterface;
use Symfony\Component\Mercure\Update;

class HubStub implements HubInterface
{
    public function publish(Update $update): string
    {
        return 'id';
    }

    // implement rest of HubInterface methods here
    public function getUrl(): string
    {
        // TODO: Implement getUrl() method.
    }

    public function getPublicUrl(): string
    {
        return 'test';
        // TODO: Implement getPublicUrl() method.
    }

    public function getProvider(): TokenProviderInterface
    {
        // TODO: Implement getProvider() method.
    }

    public function getFactory(): ?TokenFactoryInterface
    {
        // TODO: Implement getFactory() method.
    }
}
