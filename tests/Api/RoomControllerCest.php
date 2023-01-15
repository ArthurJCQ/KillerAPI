<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Room\Entity\Room;
use App\Tests\ApiTester;

class RoomControllerCest
{
    public const PLAYER_NAME = 'John';

    public function testCreateRoom(ApiTester $I): void
    {
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);
        $I->sendPost('room');

        $playerId = $I->grabFromRepository(Player::class, 'id', ['name' => self::PLAYER_NAME]);

        $I->seeResponseContainsJson(['admin' => ['id' => $playerId]]);
    }

    public function testGetRoomWithId(ApiTester $I): void
    {
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);
        $I->sendPost('room');

        /** @var int $roomId */
        $roomId = $I->grabFromRepository(Room::class, 'id', ['name' => sprintf('%s\'s room', self::PLAYER_NAME)]);

        $I->sendGet(sprintf('/room/%d', $roomId));

        $I->seeResponseCodeIsSuccessful();
    }

    public function testGetRoomWithCode(ApiTester $I): void
    {
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);
        $I->sendPost('room');

        /** @var string $roomCode */
        $roomCode = $I->grabFromRepository(Room::class, 'code', ['name' => sprintf('%s\'s room', self::PLAYER_NAME)]);

        $I->sendGet(sprintf('/room/%s', $roomCode));

        $I->seeResponseCodeIsSuccessful();
    }

    public function testPatchRoom(ApiTester $I): void
    {
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);
        $I->sendPost('room');

        /** @var int $roomId */
        $roomId = $I->grabFromRepository(Room::class, 'id', ['name' => sprintf('%s\'s room', self::PLAYER_NAME)]);
        $I->sendPatch(sprintf('/room/%d', $roomId), (string) json_encode(['name' => 'new name']));
        $I->seeInRepository(Room::class, ['name' => 'new name']);

        $I->seeResponseCodeIsSuccessful();
    }

    public function testStartGameWithNotEnoughPlayers(ApiTester $I): void
    {
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);
        $I->sendPost('room');

        /** @var int $roomId */
        $roomId = $I->grabFromRepository(Room::class, 'id', ['name' => sprintf('%s\'s room', self::PLAYER_NAME)]);

        $I->sendPatch(sprintf('/room/%d', $roomId), (string) json_encode(['status' => 'IN_GAME']));
        $I->seeResponseCodeIs(400);
    }

    public function testExceptionIfNonAdminUpdateRoom(ApiTester $I): void
    {
        $I->createAdminAndUpdateHeaders($I, self::PLAYER_NAME);
        $I->sendPost('room');

        /** @var int $roomId */
        $roomId = $I->grabFromRepository(Room::class, 'id', ['name' => sprintf('%s\'s room', 'Admin')]);

        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);

        $I->sendPatch(sprintf('/room/%d', $roomId), (string) json_encode(['status' => 'IN_GAME']));
        $I->seeResponseCodeIs(403);
    }

    public function testRoomDeletion(ApiTester $I): void
    {
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);
        $I->sendPost('room');

        /** @var int $roomId */
        $roomId = $I->grabFromRepository(Room::class, 'id', ['name' => sprintf('%s\'s room', self::PLAYER_NAME)]);

        $I->sendDelete(sprintf('/room/%s', $roomId));

        $I->seeResponseCodeIsSuccessful();

        $I->seeInRepository(Player::class, [
            'name' => self::PLAYER_NAME,
            'room' => null,
            'status' => PlayerStatus::ALIVE,
        ]);
    }

    public function testStartGameWithNoMission(ApiTester $I): void
    {
        $I->createAdminAndUpdateHeaders($I);
        $I->sendPost('room');

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);

        /** @var string $player1Id */
        $player1Id = $I->grabFromRepository(Player::class, 'id', ['name' => self::PLAYER_NAME]);
        $I->sendPatch(sprintf('player/%s', $player1Id), ['room' => $room->getCode()]);

        $I->createPlayerAndUpdateHeaders($I, 'Doe');

        /** @var string $player2Id */
        $player2Id = $I->grabFromRepository(Player::class, 'id', ['name' => 'Doe']);
        $I->sendPatch(sprintf('player/%s', $player2Id), ['room' => $room->getCode()]);

        $I->setAdminJwtHeader($I);

        $I->sendPatch(sprintf('/room/%s', $room->getId()), ['status' => 'IN_GAME']);

        $I->seeResponseCodeIs(400);
    }

    public function testStartGameSuccessfully(ApiTester $I): void
    {
        $I->createAdminAndUpdateHeaders($I);

        $I->sendPost('room');
        $I->sendPost('/mission', (string) json_encode(['content' => 'mission']));

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);

        /** @var string $player1Id */
        $player1Id = $I->grabFromRepository(Player::class, 'id', ['name' => self::PLAYER_NAME]);
        $I->sendPatch(sprintf('player/%s', $player1Id), (string) json_encode(['room' => $room->getCode()]));
        $I->sendPost('/mission', (string) json_encode(['content' => 'mission']));

        $I->createPlayerAndUpdateHeaders($I, 'Doe');

        /** @var string $player2Id */
        $player2Id = $I->grabFromRepository(Player::class, 'id', ['name' => 'Doe']);
        $I->sendPatch(sprintf('player/%s', $player2Id), (string) json_encode(['room' => $room->getCode()]));
        $I->sendPost('/mission', (string) json_encode(['content' => 'mission']));

        $I->setAdminJwtHeader($I);

        $I->sendPatch(sprintf('/room/%s', $room->getId()), (string) json_encode(['status' => 'IN_GAME']));

        $I->seeResponseCodeIs(200);
    }
}
