<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Room\Entity\Room;
use App\Domain\User\Entity\User;
use App\Tests\ApiTester;

class PlayerControllerCest
{
    public const string PLAYER_NAME = 'John';

    public function _before(ApiTester $I): void
    {
        $I->createAdminAndUpdateHeaders($I);
        $I->sendPostAsJson('room');
        $I->seeInRepository(Room::class, ['name' => 'Admin\'s room']);

        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);

        $I->setAdminJwtHeader($I);
    }

    public function testCreatePlayer(ApiTester $I): void
    {
        $I->seeResponseContainsJson(
            [
                'name' => self::PLAYER_NAME,
            ],
        );

        $I->setJwtHeader($I, self::PLAYER_NAME);
        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson(
            [
                'name' => self::PLAYER_NAME,
            ],
        );
    }

    public function testPatchPlayer(ApiTester $I): void
    {
        $I->setJwtHeader($I, self::PLAYER_NAME);
        $I->sendPatchAsJson('/user', ['name' => 'Hey']);
        $I->seeInRepository(User::class, ['name' => 'Hey']);
        $I->dontSeeInRepository(User::class, ['name' => self::PLAYER_NAME]);

        $I->seeResponseContainsJson(
            [
                'name' => 'Hey',
            ],
        );
    }

    public function testPlayerJoinRoom(ApiTester $I): void
    {
        $code = $I->grabFromRepository(Room::class, 'id', ['name' => 'Admin\'s room']);

        $I->setJwtHeader($I, self::PLAYER_NAME);
        $I->sendPatchAsJson('/user', ['room' => $code]);

        $I->seeInRepository(Player::class, ['name' => self::PLAYER_NAME, 'room' => ['id' => $code]]);

        $I->seeResponseCodeIsSuccessful();
    }

    public function testPlayerLeaveRoom(ApiTester $I): void
    {
        /** @var string $roomCode */
        $roomCode = $I->grabFromRepository(
            Room::class,
            'id',
            ['name' => sprintf('%s\'s room', 'Admin')],
        );

        $I->setJwtHeader($I, self::PLAYER_NAME);
        $I->sendPatchAsJson('/user', ['room' => $roomCode]);
        $I->seeResponseCodeIsSuccessful();
        $I->seeInRepository(
            Player::class,
            ['name' => self::PLAYER_NAME, 'status' => PlayerStatus::ALIVE->value, 'room' => ['id' => $roomCode]],
        );

        $I->seeResponseCodeIsSuccessful();

        $I->sendPostAsJson('/mission', ['content' => 'coucou']);

        $I->sendPatchAsJson('/user', ['room' => null]);
        $I->seeInRepository(Player::class, [
            'name' => self::PLAYER_NAME,
            'status' => PlayerStatus::KILLED->value,
            'room' => $roomCode,
        ]);
        $I->seeResponseCodeIsSuccessful();

        $I->setAdminJwtHeader($I);
        $I->sendGetAsJson(sprintf('/room/%s', $roomCode));
        $I->seeResponseContainsJson([
            'missions' => [],
        ]);
    }

    public function testDeletePlayer(ApiTester $I): void
    {
        $roomCode = $I->grabFromRepository(
            Room::class,
            'id',
            ['name' => sprintf('%s\'s room', 'Admin')],
        );

        $I->setJwtHeader($I, self::PLAYER_NAME);
        $I->sendPatchAsJson('/user', ['room' => $roomCode]);

        $I->seeInRepository(
            Player::class,
            ['name' => self::PLAYER_NAME, 'status' => PlayerStatus::ALIVE->value, 'room' => ['id' => $roomCode]],
        );

        $I->seeResponseCodeIsSuccessful();

        /** @var string $playerId */
        $playerId = $I->grabFromRepository(Player::class, 'id', ['name' => self::PLAYER_NAME, 'room' => ['id' => $roomCode]]);
        $I->sendDeleteAsJson(sprintf('/player/%s', $playerId));
        $I->dontSeeInRepository(Player::class, ['name' => self::PLAYER_NAME]);

        $I->seeResponseCodeIsSuccessful();
    }
}
