<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Domain\Mission\Entity\Mission;
use App\Domain\Player\Entity\Player;
use App\Domain\Room\Entity\Room;
use App\Tests\ApiTester;

class SecondaryMissionsAndSwitchingCest
{
    public const string PLAYER_1 = 'Player1';
    public const string PLAYER_2 = 'Player2';
    public const string PLAYER_3 = 'Player3';

    public function testSecondaryMissionsAreCreatedWhenGameStarts(ApiTester $I): void
    {
        // Setup: Create a room with 3 players
        $I->createAdminAndUpdateHeaders($I);
        $I->sendPostAsJson('room');
        $I->sendPostAsJson('/mission', ['content' => 'Admin mission']);

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        // Player 1 joins
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_1);
        $player1Id = $I->grabFromRepository(Player::class, 'id', ['name' => self::PLAYER_1]);
        $I->sendPatchAsJson(sprintf('player/%s', $player1Id), ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'Player1 mission']);

        // Player 2 joins
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_2);
        $player2Id = $I->grabFromRepository(Player::class, 'id', ['name' => self::PLAYER_2]);
        $I->sendPatchAsJson(sprintf('player/%s', $player2Id), ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'Player2 mission']);

        // Player 3 joins
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_3);
        $player3Id = $I->grabFromRepository(Player::class, 'id', ['name' => self::PLAYER_3]);
        $I->sendPatchAsJson(sprintf('player/%s', $player3Id), ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'Player3 mission']);

        // Start the game (4 players total including admin, should generate 8 secondary missions)
        $I->setAdminJwtHeader($I);
        $I->sendPatchAsJson(sprintf('/room/%s', $room->getId()), ['status' => 'IN_GAME']);
        $I->seeResponseCodeIs(200);

        // Verify secondary missions were created in database
        $room = $I->grabEntityFromRepository(Room::class, ['id' => $room->getId()]);
        $secondaryMissions = $room->getSecondaryMissions();

        $I->assertCount(8, $secondaryMissions, 'Expected 8 secondary missions (4 players * 2)');

        // Verify all secondary missions have content and no author
        foreach ($secondaryMissions as $mission) {
            $I->assertNotEmpty($mission->getContent());
            $I->assertNull($mission->getAuthor(), 'Secondary missions should not have an author');
        }
    }

    public function testPlayerCanSwitchMissionUsingSecondaryPool(ApiTester $I): void
    {
        // Setup: Create a game with 3 players
        $I->createAdminAndUpdateHeaders($I);
        $I->sendPostAsJson('room');
        $I->sendPostAsJson('/mission', ['content' => 'Admin mission']);

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_1);
        $player1Id = $I->grabFromRepository(Player::class, 'id', ['name' => self::PLAYER_1]);
        $I->sendPatchAsJson(sprintf('player/%s', $player1Id), ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'Player1 mission']);

        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_2);
        $player2Id = $I->grabFromRepository(Player::class, 'id', ['name' => self::PLAYER_2]);
        $I->sendPatchAsJson(sprintf('player/%s', $player2Id), ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'Player2 mission']);

        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_3);
        $player3Id = $I->grabFromRepository(Player::class, 'id', ['name' => self::PLAYER_3]);
        $I->sendPatchAsJson(sprintf('player/%s', $player3Id), ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'Player3 mission']);

        // Start the game
        $I->setAdminJwtHeader($I);
        $I->sendPatchAsJson(sprintf('/room/%s', $room->getId()), ['status' => 'IN_GAME']);

        // Count secondary missions before switch
        $room = $I->grabEntityFromRepository(Room::class, ['id' => $room->getId()]);
        $countBefore = $room->getSecondaryMissions()->count();

        // Get player's current mission
        $I->setJwtHeader($I, self::PLAYER_1);
        $I->sendGetAsJson('/player/me');
        $response = json_decode($I->grabResponse(), true);
        $originalMissionId = $response['assignedMission']['id'];

        // Switch mission
        $I->sendPostAsJson(sprintf('/player/%s/switch-mission', $player1Id));
        $I->seeResponseCodeIs(200);

        // Verify new mission is assigned
        $I->sendGetAsJson('/player/me');
        $responseAfter = json_decode($I->grabResponse(), true);
        $newMissionId = $responseAfter['assignedMission']['id'];

        $I->assertNotEquals($originalMissionId, $newMissionId, 'Mission should have changed');

        // Verify a secondary mission was consumed from the pool
        $room = $I->grabEntityFromRepository(Room::class, ['id' => $room->getId()]);
        $countAfter = $room->getSecondaryMissions()->count();

        $I->assertEquals($countBefore - 1, $countAfter, 'One secondary mission should have been removed from pool');

        // Verify mission switch was used (points deducted)
        $I->assertEquals($responseAfter['points'], -5, 'Player should have -5 points after switching');
        $I->assertTrue($responseAfter['missionSwitchUsed'], 'Mission switch should be marked as used');
    }

    public function testPlayerCannotSwitchMissionTwice(ApiTester $I): void
    {
        // Setup game with players
        $I->createAdminAndUpdateHeaders($I);
        $I->sendPostAsJson('room');
        $I->sendPostAsJson('/mission', ['content' => 'Admin mission']);

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_1);
        $player1Id = $I->grabFromRepository(Player::class, 'id', ['name' => self::PLAYER_1]);
        $I->sendPatchAsJson(sprintf('player/%s', $player1Id), ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'Player1 mission']);

        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_2);
        $player2Id = $I->grabFromRepository(Player::class, 'id', ['name' => self::PLAYER_2]);
        $I->sendPatchAsJson(sprintf('player/%s', $player2Id), ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'Player2 mission']);

        // Start game
        $I->setAdminJwtHeader($I);
        $I->sendPatchAsJson(sprintf('/room/%s', $room->getId()), ['status' => 'IN_GAME']);

        // First switch - should work
        $I->setJwtHeader($I, self::PLAYER_1);
        $I->sendPostAsJson(sprintf('/player/%s/switch-mission', $player1Id));
        $I->seeResponseCodeIs(200);

        // Second switch - should fail
        $I->sendPostAsJson(sprintf('/player/%s/switch-mission', $player1Id));
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson(['code' => 400, 'message' => 'MISSION_SWITCH_ALREADY_USED']);
    }

    public function testSecondaryPoolDepletionFallbackToGeneration(ApiTester $I): void
    {
        // Setup: Create a room with 2 players (will generate 4 secondary missions)
        $I->createAdminAndUpdateHeaders($I);
        $I->sendPostAsJson('room');
        $I->sendPostAsJson('/mission', ['content' => 'Admin mission']);

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_1);
        $player1Id = $I->grabFromRepository(Player::class, 'id', ['name' => self::PLAYER_1]);
        $I->sendPatchAsJson(sprintf('player/%s', $player1Id), ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'Player1 mission']);

        // Start game (2 players = 4 secondary missions)
        $I->setAdminJwtHeader($I);
        $I->sendPatchAsJson(sprintf('/room/%s', $room->getId()), ['status' => 'IN_GAME']);

        // Verify 4 secondary missions exist
        $room = $I->grabEntityFromRepository(Room::class, ['id' => $room->getId()]);
        $secondaryMissions = $room->getSecondaryMissions();
        $I->assertCount(4, $secondaryMissions);

        // Manually deplete the pool by removing all secondary missions
        foreach ($secondaryMissions->toArray() as $mission) {
            $room->removeSecondaryMission($mission);
        }
        $I->flushToDatabase();

        // Now try to switch - should fall back to generation
        $I->setAdminJwtHeader($I);
        $I->sendGetAsJson('/player/me');
        $adminResponse = json_decode($I->grabResponse(), true);
        $adminId = $adminResponse['id'];

        $I->sendPostAsJson(sprintf('/player/%s/switch-mission', $adminId));
        $I->seeResponseCodeIs(200);

        // Verify new mission was created (not from secondary pool)
        $I->sendGetAsJson('/player/me');
        $responseAfter = json_decode($I->grabResponse(), true);
        $I->assertNotNull($responseAfter['assignedMission']);

        // The new mission should be assigned to the room but not as a secondary mission
        $room = $I->grabEntityFromRepository(Room::class, ['id' => $room->getId()]);
        $allMissions = $I->grabEntitiesFromRepository(Mission::class, ['room' => $room]);
        $generatedMissionCount = 0;
        foreach ($allMissions as $mission) {
            if (!$room->getSecondaryMissions()->contains($mission) && $mission->getAuthor() === null) {
                $generatedMissionCount++;
            }
        }
        $I->assertGreaterThan(0, $generatedMissionCount, 'At least one generated mission should exist');
    }

    public function testSecondaryMissionsCountScalesWithPlayerCount(ApiTester $I): void
    {
        // Test with different player counts
        $testCases = [
            ['players' => 2, 'expectedSecondaryMissions' => 4],
            ['players' => 3, 'expectedSecondaryMissions' => 6],
            ['players' => 5, 'expectedSecondaryMissions' => 10],
        ];

        foreach ($testCases as $testCase) {
            // Create admin and room
            $I->createAdminAndUpdateHeaders($I);
            $I->sendPostAsJson('room');
            $I->sendPostAsJson('/mission', ['content' => 'Admin mission']);

            $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

            // Add required number of additional players (minus 1 for admin)
            for ($i = 1; $i < $testCase['players']; $i++) {
                $playerName = "TestPlayer{$i}";
                $I->createPlayerAndUpdateHeaders($I, $playerName);
                $playerId = $I->grabFromRepository(Player::class, 'id', ['name' => $playerName]);
                $I->sendPatchAsJson(sprintf('player/%s', $playerId), ['room' => $room->getId()]);
                $I->sendPostAsJson('/mission', ['content' => "{$playerName} mission"]);
            }

            // Start game
            $I->setAdminJwtHeader($I);
            $I->sendPatchAsJson(sprintf('/room/%s', $room->getId()), ['status' => 'IN_GAME']);

            // Verify correct number of secondary missions
            $room = $I->grabEntityFromRepository(Room::class, ['id' => $room->getId()]);
            $secondaryMissions = $room->getSecondaryMissions();

            $I->assertCount(
                $testCase['expectedSecondaryMissions'],
                $secondaryMissions,
                sprintf(
                    'Expected %d secondary missions for %d players',
                    $testCase['expectedSecondaryMissions'],
                    $testCase['players']
                )
            );

            // Clean up for next test
            $I->sendDeleteAsJson(sprintf('/room/%s', $room->getId()));
        }
    }
}
