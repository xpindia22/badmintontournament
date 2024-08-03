<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

function getMatches($conn) {
    $query = 'SELECT m.match_id, m.round, p1.player_name AS player1, p2.player_name AS player2, m.player1_id, m.player2_id
              FROM matches m
              LEFT JOIN players p1 ON m.player1_id = p1.player_id
              LEFT JOIN players p2 ON m.player2_id = p2.player_id
              WHERE m.winner_id IS NULL';
    $result = $conn->query($query);
    if (!$result) {
        die('Query failed: ' . $conn->error);
    }
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getFixtures($conn) {
    $query = 'SELECT m.match_id, m.round, m.pool, 
              COALESCE(p1.player_name, "Unknown Player") AS player1, 
              COALESCE(p2.player_name, "Unknown Player") AS player2, 
              m.player1_score1, m.player1_score2, m.player1_score3, 
              m.player2_score1, m.player2_score2, m.player2_score3, 
              m.winner_id, m.match_date, m.player1_id, m.player2_id
              FROM matches m 
              LEFT JOIN players p1 ON m.player1_id = p1.player_id 
              LEFT JOIN players p2 ON m.player2_id = p2.player_id 
              ORDER BY FIELD(m.round, "Pre-Quarter-finals", "Quarter-finals", "Semi-finals", "Finals"), m.match_date';
    $result = $conn->query($query);
    if (!$result) {
        die('Query failed: ' . $conn->error);
    }
    return $result->fetch_all(MYSQLI_ASSOC);
}

function updateMatchResults($conn, $matchId, $player1Scores, $player2Scores, $player1Id, $player2Id) {
    $player1Wins = 0;
    $player2Wins = 0;

    for ($i = 0; $i < 3; $i++) {
        if ($player1Scores[$i] > $player2Scores[$i]) {
            $player1Wins++;
        } elseif ($player1Scores[$i] < $player2Scores[$i]) {
            $player2Wins++;
        }
    }

    $winnerId = $player1Wins > $player2Wins ? $player1Id : $player2Id;

    $query = 'UPDATE matches SET player1_score1 = ?, player1_score2 = ?, player1_score3 = ?, 
                                player2_score1 = ?, player2_score2 = ?, player2_score3 = ?, 
                                winner_id = ? 
              WHERE match_id = ?';
    $stmt = $conn->prepare($query);
    $stmt->bind_param('iiiiiiii', $player1Scores[0], $player1Scores[1], $player1Scores[2], 
                                   $player2Scores[0], $player2Scores[1], $player2Scores[2], 
                                   $winnerId, $matchId);
    $stmt->execute();

    // Check if all matches in the current round are completed
    $query = 'SELECT round, pool FROM matches WHERE match_id = ?';
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $matchId);
    $stmt->execute();
    $result = $stmt->get_result();
    $match = $result->fetch_assoc();

    $currentRound = $match['round'];
    $pool = $match['pool'];

    $query = 'SELECT COUNT(*) as count FROM matches WHERE round = ? AND pool = ? AND winner_id IS NULL';
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $currentRound, $pool);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];

    // If all matches are completed, create fixtures for the next round
    if ($count == 0) {
        $rounds = [
            'Pre-Quarter-finals' => 'Quarter-finals', 
            'Quarter-finals' => 'Semi-finals', 
            'Semi-finals' => 'Finals'
        ];
        if (array_key_exists($currentRound, $rounds)) {
            $nextRound = $rounds[$currentRound];
            createNextRoundFixtures($conn, $currentRound, $nextRound);
        }
    }
}

function createNextRoundFixtures($conn, $currentRound, $nextRound) {
    $query = 'SELECT winner_id, pool FROM matches WHERE round = ? AND winner_id IS NOT NULL';
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $currentRound);
    $stmt->execute();
    $result = $stmt->get_result();
    $winners = $result->fetch_all(MYSQLI_ASSOC);

    $winnersByPool = [];
    foreach ($winners as $winner) {
        $winnersByPool[$winner['pool']][] = $winner['winner_id'];
    }

    foreach ($winnersByPool as $pool => $winners) {
        for ($i = 0; $i < count($winners); $i += 2) {
            if (isset($winners[$i + 1])) {
                $query = 'INSERT INTO matches (round, pool, player1_id, player2_id) VALUES (?, ?, ?, ?)';
                $stmt = $conn->prepare($query);
                $stmt->bind_param('ssii', $nextRound, $pool, $winners[$i], $winners[$i + 1]);
                $stmt->execute();
            }
        }
    }
}

function assignPlayersToPools($conn) {
    $pools = ['A', 'B'];
    $players = [];

    // Fetch players from the database
    $query = 'SELECT player_id FROM players';
    $result = $conn->query($query);

    while ($row = $result->fetch_assoc()) {
        $players[] = $row['player_id'];
    }

    // Shuffle and split players into pools
    shuffle($players);
    $poolA = array_slice($players, 0, 8);
    $poolB = array_slice($players, 8, 8);

    // Insert fixtures for Pre-Quarter-finals
    $round = 'Pre-Quarter-finals';

    foreach ([$poolA, $poolB] as $index => $pool) {
        $poolName = $pools[$index];
        for ($i = 0; $i < count($pool); $i += 2) {
            if (isset($pool[$i + 1])) {
                $query = 'INSERT INTO matches (round, pool, player1_id, player2_id) VALUES (?, ?, ?, ?)';
                $stmt = $conn->prepare($query);
                $stmt->bind_param('ssii', $round, $poolName, $pool[$i], $pool[$i + 1]);
                $stmt->execute();
            }
        }
    }

    // Update the settings table to indicate fixtures have been created
    $query = 'UPDATE settings SET value = "yes" WHERE key_name = "fixtures_created"';
    $conn->query($query);

    echo "Players assigned to pools and fixtures created successfully!";
}

function fixturesCreated($conn) {
    $query = 'SELECT value FROM settings WHERE key_name = "fixtures_created"';
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        return $row['value'] === 'yes';
    }
    return false;
}

function getQuarterFinalWinners($conn) {
    $sql = "SELECT winner_id FROM matches WHERE round = 'Quarter-finals'";
    $result = $conn->query($sql);

    $winners = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if (!is_null($row['winner_id'])) {
                $winners[] = $row['winner_id'];
            }
        }
    }
    return $winners;
}

function createSemiFinalFixtures($conn) {
    // Get the quarter-final winners
    $winners = getQuarterFinalWinners($conn);

    if (count($winners) < 4) {
        echo "Not enough players to create semi-final fixtures. Players found: " . count($winners);
        return;
    }

    // Create fixtures for the semi-finals
    $fixtures = [];
    $fixtures[] = ['player1' => $winners[0], 'player2' => $winners[1]];
    $fixtures[] = ['player1' => $winners[2], 'player2' => $winners[3]];

    // Insert the semi-final fixtures into the database
    foreach ($fixtures as $fixture) {
        $sql = "INSERT INTO matches (player1_id, player2_id, round) VALUES (?, ?, 'Semi-finals')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $fixture['player1'], $fixture['player2']);
        $stmt->execute();
    }

    echo "Semi-final fixtures created successfully.";
}
?>
