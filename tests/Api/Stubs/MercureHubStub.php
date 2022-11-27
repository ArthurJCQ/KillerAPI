<?php

declare(strict_types=1);

namespace App\Tests\Api\Stubs;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Mercure\Jwt\TokenProviderInterface;
use Symfony\Component\Mercure\Update;

class MercureHubStub implements HubInterface
{
    public function publish(Update $update): string
    {
        return 'id';
    }

    public function getUrl(): string
    {
        return 'url';
    }

    public function getPublicUrl(): string
    {
        return 'url';
    }

    public function getProvider(): TokenProviderInterface
    {
        return new StaticTokenProvider('token');
    }

    public function getFactory(): ?TokenFactoryInterface
    {
        return null;
    }
}
