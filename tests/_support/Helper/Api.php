<?php

declare(strict_types=1);

namespace App\Tests\Helper;

use App\Domain\Player\Entity\Player;
use App\Tests\ApiTester;
use Codeception\Module;

class Api extends Module
{
    public const ADMIN = 'Admin';

    private array $players = [];

    public function createPlayerAndUpdateHeaders(ApiTester $I, string $name): void
    {
        $response = $I->sendPostAsJson('/player', ['name' => $name]);

        if (!is_array($response) || !isset($response['token'])) {
            return;
        }

        $this->players[$name] = $response['token'];
        $this->setJwtHeader($I, $name);
    }

    public function createAdminAndUpdateHeaders(ApiTester $I): void
    {
        $response = $I->sendPostAsJson('/player', ['name' => self::ADMIN]);

        if (!is_array($response) || !isset($response['token'])) {
            return;
        }

        $this->players[self::ADMIN] = $response['token'];
        $this->setJwtHeader($I, self::ADMIN);
    }

    public function setAdminJwtHeader(ApiTester $I): void
    {
        $this->setJwtHeader($I, self::ADMIN);
    }

    public function setJwtHeader(ApiTester $I, string $name): void
    {
        $I->haveHttpHeader('Authorization', sprintf('Bearer %s', $this->players[$name]));
    }
}
