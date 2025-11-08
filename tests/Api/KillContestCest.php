<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Player\PlayerRepository;
use App\Domain\Room\Entity\Room;
use App\Tests\ApiTester;

class KillContestCest
{
    public function testContestKillSuccessfully(ApiTester $I): void
    {
        $this->setupGameWithThreePlayers($I);

        // Admin tries to kill John - this sets John to DYING
        $I->setAdminJwtHeader($I);
        /** @var int $adminId */
        $adminId = $I->grabFromRepository(Player::class, 'id', ['name' => 'Admin']);

        $I->sendGetAsJson('/user/me');
        /** @var array $response */
        $response = json_decode($I->grabResponse(), true);
        $adminTargetId = $response['target']['id'];
        $adminTargetName = $response['target']['name'];

        $I->sendPatchAsJson(sprintf('/player/%s/kill-target-request', $adminId));
        $I->seeResponseCodeIs(200);

        // Verify John is now DYING
        $I->setJwtHeader($I, $adminTargetName);
        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson([
            'currentPlayer' => [
                'status' => PlayerStatus::DYING->value,
            ],
        ]);

        // John contests the kill
        $I->sendPatchAsJson(sprintf('/player/%s/kill-contest', $adminTargetId));
        $I->seeResponseCodeIs(200);

        // Verify John is now ALIVE again
        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson([
            'currentPlayer' => [
                'status' => PlayerStatus::ALIVE->value,
            ],
        ]);
    }

    public function testContestKillWhenNotDying(ApiTester $I): void
    {
        $data = $this->setupGameWithThreePlayers($I);

        // Try to contest kill when John is ALIVE (not DYING)
        $I->setJwtHeader($I, 'John');
        $I->sendPatchAsJson(sprintf('/player/%s/kill-contest', $data['johnId']));
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson(['detail' => 'PLAYER_NOT_DYING']);
    }

    public function testContestKillWhenAlreadyKilled(ApiTester $I): void
    {
        $data = $this->setupGameWithThreePlayers($I);

        // Set John to KILLED status
        $I->setJwtHeader($I, 'John');
        $I->sendPatchAsJson(sprintf('/player/%s', $data['johnId']), ['status' => PlayerStatus::KILLED->value]);
        $I->seeResponseCodeIs(200);

        // Try to contest kill when John is KILLED
        $I->sendPatchAsJson(sprintf('/player/%s/kill-contest', $data['johnId']));
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson(['detail' => 'PLAYER_NOT_DYING']);
    }

    public function testContestKillWhenRoomNotInGame(ApiTester $I): void
    {
        // Create admin and room but don't start the game
        $I->createAdminAndUpdateHeaders($I);
        $I->sendPostAsJson('/room');
        $I->sendPostAsJson('/mission', ['content' => 'Mission 1']);

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        // Join room with player1
        $I->createPlayerAndUpdateHeaders($I, 'John');
        /** @var int $player1Id */
        $player1Id = $I->grabFromRepository(Player::class, 'id', ['name' => 'John']);
        $I->sendPatchAsJson(sprintf('/player/%d', $player1Id), ['room' => $room->getId()]);

        // Manually set player to DYING (bypassing game logic for test purposes)
        $player = $I->grabEntityFromRepository(Player::class, ['id' => $player1Id]);
        $player->setStatus(PlayerStatus::DYING);
        $I->flushToDatabase();

        // Try to contest kill when game is not started
        $I->sendPatchAsJson(sprintf('/player/%d/kill-contest', $player1Id));
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson(['detail' => 'ROOM_NOT_IN_GAME']);
    }

    public function testContestKillUnauthorized(ApiTester $I): void
    {
        $this->setupGameWithThreePlayers($I);

        // Admin tries to kill John - this sets John to DYING
        $I->setAdminJwtHeader($I);
        /** @var int $adminId */
        $adminId = $I->grabFromRepository(Player::class, 'id', ['name' => 'Admin']);

        $I->sendGetAsJson('/user/me');
        /** @var array $response */
        $response = json_decode($I->grabResponse(), true);
        $adminTargetId = $response['target']['id'];
        $adminTargetName = $response['target']['name'];

        $I->sendPatchAsJson(sprintf('/player/%s/kill-target-request', $adminId));
        $I->seeResponseCodeIs(200);

        // Verify target is now DYING
        $I->setJwtHeader($I, $adminTargetName);
        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson([
            'currentPlayer' => [
                'status' => PlayerStatus::DYING->value,
            ],
        ]);

        // Try to make John contest using Doe's authentication
        $I->setJwtHeader($I, $adminTargetName === 'Doe' ? 'John' : 'Doe');
        $I->sendPatchAsJson(sprintf('/player/%s/kill-contest', $adminTargetId));
        $I->seeResponseCodeIs(403);
    }

    public function testMultipleKillContestsInGame(ApiTester $I): void
    {
        // Setup game with 4 players for more complex scenarios
        $I->createAdminAndUpdateHeaders($I);
        $I->sendPostAsJson('/room');
        $I->sendPostAsJson('/mission', ['content' => 'Mission 1']);

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        // Create 3 more players
        $I->createPlayerAndUpdateHeaders($I, 'Player1');
        /** @var int $player1Id */
        $player1Id = $I->grabFromRepository(Player::class, 'id', ['name' => 'Player1']);
        $I->sendPatchAsJson(sprintf('/player/%s', $player1Id), ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'Mission 2']);

        $I->createPlayerAndUpdateHeaders($I, 'Player2');
        /** @var int $player2Id */
        $player2Id = $I->grabFromRepository(Player::class, 'id', ['name' => 'Player2']);
        $I->sendPatchAsJson(sprintf('/player/%s', $player2Id), ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'Mission 3']);

        $I->createPlayerAndUpdateHeaders($I, 'Player3');
        /** @var int $player3Id */
        $player3Id = $I->grabFromRepository(Player::class, 'id', ['name' => 'Player3']);
        $I->sendPatchAsJson(sprintf('/player/%s', $player3Id), ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'Mission 4']);

        // Start the game
        $I->setAdminJwtHeader($I);
        $I->sendPatchAsJson(sprintf('/room/%s', $room->getId()), ['status' => 'IN_GAME']);
        $I->seeResponseCodeIs(200);

        // Get Player1's killer using repository
        $player1Entity = $I->grabEntityFromRepository(Player::class, ['id' => $player1Id]);
        /** @var PlayerRepository $playerRepository */
        $playerRepository = $I->grabService(PlayerRepository::class);
        $player1Killer = $playerRepository->findKiller($player1Entity);
        $player1KillerName = $player1Killer?->getName();

        if ($player1KillerName === null) {
            return;
        }

        // Player1's killer tries to kill Player1
        $I->setJwtHeader($I, $player1KillerName);
        $player1KillerId = $player1Killer?->getId();
        $I->sendPatchAsJson(sprintf('/player/%s/kill-target-request', $player1KillerId));
        $I->seeResponseCodeIs(200);

        // Verify Player1 is DYING
        $I->setJwtHeader($I, 'Player1');
        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson(['currentPlayer' => ['status' => PlayerStatus::DYING->value]]);

        // Player1 contests the kill
        $I->sendPatchAsJson(sprintf('/player/%s/kill-contest', $player1Id));
        $I->seeResponseCodeIs(200);

        // Verify Player1 is ALIVE
        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson([
            'currentPlayer' => [
                'status' => PlayerStatus::ALIVE->value,
            ],
        ]);

        // Now try the same with another player (Player2)
        $player2Entity = $I->grabEntityFromRepository(Player::class, ['id' => $player2Id]);
        $player2Killer = $playerRepository->findKiller($player2Entity);
        $player2KillerName = $player2Killer?->getName();

        if ($player2KillerName === null) {
            return;
        }

        // Player2's killer tries to kill Player2
        $I->setJwtHeader($I, $player2KillerName);
        $player2KillerId = $player2Killer?->getId();
        $I->sendPatchAsJson(sprintf('/player/%s/kill-target-request', $player2KillerId));
        $I->seeResponseCodeIs(200);

        // Verify Player2 is DYING
        $I->setJwtHeader($I, 'Player2');
        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson(['currentPlayer' => ['status' => PlayerStatus::DYING->value]]);

        // Player2 contests the kill
        $I->sendPatchAsJson(sprintf('/player/%s/kill-contest', $player2Id));
        $I->seeResponseCodeIs(200);

        // Verify Player2 is ALIVE
        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson([
            'currentPlayer' => [
                'status' => PlayerStatus::ALIVE->value,
            ],
        ]);
    }

    private function setupGameWithThreePlayers(ApiTester $I): array
    {
        // Create admin and room
        $I->createAdminAndUpdateHeaders($I);
        $I->sendPostAsJson('/room');
        $I->sendPostAsJson('/mission', ['content' => 'Mission 1']);

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        // Join room with player1 (John)
        $I->createPlayerAndUpdateHeaders($I, 'John');
        /** @var int $player1Id */
        $player1Id = $I->grabFromRepository(Player::class, 'id', ['name' => 'John']);
        $I->sendPatchAsJson(sprintf('/player/%d', $player1Id), ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'Mission 2']);

        // Join room with player2 (Doe)
        $I->createPlayerAndUpdateHeaders($I, 'Doe');
        /** @var int $player2Id */
        $player2Id = $I->grabFromRepository(Player::class, 'id', ['name' => 'Doe']);
        $I->sendPatchAsJson(sprintf('/player/%d', $player2Id), ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'Mission 3']);

        // Start the game with admin
        $I->setAdminJwtHeader($I);
        $I->sendPatchAsJson(sprintf('/room/%s', $room->getId()), ['status' => 'IN_GAME']);
        $I->seeResponseCodeIs(200);

        return [
            'room' => $room,
            'adminId' => $I->grabFromRepository(Player::class, 'id', ['name' => 'Admin']),
            'johnId' => $player1Id,
            'doeId' => $player2Id,
        ];
    }
}
