<?php
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

function updateMatchResults($conn, $matchId, $player1Scores, $player2Scores) {
    $player1Wins = 0;
    $player2Wins = 0;

    for ($i = 0; $i < 3; $i++) {
        if ($player1Scores[$i] > $player2Scores[$i]) {
            $player1Wins++;
        } elseif ($player1Scores[$i] < $player2Scores[$i]) {
            $player2Wins++;
        }
    }

    $winnerId = $player1Wins > $player2Wins ? $_POST['player1_id'] : $_POST['player2_id'];

    $query = 'UPDATE matches SET player1_score1 = ?, player1_score2 = ?, player1_score3 = ?, 
                                player2_score1 = ?, player2_score2 = ?, player2_score3 = ?, 
                                winner_id = ? 
              WHERE match_id = ?';
    $stmt = $conn->prepare($query);
    $stmt->bind_param('iiiiiiii', $player1Scores[0], $player1Scores[1], $player1Scores[2], 
                                   $player2Scores[0], $player2Scores[1], $player2Scores[2], 
                                   $winnerId, $matchId);
    $stmt->execute();
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
?>
