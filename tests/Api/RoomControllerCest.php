<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Api\Controller\RoomController;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Room\Entity\Room;
use App\Tests\ApiTester;

use function PHPUnit\Framework\assertEquals;

class RoomControllerCest
{
    public const string PLAYER_NAME = 'John';

    public function testCreateRoom(ApiTester $I): void
    {
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);
        $I->sendPostAsJson('room');

        $playerId = $I->grabFromRepository(Player::class, 'id', ['name' => self::PLAYER_NAME]);

        $I->seeResponseContainsJson(['admin' => ['id' => $playerId]]);
    }

    public function testGetRoomWithId(ApiTester $I): void
    {
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);
        $I->sendPostAsJson('room');

        /** @var string $roomId */
        $roomId = $I->grabFromRepository(Room::class, 'id', ['name' => sprintf('%s\'s room', self::PLAYER_NAME)]);

        $I->sendGetAsJson(sprintf('/room/%s', $roomId));

        $I->seeResponseCodeIsSuccessful();
    }

    public function testGetRoomWithCode(ApiTester $I): void
    {
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);
        $I->sendPostAsJson('room');

        /** @var string $roomCode */
        $roomCode = $I->grabFromRepository(Room::class, 'id', ['name' => sprintf('%s\'s room', self::PLAYER_NAME)]);

        $I->sendGetAsJson(sprintf('/room/%s', $roomCode));

        $I->seeResponseCodeIsSuccessful();
    }

    public function testPatchRoom(ApiTester $I): void
    {
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);
        $I->sendPostAsJson('room');

        /** @var string $roomId */
        $roomId = $I->grabFromRepository(Room::class, 'id', ['name' => sprintf('%s\'s room', self::PLAYER_NAME)]);
        $I->sendPatchAsJson(sprintf('/room/%s', $roomId), ['name' => 'new name']);
        $I->seeInRepository(Room::class, ['name' => 'new name']);

        $I->seeResponseCodeIsSuccessful();
    }

    public function testStartGameWithNotEnoughPlayers(ApiTester $I): void
    {
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);
        $I->sendPostAsJson('room');

        /** @var string $roomId */
        $roomId = $I->grabFromRepository(Room::class, 'id', ['name' => sprintf('%s\'s room', self::PLAYER_NAME)]);

        $I->sendPatchAsJson(sprintf('/room/%s', $roomId), ['status' => 'IN_GAME']);
        $I->seeResponseCodeIs(400);
    }

    public function testExceptionIfNonAdminUpdateRoom(ApiTester $I): void
    {
        $I->createAdminAndUpdateHeaders($I, self::PLAYER_NAME);
        $I->sendPostAsJson('room');

        /** @var string $roomId */
        $roomId = $I->grabFromRepository(Room::class, 'id', ['name' => sprintf('%s\'s room', 'Admin')]);

        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);

        $I->sendPatchAsJson(sprintf('/room/%s', $roomId), ['status' => 'IN_GAME']);
        $I->seeResponseCodeIs(403);
    }

    public function testRoomDeletion(ApiTester $I): void
    {
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);
        $I->sendPostAsJson('room');

        /** @var string $roomId */
        $roomId = $I->grabFromRepository(Room::class, 'id', ['name' => sprintf('%s\'s room', self::PLAYER_NAME)]);

        $I->sendDeleteAsJson(sprintf('/room/%s', $roomId));

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
        $I->sendPostAsJson('room');
        $I->sendPostAsJson('/mission', ['content' => 'mission']);

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);

        $I->createPlayerAndUpdateHeaders($I, 'Doe');
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);

        $I->setAdminJwtHeader($I);

        $I->sendPatchAsJson(sprintf('/room/%s', $room->getId()), ['status' => 'IN_GAME']);

        $I->seeResponseCodeIs(400);
    }

    public function testStartGameSuccessfully(ApiTester $I): void
    {
        // Create admin and room
        $I->createAdminAndUpdateHeaders($I);

        $I->sendPostAsJson('room');
        $I->sendPostAsJson('/mission', ['content' => 'mission']);

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        // Join room with player1
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'mission']);

        // Join room with player 2
        $I->createPlayerAndUpdateHeaders($I, 'Doe');
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'mission']);

        // Start the game with admin
        $I->setAdminJwtHeader($I);

        $I->sendPatchAsJson(sprintf('/room/%s', $room->getId()), ['status' => 'IN_GAME']);

        $I->seeResponseCodeIs(200);

        // I can still get my player
        $I->sendGetAsJson('/user/me');

        $I->seeResponseContainsJson(
            [
                'currentPlayer' => [
                    'name' => 'Admin',
                    'room' => ['id' => $room->getId()],
                    'status' => PlayerStatus::ALIVE->value,
                    'target' => ['avatar' => Player::DEFAULT_AVATAR],
                ],
            ],
        );
    }

    public function testStartGameAndJoinAnotherOne(ApiTester $I): void
    {
        // Create admin and room
        $I->createAdminAndUpdateHeaders($I);

        $I->sendPostAsJson('room');
        $I->sendPostAsJson('/mission', ['content' => 'mission']);

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        // Join room with player1
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'mission']);

        // Join room with player 2
        $I->createPlayerAndUpdateHeaders($I, 'Doe');
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'mission']);

        // Start the game with admin
        $I->setAdminJwtHeader($I);

        $I->sendPatchAsJson(sprintf('/room/%s', $room->getId()), ['status' => 'IN_GAME']);

        $I->seeResponseCodeIs(200);

        // I create a new room with a player
        $I->setJwtHeader($I, self::PLAYER_NAME);
        $I->sendPatchAsJson('/user', ['room' => null]);
        $I->sendPostAsJson('/room');

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
        $I->sendPatchAsJson('/user', ['room' => $newRoomCode]);
    }

    public function testKillAPlayer(ApiTester $I): void
    {
        // Create admin and room
        $I->createAdminAndUpdateHeaders($I);

        $I->sendPostAsJson('/room');
        $I->sendPostAsJson('/mission', ['content' => 'mission']);

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        // Join room with player1
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'mission']);

        // Join room with player 2
        $I->createPlayerAndUpdateHeaders($I, 'Doe');
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'mission']);

        // Start the game with admin
        $I->setAdminJwtHeader($I);

        $I->sendPatchAsJson(sprintf('/room/%s', $room->getId()), ['status' => 'IN_GAME']);

        $I->seeResponseCodeIs(200);

        $I->setJwtHeader($I, self::PLAYER_NAME);

        /** @var string $player1Id */
        $player1Id = $I->grabFromRepository(Player::class, 'id', ['name' => self::PLAYER_NAME, 'room' => $room->getId()]);
        $I->sendPatchAsJson(sprintf('/player/%s', $player1Id), ['status' => PlayerStatus::KILLED->value]);

        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson([
            'currentPlayer' => [
                'status' => PlayerStatus::KILLED->value,
                'assignedMission' => null,
                'target' => null,
            ],
        ]);
    }

    public function testKillAllPlayersButOne(ApiTester $I): void
    {
        // Create admin and room
        $I->createAdminAndUpdateHeaders($I);

        $I->sendPostAsJson('room');
        $I->sendPostAsJson('/mission', ['content' => 'mission']);

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        // Join room with player1
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'mission']);
        $I->sendPostAsJson('/mission', ['content' => 'mission']);


        // Join room with player 2
        $I->createPlayerAndUpdateHeaders($I, 'Doe');
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);

        $I->sendGetAsJson(sprintf('/room/%s', $room->getId()));
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

        $I->sendPostAsJson('/mission', ['content' => 'mission']);

        // Start the game with admin
        $I->setAdminJwtHeader($I);

        $I->sendPatchAsJson(sprintf('/room/%s', $room->getId()), ['status' => 'IN_GAME']);

        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson([
            'currentPlayer' => [
                'status' => PlayerStatus::ALIVE->value,
                'assignedMission' => ['content' => 'mission'],
            ],
        ]);

        $I->seeResponseCodeIs(200);

        $I->setJwtHeader($I, self::PLAYER_NAME);

        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson([
            'currentPlayer' => [
                'status' => PlayerStatus::ALIVE->value,
                'assignedMission' => ['content' => 'mission'],
            ],
        ]);

        $I->sendPatchAsJson(sprintf('/player/%s', $player1Id), ['status' => PlayerStatus::KILLED->value]);

        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson([
            'currentPlayer' => [
                'status' => PlayerStatus::KILLED->value,
                'assignedMission' => null,
                'target' => null,
            ],
        ]);

        $I->setAdminJwtHeader($I);
        /** @var string $adminId */
        $adminId = $I->grabFromRepository(Player::class, 'id', ['name' => 'Admin']);
        $I->sendPatch(sprintf('/player/%s/kill-target-request', $adminId));
        $I->seeResponseCodeIs(200);

        $I->setJwtHeader($I, 'Doe');

        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson([
            'currentPlayer' => [
                'name' => 'Doe',
                'status' => PlayerStatus::DYING->value,
                'assignedMission' => ['content' => 'mission'],
                'target' => ['avatar' => Player::DEFAULT_AVATAR],
            ],
        ]);

        $I->sendPatchAsJson(sprintf('/player/%s', $player2Id), ['status' => PlayerStatus::KILLED->value]);

        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson([
            'currentPlayer' => [
                'room' => [
                    'status' => Room::ENDED,
                ],
                'status' => PlayerStatus::KILLED->value,
                'assignedMission' => null,
                'target' => null,
            ],
        ]);

        $I->setAdminJwtHeader($I);
        $I->sendGetAsJson(sprintf('/room/%s', $room->getId()));

        $I->seeResponseContainsJson([
            'status' => Room::ENDED,
            'winner' => [
                'name' => 'Admin',
            ],
        ]);

        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson([
            'currentPlayer' => [
                'room' => [
                    'status' => Room::ENDED,
                ],
                'status' => PlayerStatus::ALIVE->value,
                'assignedMission' => null,
                'target' => null,
            ],
        ]);
    }

    public function testWinnerBug(ApiTester $I): void
    {
        // Create admin and room
        $I->createAdminAndUpdateHeaders($I);

        $I->sendPostAsJson('room');
        $I->sendPostAsJson('/mission', ['content' => 'mission']);

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        // Join room with player1
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'mission']);
        $I->sendPostAsJson('/mission', ['content' => 'mission']);


        // Join room with player 2
        $I->createPlayerAndUpdateHeaders($I, 'Doe');
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);

        $I->sendGetAsJson(sprintf('/room/%s', $room->getId()));
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

        $I->sendPostAsJson('/mission', ['content' => 'mission']);

        // Start the game with admin
        $I->setAdminJwtHeader($I);

        $I->sendPatchAsJson(sprintf('/room/%s', $room->getId()), ['status' => 'IN_GAME']);

        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson([
            'currentPlayer' => [
                'status' => PlayerStatus::ALIVE->value,
                'assignedMission' => ['content' => 'mission'],
            ],
        ]);

        $I->seeResponseCodeIs(200);

        $I->setJwtHeader($I, self::PLAYER_NAME);

        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson([
            'currentPlayer' => [
                'status' => PlayerStatus::ALIVE->value,
                'assignedMission' => ['content' => 'mission'],
            ],
        ]);

        $I->sendPatchAsJson(sprintf('/player/%s', $player1Id), ['status' => PlayerStatus::KILLED->value]);

        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson([
            'currentPlayer' => [
                'status' => PlayerStatus::KILLED->value,
                'assignedMission' => null,
                'target' => null,
            ],
        ]);

        $I->setJwtHeader($I, 'Doe');

        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson([
            'currentPlayer' => [
                'name' => 'Doe',
                'status' => PlayerStatus::ALIVE->value,
                'assignedMission' => ['content' => 'mission'],
            ],
        ]);

        $I->sendPatchAsJson(sprintf('/player/%s', $player2Id), ['status' => PlayerStatus::KILLED->value]);

        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson([
            'currentPlayer' => [
                'room' => [
                    'status' => Room::ENDED,
                ],
                'status' => PlayerStatus::KILLED->value,
                'assignedMission' => null,
                'target' => null,
            ],
        ]);

        $I->setAdminJwtHeader($I);
        $I->sendGetAsJson(sprintf('/room/%s', $room->getId()));

        $I->seeResponseContainsJson([
            'status' => Room::ENDED,
            'winner' => [
                'name' => 'Admin',
            ],
        ]);

        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson([
            'currentPlayer' => [
                'room' => [
                    'status' => Room::ENDED,
                ],
                'status' => PlayerStatus::ALIVE->value,
                'assignedMission' => null,
                'target' => null,
            ],
        ]);

        /** @var string $adminId */
        $adminId = $I->grabFromRepository(Player::class, 'id', ['name' => 'Admin']);

        $I->setJwtHeader($I, 'Doe');
        $I->sendPatchAsJson('/user', ['room' => null]);
        $I->seeResponseCodeIsSuccessful();
        $I->sendPostAsJson('/room');
        $I->seeResponseCodeIsSuccessful();
        $I->sendPostAsJson('/mission', ['content' => 'mission']);
        $I->seeResponseCodeIsSuccessful();

        $room2 = $I->grabEntityFromRepository(Room::class, ['name' => 'Doe\'s room']);

        // Join room with player1
        $I->setJwtHeader($I, self::PLAYER_NAME);
        $I->sendPatchAsJson('/user', ['room' => $room2->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'mission']);
        $I->sendPostAsJson('/mission', ['content' => 'mission']);


        // Join room with player 2
        $I->setAdminJwtHeader($I);
        $I->sendPatchAsJson('/user', ['room' => $room2->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'mission']);

        // Start the game with admin
        $I->setJwtHeader($I, 'Doe');

        $I->sendPatchAsJson(sprintf('/room/%s', $room2->getId()), ['status' => 'IN_GAME']);

        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson([
            'currentPlayer' => [
                'status' => PlayerStatus::ALIVE->value,
                'assignedMission' => ['content' => 'mission'],
            ],
        ]);

        $I->seeResponseCodeIs(200);

        $I->setJwtHeader($I, self::PLAYER_NAME);

        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson([
            'currentPlayer' => [
                'status' => PlayerStatus::ALIVE->value,
                'assignedMission' => ['content' => 'mission'],
            ],
        ]);

        $I->sendPatchAsJson(sprintf('/player/%s', $player1Id), ['status' => PlayerStatus::KILLED->value]);

        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson([
            'currentPlayer' => [
                'status' => PlayerStatus::KILLED->value,
                'assignedMission' => null,
                'target' => null,
            ],
        ]);

        $I->setJwtHeader($I, 'Doe');

        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson([
            'currentPlayer' => [
                'name' => 'Doe',
                'status' => PlayerStatus::ALIVE->value,
                'assignedMission' => ['content' => 'mission'],
            ],
        ]);

        $I->sendPatchAsJson(sprintf('/player/%s', $player2Id), ['status' => PlayerStatus::KILLED->value]);
        $I->seeResponseCodeIsSuccessful();

        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson([
            'currentPlayer' => [
                'room' => [
                    'status' => Room::ENDED,
                ],
                'status' => PlayerStatus::KILLED->value,
                'assignedMission' => null,
                'target' => null,
            ],
        ]);

        $I->setAdminJwtHeader($I);
        $I->sendGetAsJson(sprintf('/room/%s', $room2->getId()));

        $I->seeResponseContainsJson([
            'status' => Room::ENDED,
            'winner' => [
                'name' => 'Admin',
            ],
        ]);

        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson([
            'currentPlayer' => [
                'room' => [
                    'status' => Room::ENDED,
                ],
                'status' => PlayerStatus::ALIVE->value,
                'assignedMission' => null,
                'target' => null,
            ],
        ]);
    }

    public function testAdminLeavesGame(ApiTester $I): void
    {
        // Create admin and room
        $I->createAdminAndUpdateHeaders($I);

        $I->sendPostAsJson('room');
        $I->sendPostAsJson('/mission', ['content' => 'mission']);

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        // Join room with player1
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);

        $I->sendPostAsJson('/mission', ['content' => 'mission']);

        $I->setAdminJwtHeader($I);
        $I->sendPatchAsJson('/user', ['room' => null]);

        $I->setJwtHeader($I, self::PLAYER_NAME);
        $I->sendGetAsJson(sprintf('/room/%s', $room->getId()));
        $I->seeResponseContainsJson([
            'id' => $room->getId(),
            'admin' => [
                'name' => 'John',
            ],
        ]);
    }

    public function testStartGameSuccessfullyWithGameMaster(ApiTester $I): void
    {
        // Create admin and room
        $I->createAdminAndUpdateHeaders($I);

        $I->sendPostAsJson('room', [RoomController::IS_GAME_MASTERED_ROOM => true]);
        $I->sendPostAsJson('/mission', ['content' => 'mission']);
        $I->sendPostAsJson('/mission', ['content' => 'mission']);
        $I->sendPostAsJson('/mission', ['content' => 'mission']);

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        // Join room with player1
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'mission']);
        $I->seeResponseCodeIs(403);

        // Join room with player 2
        $I->createPlayerAndUpdateHeaders($I, 'Doe');
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);

        // Join room with player 3
        $I->createPlayerAndUpdateHeaders($I, 'Jane');
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);

        // Start the game with admin
        $I->setAdminJwtHeader($I);

        $I->sendPatchAsJson(sprintf('/room/%s', $room->getId()), ['status' => 'IN_GAME']);

        $I->seeResponseCodeIs(200);

        // I can still get my player
        $I->sendGetAsJson('/user/me');

        $I->seeResponseContainsJson(
            [
                'currentPlayer' => [
                    'name' => 'Admin',
                    'room' => ['id' => $room->getId()],
                    'status' => PlayerStatus::SPECTATING->value,
                    'target' => null,
                    'assignedMission' => null,
                ],
            ],
        );

        $I->setJwtHeader($I, 'Jane');

        $I->sendGetAsJson('/user/me');

        $I->seeResponseContainsJson(
            [
                'currentPlayer' => [
                    'name' => 'Jane',
                    'room' => ['id' => $room->getId()],
                    'status' => PlayerStatus::ALIVE->value,
                    'target' => ['avatar' => Player::DEFAULT_AVATAR],
                    'assignedMission' => ['content' => 'mission'],
                ],
            ],
        );
    }

    public function testMissionSwitchAndPointsTracking(ApiTester $I): void
    {
        // Create admin and room
        $I->createAdminAndUpdateHeaders($I);

        $I->sendPostAsJson('room');
        $I->sendPostAsJson('/mission', ['content' => 'Original mission 1']);
        $I->sendPostAsJson('/mission', ['content' => 'Original mission 2']);

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        // Join room with player1 (John)
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_NAME);
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'John mission']);

        // Join room with player 2 (Doe)
        $I->createPlayerAndUpdateHeaders($I, 'Doe');
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'Doe mission']);

        // Start the game with admin
        $I->setAdminJwtHeader($I);

        $I->sendPatchAsJson(sprintf('/room/%s', $room->getId()), ['status' => 'IN_GAME']);
        $I->seeResponseCodeIs(200);

        // Verify all players start with 0 points
        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson(['currentPlayer' => ['points' => 0]]);

        $I->setJwtHeader($I, self::PLAYER_NAME);
        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson(['currentPlayer' => ['points' => 0]]);

        $I->setJwtHeader($I, 'Doe');
        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson(['currentPlayer' => ['points' => 0]]);

        // Player John switches mission (costs 5 points)
        $I->setJwtHeader($I, self::PLAYER_NAME);
        $I->sendPatch(sprintf('/player/%s/switch-mission', $player1Id));
        $I->seeResponseCodeIs(200);

        // Verify John now has -5 points and a different mission
        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson(['currentPlayer' => ['points' => -5]]);
        $I->dontSeeResponseContainsJson(['assignedMission' => ['content' => 'John mission']]);

        // Try to switch mission again - should fail
        $I->sendPatch(sprintf('/player/%s/switch-mission', $player1Id));
        $I->seeResponseCodeIs(400);

        // Admin kills John (Admin should get 10 points)
        $I->setAdminJwtHeader($I);
        /** @var string $adminId */
        $adminId = $I->grabFromRepository(Player::class, 'id', ['name' => 'Admin']);

        // Get Admin's target to find who they need to kill
        $I->sendGetAsJson('/user/me');
        /** @var array $response */
        $response = json_decode($I->grabResponse(), true);
        $adminTargetId = $response['target']['id'];

        // Request kill on Admin's target
        $I->sendPatch(sprintf('/player/%s/kill-target-request', $adminId));
        $I->seeResponseCodeIs(200);

        // Confirm the kill from the victim's side
        $victimName = $adminTargetId === $player1Id ? self::PLAYER_NAME : 'Doe';
        $victimId = $adminTargetId;

        $I->setJwtHeader($I, $victimName);
        $I->sendPatchAsJson(sprintf('/player/%s', $victimId), ['status' => PlayerStatus::KILLED->value]);
        $I->seeResponseCodeIs(200);

        // Verify the killed player's points didn't change
        $I->sendGetAsJson('/user/me');
        if ($victimName === self::PLAYER_NAME) {
            $I->seeResponseContainsJson(['currentPlayer' => ['points' => -5, 'status' => PlayerStatus::KILLED->value]]);
        } else {
            $I->seeResponseContainsJson(['currentPlayer' => ['points' => 0, 'status' => PlayerStatus::KILLED->value]]);
        }

        // Verify Admin now has 10 points
        $I->setAdminJwtHeader($I);
        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson(['currentPlayer' => ['points' => 10]]);

        // Admin kills the second player
        $I->sendPatch(sprintf('/player/%s/kill-target-request', $adminId));
        $I->seeResponseCodeIs(200);

        // Get the remaining alive player
        $I->sendGetAsJson('/user/me');
        /** @var array $response */
        $response = json_decode($I->grabResponse(), true);
        $secondTargetId = $response['target']['id'];
        $secondVictimName = $secondTargetId === $player1Id ? self::PLAYER_NAME : 'Doe';

        // Confirm the second kill
        $I->setJwtHeader($I, $secondVictimName);
        $I->sendPatchAsJson(sprintf('/player/%s', $secondTargetId), ['status' => PlayerStatus::KILLED->value]);
        $I->seeResponseCodeIs(200);

        // Verify game has ended
        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson([
            'currentPlayer' => [
                'room' => ['status' => Room::ENDED],
                'status' => PlayerStatus::KILLED->value,
            ],
        ]);

        // Verify Admin is the winner with 20 points (2 kills * 10 points)
        $I->setAdminJwtHeader($I);
        $I->sendGetAsJson(sprintf('/room/%s', $room->getId()));
        $I->seeResponseContainsJson([
            'status' => Room::ENDED,
            'winner' => ['name' => 'Admin'],
        ]);

        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson([
            'currentPlayer' => [
                'points' => 20,
                'status' => PlayerStatus::ALIVE->value,
            ],
        ]);

        // Verify final points for all players
        /** @var Player $adminPlayer */
        $adminPlayer = $I->grabEntityFromRepository(Player::class, ['name' => 'Admin']);
        assertEquals(20, $adminPlayer->getPoints(), 'Admin should have 20 points (2 kills)');

        /** @var Player $johnPlayer */
        $johnPlayer = $I->grabEntityFromRepository(Player::class, ['name' => self::PLAYER_NAME]);
        assertEquals(-5, $johnPlayer->getPoints(), 'John should have -5 points (1 mission switch)');

        /** @var Player $doePlayer */
        $doePlayer = $I->grabEntityFromRepository(Player::class, ['name' => 'Doe']);
        assertEquals(0, $doePlayer->getPoints(), 'Doe should have 0 points (no kills, no switches)');
    }
}
