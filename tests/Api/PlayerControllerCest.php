<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Room\Entity\Room;
use App\Tests\ApiTester;

class PlayerControllerCest
{
    public function _before(ApiTester $I): void
    {
        $I->sendPost('player', (string) json_encode(['name' => 'Admin']));
        $I->sendPost('room');
        $I->seeInRepository(Room::class, ['name' => 'Admin\'s room']);

        $I->sendPost('player', (string) json_encode(['name' => 'John']));
        $I->seeAuthentication();
    }

    public function testCreatePlayer(ApiTester $I): void
    {
        $I->seeInRepository(Player::class, ['name' => 'John']);

        $I->canSeeResponseContainsJson(
            [
                'name' => 'John',
                'room' => null,
                'status' => PlayerStatus::ALIVE->value
            ],
        );

        $I->sendGet('player/me');

        $I->canSeeResponseContainsJson(
            [
                'name' => 'John',
                'room' => null,
                'status' => PlayerStatus::ALIVE->value
            ],
        );
    }

    public function testPatchPlayer(ApiTester $I): void
    {
        /** @var string $playerId */
        $playerId = $I->grabFromRepository(Player::class, 'id', ['name' => 'John']);

        $I->sendPatch(sprintf('/player/%s', $playerId), (string) json_encode(['name' => 'Hey']));
        $I->seeInRepository(Player::class, ['name' => 'Hey']);
        $I->dontSeeInRepository(Player::class, ['name' => 'John']);

        $I->canSeeResponseContainsJson(
            [
                'name' => 'Hey',
                'status' => PlayerStatus::ALIVE->value
            ],
        );
    }

    public function testPlayerJoinRoom(ApiTester $I): void
    {
        $code = $I->grabFromRepository(Room::class, 'code', ['name' => 'Admin\'s room']);
        /** @var string $playerId */
        $playerId = $I->grabFromRepository(Player::class, 'id', ['name' => 'John']);

        $I->sendPatch(sprintf('/player/%s', $playerId), (string) json_encode(['room' => $code]));

        $I->seeInRepository(Player::class, ['name' => 'John', 'room' => ['code' => $code]]);

        $I->seeResponseCodeIsSuccessful();
    }

    public function testPlayerLeaveRoom(ApiTester $I): void
    {
        /** @var string $playerId */
        $playerId = $I->grabFromRepository(Player::class, 'id', ['name' => 'John']);

        $I->haveInRepository(Room::class, ['code' => 'XXXXX', 'name' => 'John\'s room']);
        $player = $I->grabEntityFromRepository(Player::class, ['name' => 'John']);
        $player->setStatus(PlayerStatus::KILLED);
        $I->flushToDatabase();

        $I->sendPatch(sprintf('/player/%s', $playerId), (string) json_encode(['room' => 'XXXXX']));
        $I->seeInRepository(
            Player::class,
            ['name' => 'John', 'status' => PlayerStatus::KILLED->value, 'room' => ['code' => 'XXXXX']],
        );

        $I->seeResponseCodeIsSuccessful();

        $I->sendPatch(sprintf('/player/%s', $playerId), (string) json_encode(['room' => null]));
        $I->seeInRepository(Player::class, ['name' => 'John', 'status' => PlayerStatus::ALIVE->value, 'room' => null]);
        $I->dontSeeInRepository(Room::class, ['name' => 'John\'s room']);

        $I->seeResponseCodeIsSuccessful();
    }


    public function testDeletePlayer(ApiTester $I): void
    {
        /** @var string $playerId */
        $playerId = $I->grabFromRepository(Player::class, 'id', ['name' => 'John']);

        $I->haveInRepository(Room::class, ['code' => 'XXXXX', 'name' => 'John\'s room']);
        $player = $I->grabEntityFromRepository(Player::class, ['name' => 'John']);
        $player->setStatus(PlayerStatus::KILLED);
        $I->flushToDatabase();

        $I->sendPatch(sprintf('/player/%s', $playerId), (string) json_encode(['room' => 'XXXXX']));
        $I->seeInRepository(
            Player::class,
            ['name' => 'John', 'status' => PlayerStatus::KILLED->value, 'room' => ['code' => 'XXXXX']],
        );

        $I->seeResponseCodeIsSuccessful();

        $I->sendDelete(sprintf('/player/%s', $playerId));
        $I->dontSeeInRepository(Player::class, ['name' => 'John']);

        $I->seeResponseCodeIsSuccessful();
    }
}
