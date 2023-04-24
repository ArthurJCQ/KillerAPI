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

        /** @var string $roomId */
        $roomId = $I->grabFromRepository(Room::class, 'id', ['name' => sprintf('%s\'s room', self::PLAYER_NAME)]);

        $I->sendGet(sprintf('/room/%s', $roomId));

        $I->seeResponseCodeIsSuccessful();
    }

    public function testGetRoomWithCode(ApiTester $I): void
    {
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);
        $I->sendPost('room');

        /** @var string $roomCode */
        $roomCode = $I->grabFromRepository(Room::class, 'id', ['name' => sprintf('%s\'s room', self::PLAYER_NAME)]);

        $I->sendGet(sprintf('/room/%s', $roomCode));

        $I->seeResponseCodeIsSuccessful();
    }

    public function testPatchRoom(ApiTester $I): void
    {
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);
        $I->sendPost('room');

        /** @var string $roomId */
        $roomId = $I->grabFromRepository(Room::class, 'id', ['name' => sprintf('%s\'s room', self::PLAYER_NAME)]);
        $I->sendPatch(sprintf('/room/%s', $roomId), (string) json_encode(['name' => 'new name']));
        $I->seeInRepository(Room::class, ['name' => 'new name']);

        $I->seeResponseCodeIsSuccessful();
    }

    public function testStartGameWithNotEnoughPlayers(ApiTester $I): void
    {
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);
        $I->sendPost('room');

        /** @var string $roomId */
        $roomId = $I->grabFromRepository(Room::class, 'id', ['name' => sprintf('%s\'s room', self::PLAYER_NAME)]);

        $I->sendPatch(sprintf('/room/%s', $roomId), (string) json_encode(['status' => 'IN_GAME']));
        $I->seeResponseCodeIs(400);
    }

    public function testExceptionIfNonAdminUpdateRoom(ApiTester $I): void
    {
        $I->createAdminAndUpdateHeaders($I, self::PLAYER_NAME);
        $I->sendPost('room');

        /** @var string $roomId */
        $roomId = $I->grabFromRepository(Room::class, 'id', ['name' => sprintf('%s\'s room', 'Admin')]);

        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);

        $I->sendPatch(sprintf('/room/%s', $roomId), (string) json_encode(['status' => 'IN_GAME']));
        $I->seeResponseCodeIs(403);
    }

    public function testRoomDeletion(ApiTester $I): void
    {
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);
        $I->sendPost('room');

        /** @var string $roomId */
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
        $I->sendPost('/mission', (string) json_encode(['content' => 'mission']));

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);

        /** @var string $player1Id */
        $player1Id = $I->grabFromRepository(Player::class, 'id', ['name' => self::PLAYER_NAME]);
        $I->sendPatch(sprintf('player/%s', $player1Id), ['room' => $room->getId()]);

        $I->createPlayerAndUpdateHeaders($I, 'Doe');

        /** @var string $player2Id */
        $player2Id = $I->grabFromRepository(Player::class, 'id', ['name' => 'Doe']);
        $I->sendPatch(sprintf('player/%s', $player2Id), ['room' => $room->getId()]);

        $I->setAdminJwtHeader($I);

        $I->sendPatch(sprintf('/room/%s', $room->getId()), ['status' => 'IN_GAME']);

        $I->seeResponseCodeIs(400);
    }

    public function testStartGameSuccessfully(ApiTester $I): void
    {
        // Create admin and room
        $I->createAdminAndUpdateHeaders($I);

        $I->sendPost('room');
        $I->sendPost('/mission', (string) json_encode(['content' => 'mission']));

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        // Join room with player1
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);

        /** @var string $player1Id */
        $player1Id = $I->grabFromRepository(Player::class, 'id', ['name' => self::PLAYER_NAME]);
        $I->sendPatch(sprintf('player/%s', $player1Id), (string) json_encode(['room' => $room->getId()]));
        $I->sendPost('/mission', (string) json_encode(['content' => 'mission']));

        // Join room with player 2
        $I->createPlayerAndUpdateHeaders($I, 'Doe');

        /** @var string $player2Id */
        $player2Id = $I->grabFromRepository(Player::class, 'id', ['name' => 'Doe']);
        $I->sendPatch(sprintf('player/%s', $player2Id), (string) json_encode(['room' => $room->getId()]));
        $I->sendPost('/mission', (string) json_encode(['content' => 'mission']));

        // Start the game with admin
        $I->setAdminJwtHeader($I);

        $I->sendPatch(sprintf('/room/%s', $room->getId()), (string) json_encode(['status' => 'IN_GAME']));

        $I->seeResponseCodeIs(200);

        // I can still get my player
        $I->sendGet('/player/me');

        $I->canSeeResponseContainsJson(
            [
                'name' => 'Admin',
                'room' => ['id' => $room->getId()],
                'status' => PlayerStatus::ALIVE->value,
            ],
        );
    }

    public function testStartGameAndJoinAnotherOne(ApiTester $I): void
    {
        // Create admin and room
        $I->createAdminAndUpdateHeaders($I);

        $I->sendPost('room');
        $I->sendPost('/mission', (string) json_encode(['content' => 'mission']));

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        // Join room with player1
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);

        /** @var string $player1Id */
        $player1Id = $I->grabFromRepository(Player::class, 'id', ['name' => self::PLAYER_NAME]);
        $I->sendPatch(sprintf('player/%s', $player1Id), (string) json_encode(['room' => $room->getId()]));
        $I->sendPost('/mission', (string) json_encode(['content' => 'mission']));

        // Join room with player 2
        $I->createPlayerAndUpdateHeaders($I, 'Doe');

        /** @var string $player2Id */
        $player2Id = $I->grabFromRepository(Player::class, 'id', ['name' => 'Doe']);
        $I->sendPatch(sprintf('player/%s', $player2Id), (string) json_encode(['room' => $room->getId()]));
        $I->sendPost('/mission', (string) json_encode(['content' => 'mission']));

        // Start the game with admin
        $I->setAdminJwtHeader($I);

        $I->sendPatch(sprintf('/room/%s', $room->getId()), (string) json_encode(['status' => 'IN_GAME']));

        $I->seeResponseCodeIs(200);

        // I create a new room with a player
        $I->setJwtHeader($I, self::PLAYER_NAME);
        $I->sendPost('/room');

        $I->seeResponseCodeIsSuccessful();

        /** @var int $newRoomCode */
        $newRoomCode = $I->grabFromRepository(
            Room::class,
            'id',
            ['name' => sprintf('%s\'s room', self::PLAYER_NAME)],
        );
        $I->seeResponseCodeIsSuccessful();

        // I join the new room with the admin of the previous room
        $I->setAdminJwtHeader($I);

        /** @var string $adminId */
        $adminId = $I->grabFromRepository(Player::class, 'id', ['name' => 'Admin']);

        $I->sendPatch(sprintf('/player/%s', $adminId), (string) json_encode(['room' => $newRoomCode]));
        $I->seeResponseContainsJson([
            'room' => ['id' => $newRoomCode],
        ]);
    }

    public function testKillAPlayer(ApiTester $I): void
    {
        // Create admin and room
        $I->createAdminAndUpdateHeaders($I);

        $I->sendPost('room');
        $I->sendPost('/mission', (string) json_encode(['content' => 'mission']));

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        // Join room with player1
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);

        /** @var string $player1Id */
        $player1Id = $I->grabFromRepository(Player::class, 'id', ['name' => self::PLAYER_NAME]);
        $I->sendPatch(sprintf('player/%s', $player1Id), (string) json_encode(['room' => $room->getId()]));
        $I->sendPost('/mission', (string) json_encode(['content' => 'mission']));

        // Join room with player 2
        $I->createPlayerAndUpdateHeaders($I, 'Doe');

        /** @var string $player2Id */
        $player2Id = $I->grabFromRepository(Player::class, 'id', ['name' => 'Doe']);
        $I->sendPatch(sprintf('player/%s', $player2Id), (string) json_encode(['room' => $room->getId()]));
        $I->sendPost('/mission', (string) json_encode(['content' => 'mission']));

        // Start the game with admin
        $I->setAdminJwtHeader($I);

        $I->sendPatch(sprintf('/room/%s', $room->getId()), (string) json_encode(['status' => 'IN_GAME']));

        $I->seeResponseCodeIs(200);

        $I->setJwtHeader($I, self::PLAYER_NAME);

        $I->sendPatch(sprintf('/player/%s', $player1Id), (string) json_encode(['status' => PlayerStatus::KILLED]));

        $I->sendGet('/player/me');
        $I->seeResponseContainsJson([
            'status' => PlayerStatus::KILLED->value,
            'assignedMission' => null,
            'target' => null,
        ]);
    }

    public function testKillAllPlayersButOne(ApiTester $I): void
    {
        // Create admin and room
        $I->createAdminAndUpdateHeaders($I);

        $I->sendPost('room');
        $I->sendPost('/mission', (string) json_encode(['content' => 'mission']));

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        // Join room with player1
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);

        /** @var string $player1Id */
        $player1Id = $I->grabFromRepository(Player::class, 'id', ['name' => self::PLAYER_NAME]);
        $I->sendPatch(sprintf('player/%s', $player1Id), (string) json_encode(['room' => $room->getId()]));
        $I->sendPost('/mission', (string) json_encode(['content' => 'mission']));
        $I->sendPost('/mission', (string) json_encode(['content' => 'mission']));


        // Join room with player 2
        $I->createPlayerAndUpdateHeaders($I, 'Doe');

        /** @var string $player2Id */
        $player2Id = $I->grabFromRepository(Player::class, 'id', ['name' => 'Doe']);
        $I->sendPatch(sprintf('player/%s', $player2Id), (string) json_encode(['room' => $room->getId()]));

        $I->sendGet(sprintf('/room/%s', $room->getId()));
        $I->seeResponseContainsJson([
            'players' => [
                [
                    'name' => 'Admin',
                    'hasAtLeastOneMission' => true,
                ],
                [
                    'name' => 'Doe',
                    'hasAtLeastOneMission' => false,
                ],
            ],
        ]);

        $I->sendPost('/mission', (string) json_encode(['content' => 'mission']));

        // Start the game with admin
        $I->setAdminJwtHeader($I);

        $I->sendPatch(sprintf('/room/%s', $room->getId()), (string) json_encode(['status' => 'IN_GAME']));

        $I->sendGet('/player/me');
        $I->seeResponseContainsJson([
            'status' => PlayerStatus::ALIVE->value,
            'assignedMission' => ['content' => 'mission'],
        ]);

        $I->seeResponseCodeIs(200);

        $I->setJwtHeader($I, self::PLAYER_NAME);

        $I->sendGet('/player/me');
        $I->seeResponseContainsJson([
            'status' => PlayerStatus::ALIVE->value,
            'assignedMission' => ['content' => 'mission'],
        ]);

        $I->sendPatch(sprintf('/player/%s', $player1Id), (string) json_encode(['status' => PlayerStatus::KILLED]));

        $I->sendGet('/player/me');
        $I->seeResponseContainsJson([
            'status' => PlayerStatus::KILLED->value,
            'assignedMission' => null,
            'target' => null,
        ]);

        $I->setJwtHeader($I, 'Doe');

        $I->sendGet('/player/me');
        $I->seeResponseContainsJson([
            'name' => 'Doe',
            'status' => PlayerStatus::ALIVE->value,
            'assignedMission' => ['content' => 'mission'],
        ]);

        $I->sendPatch(sprintf('/player/%s', $player2Id), (string) json_encode(['status' => PlayerStatus::KILLED]));

        $I->sendGet('/player/me');
        $I->seeResponseContainsJson([
            'room' => [
                'status' => Room::ENDED,
            ],
            'status' => PlayerStatus::KILLED->value,
            'assignedMission' => null,
            'target' => null,
        ]);

        $I->setAdminJwtHeader($I);
        $I->sendGet(sprintf('/room/%s', $room->getId()));

        $I->seeResponseContainsJson([
            'status' => Room::ENDED,
            'winner' => [
                'name' => 'Admin',
            ],
        ]);

        $I->sendGet('/player/me');
        $I->seeResponseContainsJson([
            'room' => [
                'status' => Room::ENDED,
            ],
            'status' => PlayerStatus::ALIVE->value,
            'assignedMission' => null,
            'target' => null,
        ]);
    }

    public function testWinnerBug(ApiTester $I): void
    {
        // Create admin and room
        $I->createAdminAndUpdateHeaders($I);

        $I->sendPost('room');
        $I->sendPost('/mission', (string) json_encode(['content' => 'mission']));

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        // Join room with player1
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);

        /** @var string $player1Id */
        $player1Id = $I->grabFromRepository(Player::class, 'id', ['name' => self::PLAYER_NAME]);
        $I->sendPatch(sprintf('player/%s', $player1Id), (string) json_encode(['room' => $room->getId()]));
        $I->sendPost('/mission', (string) json_encode(['content' => 'mission']));
        $I->sendPost('/mission', (string) json_encode(['content' => 'mission']));


        // Join room with player 2
        $I->createPlayerAndUpdateHeaders($I, 'Doe');

        /** @var string $player2Id */
        $player2Id = $I->grabFromRepository(Player::class, 'id', ['name' => 'Doe']);
        $I->sendPatch(sprintf('player/%s', $player2Id), (string) json_encode(['room' => $room->getId()]));

        $I->sendGet(sprintf('/room/%s', $room->getId()));
        $I->seeResponseContainsJson([
            'players' => [
                [
                    'name' => 'Admin',
                    'hasAtLeastOneMission' => true,
                ],
                [
                    'name' => 'Doe',
                    'hasAtLeastOneMission' => false,
                ],
            ],
        ]);

        $I->sendPost('/mission', (string) json_encode(['content' => 'mission']));

        // Start the game with admin
        $I->setAdminJwtHeader($I);

        $I->sendPatch(sprintf('/room/%s', $room->getId()), (string) json_encode(['status' => 'IN_GAME']));

        $I->sendGet('/player/me');
        $I->seeResponseContainsJson([
            'status' => PlayerStatus::ALIVE->value,
            'assignedMission' => ['content' => 'mission'],
        ]);

        $I->seeResponseCodeIs(200);

        $I->setJwtHeader($I, self::PLAYER_NAME);

        $I->sendGet('/player/me');
        $I->seeResponseContainsJson([
            'status' => PlayerStatus::ALIVE->value,
            'assignedMission' => ['content' => 'mission'],
        ]);

        $I->sendPatch(sprintf('/player/%s', $player1Id), (string) json_encode(['status' => PlayerStatus::KILLED]));

        $I->sendGet('/player/me');
        $I->seeResponseContainsJson([
            'status' => PlayerStatus::KILLED->value,
            'assignedMission' => null,
            'target' => null,
        ]);

        $I->setJwtHeader($I, 'Doe');

        $I->sendGet('/player/me');
        $I->seeResponseContainsJson([
            'name' => 'Doe',
            'status' => PlayerStatus::ALIVE->value,
            'assignedMission' => ['content' => 'mission'],
        ]);

        $I->sendPatch(sprintf('/player/%s', $player2Id), (string) json_encode(['status' => PlayerStatus::KILLED]));

        $I->sendGet('/player/me');
        $I->seeResponseContainsJson([
            'room' => [
                'status' => Room::ENDED,
            ],
            'status' => PlayerStatus::KILLED->value,
            'assignedMission' => null,
            'target' => null,
        ]);

        $I->setAdminJwtHeader($I);
        $I->sendGet(sprintf('/room/%s', $room->getId()));

        $I->seeResponseContainsJson([
            'status' => Room::ENDED,
            'winner' => [
                'name' => 'Admin',
            ],
        ]);

        $I->sendGet('/player/me');
        $I->seeResponseContainsJson([
            'room' => [
                'status' => Room::ENDED,
            ],
            'status' => PlayerStatus::ALIVE->value,
            'assignedMission' => null,
            'target' => null,
        ]);

        /** @var string $adminId */
        $adminId = $I->grabFromRepository(Player::class, 'id', ['name' => 'Admin']);

        $I->setJwtHeader($I, 'Doe');
        $I->sendPatch(sprintf('/player/%d', $player2Id), (string) json_encode(['room' => null]));
        $I->seeResponseCodeIsSuccessful();
        $I->sendPost('/room');
        $I->seeResponseCodeIsSuccessful();
        $I->sendPost('/mission', (string) json_encode(['content' => 'mission']));
        $I->seeResponseCodeIsSuccessful();

        $room2 = $I->grabEntityFromRepository(Room::class, ['name' => 'Doe\'s room']);

        // Join room with player1
        $I->setJwtHeader($I, self::PLAYER_NAME);

        $I->sendPatch(sprintf('player/%s', $player1Id), (string) json_encode(['room' => $room2->getId()]));
        $I->sendPost('/mission', (string) json_encode(['content' => 'mission']));
        $I->sendPost('/mission', (string) json_encode(['content' => 'mission']));


        // Join room with player 2
        $I->setAdminJwtHeader($I);

        $I->sendPatch(sprintf('player/%s', $adminId), (string) json_encode(['room' => $room2->getId()]));
        $I->sendPost('/mission', (string) json_encode(['content' => 'mission']));

        // Start the game with admin
        $I->setJwtHeader($I, 'Doe');

        $I->sendPatch(sprintf('/room/%s', $room2->getId()), (string) json_encode(['status' => 'IN_GAME']));

        $I->sendGet('/player/me');
        $I->seeResponseContainsJson([
            'status' => PlayerStatus::ALIVE->value,
            'assignedMission' => ['content' => 'mission'],
        ]);

        $I->seeResponseCodeIs(200);

        $I->setJwtHeader($I, self::PLAYER_NAME);

        $I->sendGet('/player/me');
        $I->seeResponseContainsJson([
            'status' => PlayerStatus::ALIVE->value,
            'assignedMission' => ['content' => 'mission'],
        ]);

        $I->sendPatch(sprintf('/player/%s', $player1Id), (string) json_encode(['status' => PlayerStatus::KILLED]));

        $I->sendGet('/player/me');
        $I->seeResponseContainsJson([
            'status' => PlayerStatus::KILLED->value,
            'assignedMission' => null,
            'target' => null,
        ]);

        $I->setJwtHeader($I, 'Doe');

        $I->sendGet('/player/me');
        $I->seeResponseContainsJson([
            'name' => 'Doe',
            'status' => PlayerStatus::ALIVE->value,
            'assignedMission' => ['content' => 'mission'],
        ]);

        $I->sendPatch(sprintf('/player/%s', $player2Id), (string) json_encode(['status' => PlayerStatus::KILLED]));
        $I->seeResponseCodeIsSuccessful();

        $I->sendGet('/player/me');
        $I->seeResponseContainsJson([
            'room' => [
                'status' => Room::ENDED,
            ],
            'status' => PlayerStatus::KILLED->value,
            'assignedMission' => null,
            'target' => null,
        ]);

        $I->setAdminJwtHeader($I);
        $I->sendGet(sprintf('/room/%s', $room2->getId()));

        $I->seeResponseContainsJson([
            'status' => Room::ENDED,
            'winner' => [
                'name' => 'Admin',
            ],
        ]);

        $I->sendGet('/player/me');
        $I->seeResponseContainsJson([
            'room' => [
                'status' => Room::ENDED,
            ],
            'status' => PlayerStatus::ALIVE->value,
            'assignedMission' => null,
            'target' => null,
        ]);
    }

    public function testAdminLeavesGame(ApiTester $I): void
    {
        // Create admin and room
        $I->createAdminAndUpdateHeaders($I);

        $I->sendPost('room');
        $I->sendPost('/mission', (string) json_encode(['content' => 'mission']));

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        // Join room with player1
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);

        /** @var string $player1Id */
        $player1Id = $I->grabFromRepository(Player::class, 'id', ['name' => self::PLAYER_NAME]);
        $I->sendPatch(sprintf('player/%s', $player1Id), (string) json_encode(['room' => $room->getId()]));

        $I->sendPost('/mission', (string) json_encode(['content' => 'mission']));

        $I->setAdminJwtHeader($I);
        /** @var string $adminId */
        $adminId = $I->grabFromRepository(Player::class, 'id', ['name' => 'Admin']);
        $I->sendPatch(sprintf('player/%s', $adminId), (string) json_encode(['room' => null]));

        $I->setJwtHeader($I, self::PLAYER_NAME);
        $I->sendGet(sprintf('/room/%s', $room->getId()));
        $I->seeResponseContainsJson([
            'id' => $room->getId(),
            'admin' => [
                'name' => 'John',
            ],
        ]);
    }
}
