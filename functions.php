<?php
require_once 'config.php'; // Ensure this points to your database configuration file

// Fetch fixtures from the database organized by rounds
function getFixtures($conn) {
    $query = "SELECT m.match_id, m.round, p1.player_name AS player1, p2.player_name AS player2, 
              m.player1_id, m.player2_id, m.winner_id
              FROM mens_open_matches m
              LEFT JOIN mens_open_players p1 ON m.player1_id = p1.player_id
              LEFT JOIN mens_open_players p2 ON m.player2_id = p2.player_id
              ORDER BY FIELD(m.round, 'Round of 32', 'Round of 16', 'Quarter-finals', 'Semi-finals', 'Finals')";
    $result = $conn->query($query);
    if (!$result) {
        die('Query failed: ' . $conn->error);
    }
    return $result->fetch_all(MYSQLI_ASSOC);
}

function updateMatchResults($conn, $matchId, $player1Scores, $player2Scores, $player1Id, $player2Id) {
    $winnerId = determineWinner($player1Scores, $player2Scores, $player1Id, $player2Id);

    $stmt = $conn->prepare('UPDATE mens_open_matches SET player1_set1 = ?, player1_set2 = ?, player1_set3 = ?, 
                            player2_set1 = ?, player2_set2 = ?, player2_set3 = ?, winner_id = ? 
                            WHERE match_id = ?');
    $stmt->bind_param('iiiiiiii', $player1Scores[0], $player1Scores[1], $player1Scores[2], 
                      $player2Scores[0], $player2Scores[1], $player2Scores[2], $winnerId, $matchId);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        echo "Match result updated successfully!";
        return $winnerId;
    } else {
        echo "Error updating match result: " . $stmt->error;
        return false;
    }
}

// Determine the winner based on scores
function determineWinner($scores1, $scores2, $id1, $id2) {
    $countWins1 = 0;
    $countWins2 = 0;
    for ($i = 0; $i < count($scores1); $i++) {
        if ($scores1[$i] > $scores2[$i]) {
            $countWins1++;
        } elseif ($scores1[$i] < $scores2[$i]) {
            $countWins2++;
        }
    }
    return $countWins1 > $countWins2 ? $id1 : $id2;
}

function createNextRoundFixtures($conn, $currentRound, $nextRound) {
    $query = "SELECT GROUP_CONCAT(winner_id ORDER BY match_id) AS winners
              FROM mens_open_matches
              WHERE round = ? AND winner_id IS NOT NULL";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $currentRound);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $winners = explode(',', $row['winners']);
        for ($i = 0; $i < count($winners); $i += 2) {
            if (isset($winners[$i + 1])) {
                $query = 'INSERT INTO mens_open_matches (round, player1_id, player2_id) VALUES (?, ?, ?, ?)';
                $stmt = $conn->prepare($query);
                $stmt->bind_param('siii', $nextRound, $winners[$i], $winners[$i + 1]);
                if (!$stmt->execute()) {
                    die('Create next round fixtures failed: ' . $stmt->error);
                }
            }
        }
    }
}

function assignPlayersToPools($conn) {
    $players = [];

    // Fetch players from the database
    $query = 'SELECT player_id FROM mens_open_players';
    $result = $conn->query($query);

    while ($row = $result->fetch_assoc()) {
        $players[] = $row['player_id'];
    }

    // Shuffle and split players into fixtures
    shuffle($players);
    $round = 'Round of 32';

    for ($i = 0; $i < count($players); $i += 2) {
        if (isset($players[$i + 1])) {
            $query = 'INSERT INTO mens_open_matches (round, player1_id, player2_id) VALUES (?, ?, ?, ?)';
            $stmt = $conn->prepare($query);
            $stmt->bind_param('sii', $round, $players[$i], $players[$i + 1]);
            if (!$stmt->execute()) {
                die('Assign players to fixtures failed: ' . $stmt->error);
            }
        }
    }

    // Update the settings table to indicate fixtures have been created
    $query = 'UPDATE settings SET value = "yes" WHERE key_name = "fixtures_created"';
    if (!$conn->query($query)) {
        die('Update settings failed: ' . $conn->error);
    }

    echo "Players assigned to fixtures and matches created successfully!";
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

function getEligiblePlayers($conn) {
    $query = 'SELECT player_id, player_name FROM mens_open_players';
    $result = $conn->query($query);
    if (!$result) {
        die('Query failed: ' . $conn->error);
    }
    return $result->fetch_all(MYSQLI_ASSOC);
}
?>
