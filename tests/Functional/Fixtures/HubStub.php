<?php

// tests/Functional/Fixtures/HubStub.php
namespace App\Tests\Functional\Fixtures;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Jwt\LcobucciFactory;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
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
        // TODO: Implement proper getUrl() method.
        return 'https://123.com';
    }

    public function getPublicUrl(): string
    {
        // TODO: Implement proper getPublicUrl() method.
        return 'test';
    }

    public function getProvider(): TokenProviderInterface
    {
        // TODO: Implement proper getProvider() method.
        return new StaticTokenProvider('test');
    }

    public function getFactory(): ?TokenFactoryInterface
    {
        // TODO: Implement proper getFactory() method.
        return new LcobucciFactory('test');
    }
}
