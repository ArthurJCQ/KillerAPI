<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Room\Entity\Room;
use App\Tests\ApiTester;

class RoomControllerCest
{
    public function testCreateRoom(ApiTester $I): void
    {
        $I->sendPost('player', (string) json_encode(['name' => 'John']));

        $I->sendPost('room');

        $playerId = $I->grabFromRepository(Player::class, 'id', ['name' => 'John']);

        $I->seeResponseContainsJson(['admin' => ['id' => $playerId]]);
    }

    public function testPatchRoom(ApiTester $I): void
    {
        $I->sendPost('player', (string) json_encode(['name' => 'John']));
        $I->sendPost('room');

        /** @var int $roomId */
        $roomId = $I->grabFromRepository(Room::class, 'id', ['name' => 'John\'s room']);
        $I->sendPatch(sprintf('/room/%d', $roomId), (string) json_encode(['name' => 'new name']));
        $I->seeInRepository(Room::class, ['name' => 'new name']);

        $I->seeResponseCodeIsSuccessful();
    }

    public function testStartGameWithNotEnoughPlayers(ApiTester $I): void
    {
        $I->sendPost('player', (string) json_encode(['name' => 'John']));
        $I->sendPost('room');

        /** @var int $roomId */
        $roomId = $I->grabFromRepository(Room::class, 'id', ['name' => 'John\'s room']);

        $I->sendPatch(sprintf('/room/%d', $roomId), (string) json_encode(['status' => 'IN_GAME']));
        $I->seeResponseCodeIs(400);
    }

    public function testExceptionIfNonAdminUpdateRoom(ApiTester $I): void
    {
        $I->sendPost('player', (string) json_encode(['name' => 'John']));
        $I->sendPost('room');

        /** @var int $roomId */
        $roomId = $I->grabFromRepository(Room::class, 'id', ['name' => 'John\'s room']);

        $I->sendPost('player', (string) json_encode(['name' => 'Test']));

        $I->sendPatch(sprintf('/room/%d', $roomId), (string) json_encode(['status' => 'IN_GAME']));
        $I->seeResponseCodeIs(403);
    }

    public function testRoomDeletion(ApiTester $I): void
    {
        $I->sendPost('player', (string) json_encode(['name' => 'John']));
        $I->sendPost('room');

        /** @var int $roomId */
        $roomId = $I->grabFromRepository(Room::class, 'id', ['name' => 'John\'s room']);

        $I->sendDelete(sprintf('/room/%s', $roomId));

        $I->seeResponseCodeIsSuccessful();

        $I->seeInRepository(Player::class, ['name' => 'John', 'room' => null, 'status' => PlayerStatus::ALIVE]);
    }

    public function testStartGameWithNoMission(ApiTester $I): void
    {
        $I->sendPost('/player', (string) json_encode(['name' => 'Admin']));
        $I->sendPost('room');

        $admin = $I->grabEntityFromRepository(Player::class, ['name' => 'Admin']);
        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        $I->sendPost('/player', (string) json_encode(['name' => 'John']));

        /** @var string $player1Id */
        $player1Id = $I->grabFromRepository(Player::class, 'id', ['name' => 'John']);
        $I->sendPatch(sprintf('player/%s', $player1Id), ['room' => $room->getCode()]);


        $I->sendPost('/player', (string) json_encode(['name' => 'Doe']));

        /** @var string $player2Id */
        $player2Id = $I->grabFromRepository(Player::class, 'id', ['name' => 'Doe']);
        $I->sendPatch(sprintf('player/%s', $player2Id), ['room' => $room->getCode()]);

        $I->amLoggedInAs($admin);

        $I->sendPatch(sprintf('/room/%s', $room->getId()), ['status' => 'IN_GAME']);

        $I->seeResponseCodeIs(400);
    }

    public function testStartGameSuccessfully(ApiTester $I): void
    {
        $I->sendPost('/player', (string) json_encode(['name' => 'Admin']));
        $I->sendPost('room');
        $I->sendPost('/mission', (string) json_encode(['content' => 'mission']));

        $admin = $I->grabEntityFromRepository(Player::class, ['name' => 'Admin']);
        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        $I->sendPost('/player', (string) json_encode(['name' => 'John']));

        /** @var string $player1Id */
        $player1Id = $I->grabFromRepository(Player::class, 'id', ['name' => 'John']);
        $I->sendPatch(sprintf('player/%s', $player1Id), (string) json_encode(['room' => $room->getCode()]));
        $I->sendPost('/mission', (string) json_encode(['content' => 'mission']));


        $I->sendPost('/player', (string) (string) json_encode(['name' => 'Doe']));

        /** @var string $player2Id */
        $player2Id = $I->grabFromRepository(Player::class, 'id', ['name' => 'Doe']);
        $I->sendPatch(sprintf('player/%s', $player2Id), (string) json_encode(['room' => $room->getCode()]));
        $I->sendPost('/mission', (string) json_encode(['content' => 'mission']));

        $I->amLoggedInAs($admin);

        $I->sendPatch(sprintf('/room/%s', $room->getId()), (string) json_encode(['status' => 'IN_GAME']));

        $I->seeResponseCodeIs(200);
    }
}
