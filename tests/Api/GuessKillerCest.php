<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Player\PlayerRepository;
use App\Domain\Room\Entity\Room;
use App\Tests\ApiTester;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotNull;

class GuessKillerCest
{
    private function setupGameWithThreePlayers(ApiTester $I): array
    {
        // Create admin and room
        $I->createAdminAndUpdateHeaders($I);
        $I->sendPostAsJson('/room');
        $I->sendPostAsJson('/mission', ['content' => 'Mission 1']);

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        // Join room with player1 (John)
        $I->createPlayerAndUpdateHeaders($I, 'John');
        $player1Id = $I->grabFromRepository(Player::class, 'id', ['name' => 'John']);
        $I->sendPatchAsJson(sprintf('/player/%s', $player1Id), ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'Mission 2']);

        // Join room with player2 (Doe)
        $I->createPlayerAndUpdateHeaders($I, 'Doe');
        $player2Id = $I->grabFromRepository(Player::class, 'id', ['name' => 'Doe']);
        $I->sendPatchAsJson(sprintf('/player/%s', $player2Id), ['room' => $room->getId()]);
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

    public function testGuessKillerCorrectly(ApiTester $I): void
    {
        $data = $this->setupGameWithThreePlayers($I);

        // Get John's killer ID
        $I->setJwtHeader($I, 'John');
        $I->sendGetAsJson('/player/me');
        $response = json_decode($I->grabResponse(), true);
        $johnTargetId = $response['target']['id'];
        $johnId = $response['id'];
        $targetName = $I->grabFromRepository(Player::class, 'name', ['id' => $johnTargetId]);

        $I->setJwtHeader($I, $targetName);
        $I->sendGetAsJson('/player/me');

        // Verify Target starts with 0 points
        $I->seeResponseContainsJson(['points' => 0]);

        // Target guesses his killer correctly
        $I->sendPatchAsJson(sprintf('/player/%s/guess-killer', $johnTargetId), [
            'guessedPlayerId' => $johnId,
        ]);
        $I->seeResponseCodeIs(200);

        // Verify Target now has 5 points
        $I->sendGetAsJson('/player/me');
        $I->seeResponseContainsJson([
            'points' => 5,
            'status' => PlayerStatus::ALIVE->value,
        ]);

        // Verify John has been eliminated
        $I->setJwtHeader($I, 'John');
        $I->sendGetAsJson('/player/me');
        $I->seeResponseContainsJson([
            'status' => PlayerStatus::KILLED->value,
            'target' => null,
            'assignedMission' => null,
        ]);

        $newKillerName = $johnTargetId === $data['adminId'] ? 'Doe' : 'Admin';
        $killerEntity = $I->grabEntityFromRepository(Player::class, ['name' => $newKillerName]);
        $playerRepository = $I->grabService(PlayerRepository::class);
        $killersKiller = $playerRepository->findKiller($killerEntity);

        if ($killersKiller !== null) {
            $killersKillerName = $killersKiller->getName();
            $I->setJwtHeader($I, $killersKillerName);
            $I->sendGetAsJson('/player/me');

            // The killer's killer should now have the eliminated player's target
            $response = json_decode($I->grabResponse(), true);
            assertNotNull($response['target'], 'Killer\'s killer should have a new target');
        }
    }

    public function testGuessKillerWrong(ApiTester $I): void
    {
        $data = $this->setupGameWithThreePlayers($I);

        // Get John's killer ID using the repository
        $johnEntity = $I->grabEntityFromRepository(Player::class, ['id' => $data['johnId']]);
        $playerRepository = $I->grabService(PlayerRepository::class);
        $johnKiller = $playerRepository->findKiller($johnEntity);
        $johnKillerId = $johnKiller->getId();

        // Find a player ID that is NOT John's killer
        $wrongPlayerId = null;
        foreach ([$data['adminId'], $data['johnId'], $data['doeId']] as $playerId) {
            if ($playerId !== $johnKillerId && $playerId !== $data['johnId']) {
                $wrongPlayerId = $playerId;
                break;
            }
        }

        // Verify John starts with 0 points
        $I->setJwtHeader($I, 'John');
        $I->sendGetAsJson('/player/me');
        $I->seeResponseContainsJson(['points' => 0]);

        // John guesses wrong
        $I->sendPatchAsJson(sprintf('/player/%s/guess-killer', $data['johnId']), [
            'guessedPlayerId' => $wrongPlayerId,
        ]);
        $I->seeResponseCodeIs(200);

        // Verify John is now eliminated
        $I->sendGetAsJson('/player/me');
        $I->seeResponseContainsJson([
            'points' => 0,
            'status' => PlayerStatus::KILLED->value,
            'target' => null,
            'assignedMission' => null,
        ]);

        // Verify John's actual killer received no points but got John's target
        $killerName = $johnKillerId === $data['adminId'] ? 'Admin' : 'Doe';
        $I->setJwtHeader($I, $killerName);
        $I->sendGetAsJson('/player/me');
        $I->seeResponseContainsJson([
            'points' => 0,
            'status' => PlayerStatus::ALIVE->value,
        ]);

        // The killer should have John's target now
        $response = json_decode($I->grabResponse(), true);
        assertNotNull($response['target'], 'Killer should have received John\'s target');
    }

    public function testGuessKillerWhenPlayerIsDead(ApiTester $I): void
    {
        $data = $this->setupGameWithThreePlayers($I);

        // Kill John first
        $I->setJwtHeader($I, 'John');
        $I->sendPatchAsJson(sprintf('/player/%s', $data['johnId']), ['status' => PlayerStatus::KILLED->value]);
        $I->seeResponseCodeIs(200);

        // Try to guess killer when dead
        $I->sendPatchAsJson(sprintf('/player/%s/guess-killer', $data['johnId']), [
            'guessedPlayerId' => $data['adminId'],
        ]);
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson(['detail' => 'PLAYER_IS_KILLED']);
    }

    public function testGuessKillerWhenRoomNotInGame(ApiTester $I): void
    {
        // Create admin and room but don't start the game
        $I->createAdminAndUpdateHeaders($I);
        $I->sendPostAsJson('/room');
        $I->sendPostAsJson('/mission', ['content' => 'Mission 1']);

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        // Join room with player1
        $I->createPlayerAndUpdateHeaders($I, 'John');
        $player1Id = $I->grabFromRepository(Player::class, 'id', ['name' => 'John']);
        $I->sendPatchAsJson(sprintf('/player/%s', $player1Id), ['room' => $room->getId()]);

        // Try to guess killer when game is not started
        $adminId = $I->grabFromRepository(Player::class, 'id', ['name' => 'Admin']);
        $I->sendPatchAsJson(sprintf('/player/%s/guess-killer', $player1Id), [
            'guessedPlayerId' => $adminId,
        ]);
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson(['detail' => 'ROOM_NOT_IN_GAME']);
    }

    public function testGuessKillerWithoutGuessedPlayerId(ApiTester $I): void
    {
        $data = $this->setupGameWithThreePlayers($I);

        // Try to guess without providing guessedPlayerId
        $I->setJwtHeader($I, 'John');
        $I->sendPatchAsJson(sprintf('/player/%s/guess-killer', $data['johnId']), []);
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson(['detail' => 'GUESSED_PLAYER_ID_REQUIRED']);
    }

    public function testMultipleCorrectGuessesInChain(ApiTester $I): void
    {
        // Setup game with 4 players for a longer chain
        $I->createAdminAndUpdateHeaders($I);
        $I->sendPostAsJson('/room');
        $I->sendPostAsJson('/mission', ['content' => 'Mission 1']);

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        // Create 3 more players
        $I->createPlayerAndUpdateHeaders($I, 'Player1');
        $player1Id = $I->grabFromRepository(Player::class, 'id', ['name' => 'Player1']);
        $I->sendPatchAsJson(sprintf('/player/%s', $player1Id), ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'Mission 2']);

        $I->createPlayerAndUpdateHeaders($I, 'Player2');
        $player2Id = $I->grabFromRepository(Player::class, 'id', ['name' => 'Player2']);
        $I->sendPatchAsJson(sprintf('/player/%s', $player2Id), ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'Mission 3']);

        $I->createPlayerAndUpdateHeaders($I, 'Player3');
        $player3Id = $I->grabFromRepository(Player::class, 'id', ['name' => 'Player3']);
        $I->sendPatchAsJson(sprintf('/player/%s', $player3Id), ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'Mission 4']);

        // Start the game
        $I->setAdminJwtHeader($I);
        $I->sendPatchAsJson(sprintf('/room/%s', $room->getId()), ['status' => 'IN_GAME']);
        $I->seeResponseCodeIs(200);

        // Player1 guesses their killer correctly
        $I->setJwtHeader($I, 'Player1');
        $player1Entity = $I->grabEntityFromRepository(Player::class, ['id' => $player1Id]);
        $playerRepository = $I->grabService(PlayerRepository::class);
        $player1Killer = $playerRepository->findKiller($player1Entity);
        $player1KillerId = $player1Killer->getId();

        $I->sendPatchAsJson(sprintf('/player/%s/guess-killer', $player1Id), [
            'guessedPlayerId' => $player1KillerId,
        ]);
        $I->seeResponseCodeIs(200);

        // Verify Player1 has 5 points
        $I->sendGetAsJson('/player/me');
        $I->seeResponseContainsJson(['points' => 5]);

        // Verify the chain still works - Player1's killer is eliminated
        $killerEntity = $I->grabEntityFromRepository(Player::class, ['id' => $player1KillerId]);
        assertEquals(PlayerStatus::KILLED, $killerEntity->getStatus());

        // The killer's killer should have a new target
        $killersKiller = $playerRepository->findKiller($killerEntity);
        if ($killersKiller !== null) {
            assertEquals(PlayerStatus::ALIVE, $killersKiller->getStatus());
            assertNotNull($killersKiller->getTarget(), 'Killer\'s killer should have received new target');
        }
    }

    public function testGuessKillerUnauthorized(ApiTester $I): void
    {
        $data = $this->setupGameWithThreePlayers($I);

        // Try to make John guess using Doe's authentication
        $I->setJwtHeader($I, 'Doe');
        $I->sendPatchAsJson(sprintf('/player/%s/guess-killer', $data['johnId']), [
            'guessedPlayerId' => $data['adminId'],
        ]);
        $I->seeResponseCodeIs(403);
    }
}
