<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Room\Entity\Room;
use App\Tests\ApiTester;

class PlayerControllerCest
{
    public const PLAYER_NAME = 'John';

    public function _before(ApiTester $I): void
    {
        $I->createAdminAndUpdateHeaders($I);
        $I->sendPost('room');
        $I->seeInRepository(Room::class, ['name' => 'Admin\'s room']);

        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);

        $I->setAdminJwtHeader($I);
    }

    public function testCreatePlayer(ApiTester $I): void
    {
        $I->seeInRepository(Player::class, ['name' => self::PLAYER_NAME]);

        $I->canSeeResponseContainsJson(
            [
                'name' => self::PLAYER_NAME,
                'room' => null,
                'status' => PlayerStatus::ALIVE->value
            ],
        );

        $I->setJwtHeader($I, self::PLAYER_NAME);
        $I->sendGet('player/me');

        $I->canSeeResponseContainsJson(
            [
                'name' => self::PLAYER_NAME,
                'room' => null,
                'status' => PlayerStatus::ALIVE->value
            ],
        );
    }

    public function testPatchPlayer(ApiTester $I): void
    {
        /** @var string $playerId */
        $playerId = $I->grabFromRepository(Player::class, 'id', ['name' => self::PLAYER_NAME]);

        $I->setJwtHeader($I, self::PLAYER_NAME);
        $I->sendPatch(sprintf('/player/%s', $playerId), (string) json_encode(['name' => 'Hey']));
        $I->seeInRepository(Player::class, ['name' => 'Hey']);
        $I->dontSeeInRepository(Player::class, ['name' => self::PLAYER_NAME]);

        $I->canSeeResponseContainsJson(
            [
                'name' => 'Hey',
                'status' => PlayerStatus::ALIVE->value
            ],
        );
    }

    public function testPlayerJoinRoom(ApiTester $I): void
    {
        $code = $I->grabFromRepository(Room::class, 'id', ['name' => 'Admin\'s room']);
        /** @var string $playerId */
        $playerId = $I->grabFromRepository(Player::class, 'id', ['name' => self::PLAYER_NAME]);

        $I->setJwtHeader($I, self::PLAYER_NAME);
        $I->sendPatch(sprintf('/player/%s', $playerId), (string) json_encode(['room' => $code]));

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

        /** @var string $playerId */
        $playerId = $I->grabFromRepository(Player::class, 'id', ['name' => self::PLAYER_NAME]);

        $player = $I->grabEntityFromRepository(Player::class, ['name' => self::PLAYER_NAME]);
        $player->setStatus(PlayerStatus::KILLED);
        $I->flushToDatabase();

        $I->setJwtHeader($I, self::PLAYER_NAME);
        $I->sendPatch(sprintf('/player/%s', $playerId), (string) json_encode(['room' => $roomCode]));
        $I->seeResponseCodeIsSuccessful();
        $I->seeInRepository(
            Player::class,
            ['name' => self::PLAYER_NAME, 'status' => PlayerStatus::KILLED->value, 'room' => ['id' => $roomCode]],
        );

        $I->seeResponseCodeIsSuccessful();

        $I->sendPost('/mission', (string) json_encode(['content' => 'coucou']));

        $I->sendPatch(sprintf('/player/%s', $playerId), (string) json_encode(['room' => null]));
        $I->seeInRepository(Player::class, [
            'name' => self::PLAYER_NAME,
            'status' => PlayerStatus::ALIVE->value,
            'room' => null,
        ]);
        $I->seeResponseCodeIsSuccessful();

        $I->setAdminJwtHeader($I);
        $I->sendGet(sprintf('/room/%s', $roomCode));
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

        /** @var string $playerId */
        $playerId = $I->grabFromRepository(Player::class, 'id', ['name' => self::PLAYER_NAME]);

        $player = $I->grabEntityFromRepository(Player::class, ['name' => self::PLAYER_NAME]);
        $player->setStatus(PlayerStatus::KILLED);
        $I->flushToDatabase();

        $I->setJwtHeader($I, self::PLAYER_NAME);
        $I->sendPatch(sprintf('/player/%s', $playerId), (string) json_encode(['room' => $roomCode]));
        $I->seeInRepository(
            Player::class,
            ['name' => self::PLAYER_NAME, 'status' => PlayerStatus::KILLED->value, 'room' => ['id' => $roomCode]],
        );

        $I->seeResponseCodeIsSuccessful();

        $I->sendDelete(sprintf('/player/%s', $playerId));
        $I->dontSeeInRepository(Player::class, ['name' => self::PLAYER_NAME]);

        $I->seeResponseCodeIsSuccessful();
    }
}
